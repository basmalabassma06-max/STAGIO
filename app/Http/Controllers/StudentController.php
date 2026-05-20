<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Offer;
use App\Models\Notification;
use App\Models\Skill;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Internship;

class StudentController extends Controller
{
    // ================= HELPERS =================

    private function student()
    {
        $user = auth()->user();
        if (!$user || !$user->student) {
            abort(403, 'Student profile not found');
        }
        return $user->student;
    }

    private function success($data = null, $message = null)
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'OK',
            'data'    => $data,
        ]);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    // ── CHANGED: removed cv file slot; digital CV = cv_summary + (education|experience) ──
    private function getCompletion($s)
    {
        $fields = [
            $s->university,
            $s->wilaya,
            $s->bio,
            $s->github_link,
            $s->phone,
            $s->linkedin,
            $s->portfolio,
        ];

        $filled = collect($fields)->filter()->count();

        $skillsCount = $s->loadCount('skills')->skills_count;
        if ($skillsCount > 0) $filled++;

        // Digital CV counts as one completion slot
        if ($s->hasDigitalCv()) $filled++;

        $total = count($fields) + 2; // +1 skills +1 digitalCV

        return round(($filled / $total) * 100);
    }

    // ── CHANGED: 'cv' → 'digital_cv' in missing fields ──
    private function getMissingFields($s)
    {
        $missing = [];

        if (!$s->hasDigitalCv()) $missing[] = 'digital_cv';
        if (!$s->bio)            $missing[] = 'bio';
        if (!$s->education)      $missing[] = 'education';
        if (!$s->experience)     $missing[] = 'experience';
        if (!$s->projects)       $missing[] = 'projects';
        if (!$s->phone)          $missing[] = 'phone';

        $skillsCount = $s->relationLoaded('skills')
            ? $s->skills->count()
            : $s->skills()->count();

        if (!$skillsCount) $missing[] = 'skills';

        return $missing;
    }

    // ================= DASHBOARD ================= (unchanged)

    public function dashboard(Request $request)
    {
        $s    = $this->student();
        $base = $s->applications();

        return $this->success([
            'applications' => (clone $base)
                ->with('offer.company')
                ->latest()
                ->paginate($request->per_page ?? 10),
            'stats' => [
                'total'               => (clone $base)->count(),
                'accepted_by_company' => (clone $base)->where('status', 'accepted_by_company')->count(),
                'validated'           => Internship::where('student_id', $s->id)
                                            ->where('status', Internship::VALIDATED)->count(),
                'rejected_by_company' => (clone $base)->where('status', 'rejected_by_company')->count(),
                'rejected_by_admin'   => (clone $base)->where('status', 'rejected_by_admin')->count(),
            ],
        ]);
    }

    // ================= PROFILE ================= (unchanged)

    public function profile()
    {
        $s = $this->student();

        return $this->success([
            'user'       => auth()->user()->load('student.skills'),
            'completion' => $this->getCompletion($s),
            'missing'    => $this->getMissingFields($s),
        ]);
    }

    // ── CHANGED: removed file upload; added cv_summary + cv_languages ──
    public function updateProfile(Request $request)
    {
        $request->validate([
            'university'   => 'sometimes|string|max:255',
            'wilaya'       => 'sometimes|string|max:100',
            'github_link'  => 'nullable|url',
            'bio'          => 'nullable|string',
            'phone'        => 'nullable|string',
            'education'    => 'nullable|string',
            'experience'   => 'nullable|string',
            'projects'     => 'nullable|string',
            'linkedin'     => 'nullable|url',
            'portfolio'    => 'nullable|url',
            // Digital CV
            'cv_summary'   => 'nullable|string|max:2000',
            'cv_languages' => 'nullable|string|max:500',
        ]);

        $s = $this->student();

        $data = array_filter(
            $request->only([
                'university', 'wilaya', 'github_link', 'bio',
                'phone', 'education', 'experience', 'projects',
                'linkedin', 'portfolio',
                'cv_summary', 'cv_languages',
            ]),
            fn($v) => !is_null($v)
        );

        $s->update($data);

        return $this->success($s->fresh(), 'Profile updated');
    }

    // ── CHANGED: clears only digital CV fields (summary + languages) ──
    // education/experience/projects are shared with the profile, so NOT cleared here
    public function clearDigitalCv()
    {
        $s = $this->student();

        $s->update([
            'cv_summary'   => null,
            'cv_languages' => null,
        ]);

        return $this->success(null, 'Digital CV cleared');
    }

    // ── REMOVED: deleteCv(), downloadCv() ──

    // ================= SKILLS ================= (unchanged)

    public function addSkills(Request $request)
    {
        $request->validate([
            'skills'   => 'required|array|min:1|max:10',
            'skills.*' => 'string|max:50',
        ]);

        $s = $this->student();

        foreach ($request->skills as $name) {
            $skill = Skill::firstOrCreate(['name' => strtolower(trim($name))]);
            $s->skills()->syncWithoutDetaching([$skill->id]);
        }

        return $this->success($s->skills()->get(), 'Skills updated');
    }

    public function removeSkill($id)
    {
        $student = $this->student();

        if (!$student->skills()->where('skills.id', $id)->exists()) {
            return $this->error('Skill not found');
        }

        $student->skills()->detach($id);

        return $this->success($student->skills()->get(), 'Skill removed');
    }

    // ================= OFFERS ================= (unchanged)

    public function offers(Request $request)
    {
        $student = $this->student();

        $query = Offer::with(['company:id,name,location', 'skills:id,name'])
            ->withExists([
                'applications as applied' => fn($q) => $q->where('student_id', $student->id),
                'savedOffers as saved'    => fn($q) => $q->where('student_id', $student->id),
            ])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>', now());
            });

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($qq) => $qq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('skills',  fn($qq) => $qq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('wilaya')) $query->where('location', $request->wilaya);

        return $this->success($query->latest()->paginate($request->per_page ?? 9));
    }

    // ================= MATCHING ================= (unchanged)

    public function advancedRecommended(Request $request)
{
    $student = $this->student();
    $skills  = $student->skills()->pluck('skills.id');

    $offers = Offer::with(['company', 'skills'])
        ->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('deadline')->orWhere('deadline', '>', now());
        })
        ->get();

    $scored = $offers->map(function ($o) use ($skills, $student) {
        $offerSkills = $o->skills->pluck('id');
        
        // ✅ Skills
        if ($offerSkills->count() > 0 && $skills->count() > 0) {
            $skillsMatch = $offerSkills->intersect($skills)->count();
            $skillsScore = ($skillsMatch / $offerSkills->count()) * 60;
        } else {
            $skillsScore = 0;
        }

        // ✅ Location
        $locationScore = ($o->location === $student->wilaya) ? 20 : 0;

        // ✅ Type
        $typeScore = ($o->type === 'internship') ? 20 : 10;

        $o->score = round($skillsScore + $locationScore + $typeScore);
        return $o;
    });

    $sorted  = $scored->sortByDesc('score')->values();
    $page    = $request->get('page', 1);
    $perPage = 10;

    $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
        $sorted->forPage($page, $perPage),
        $sorted->count(),
        $perPage,
        $page
    );

    return $this->success($paginated);
}

    // ================= APPLICATION =================
    // ── CHANGED: `if (!$s->cv)` → `if (!$s->hasDigitalCv())` ──

    public function apply(Request $request, $offer_id)
    {
        $request->validate(['motivation' => 'nullable|string|max:1000']);

        $s     = $this->student();
        $offer = Offer::with('company', 'skills')->findOrFail($offer_id);

        if (!$offer->is_active)                              return $this->error('Offer not available');
        if ($offer->deadline && now()->gt($offer->deadline)) return $this->error('Offer expired');
        if (!$offer->company)                                return $this->error('Invalid offer');

        // ── CHANGED ──
        if (!$s->hasDigitalCv()) {
            return $this->error('Please fill in your Digital CV first (summary + education or experience)');
        }

        if ($this->getCompletion($s) < 50) {
            return $this->error('Please complete at least 50% of your profile');
        }

        $alreadyApplied = Application::where('student_id', $s->id)
            ->where('offer_id', $offer_id)
            ->whereIn('status', [
                Application::PENDING,
                Application::ACCEPTED_BY_COMPANY,
                Application::PENDING_ADMIN,
            ])
            ->exists()
            ||
            Internship::where('student_id', $s->id)
                ->where('offer_id', $offer_id)
                ->whereIn('status', [Internship::PENDING_ADMIN, Internship::VALIDATED])
                ->exists();

        if ($alreadyApplied) return $this->error('Already applied');

        $dailyLimit = 100;
        if (Application::where('student_id', $s->id)
                ->whereDate('created_at', today())->count() >= $dailyLimit) {
            return $this->error("Daily limit reached ($dailyLimit)");
        }

        if (!$offer->company || !$offer->company->user_id) return $this->error('Invalid company');

        $existing = Application::where('student_id', $s->id)
            ->where('offer_id', $offer_id)->first();

        if ($existing) {
            if ($existing->status === Application::WITHDRAWN) {
                $existing->update([
                    'status'     => Application::PENDING,
                    'motivation' => $request->motivation,
                ]);
                return $this->success($existing, 'Re-applied');
            }
            return $this->error('Already applied');
        }

        \DB::beginTransaction();
        try {
            $app = Application::create([
                'student_id' => $s->id,
                'offer_id'   => $offer_id,
                'status'     => Application::PENDING,
                'motivation' => $request->motivation,
            ]);

            Notification::create([
                'user_id' => $offer->company->user_id,
                'type'    => 'application_sent',
                'message' => auth()->user()->name . ' applied to your offer: ' . $offer->title,
                'is_read' => false,
                'data'    => ['offer_id' => $offer->id],
            ]);

            \DB::commit();

            return $this->success(
                ['application' => $app, 'offer' => $offer->title],
                'Applied successfully'
            );

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Apply failed', [
                'student_id' => $s->id,
                'offer_id'   => $offer_id,
                'error'      => $e->getMessage(),
            ]);
            return $this->error($e->getMessage());
        }
    }

    // ================= (remaining methods — all unchanged) =================

    public function updateApplication(Request $request, $id)
    {
        $request->validate([
            'motivation' => 'nullable|string|max:1000',
            'cv'         => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $app = Application::where('id', $id)
            ->where('student_id', $this->student()->id)
            ->firstOrFail();

        if ($app->status !== Application::PENDING)
            return $this->error('Application locked');

        if ($request->hasFile('cv')) {
            if ($app->cv && Storage::disk('public')->exists($app->cv))
                Storage::disk('public')->delete($app->cv);
            $app->cv = $request->file('cv')->storeAs('applications', uniqid() . '.pdf', 'public');
        }

        if ($request->filled('motivation')) $app->motivation = $request->motivation;

        $app->save();

        return $this->success($app, 'Updated');
    }

    public function withdraw($id)
    {
        $app = Application::where('id', $id)
            ->where('student_id', $this->student()->id)
            ->firstOrFail();

        if (!in_array($app->status, [
            Application::PENDING,
            Application::REJECTED_BY_COMPANY,
            Application::REJECTED_BY_ADMIN,
        ])) return $this->error('Cannot withdraw');

        $app->update(['status' => Application::WITHDRAWN]);

        return $this->success(null, 'Withdrawn');
    }

    public function myApplications(Request $request)
    {
        return $this->success(
            $this->student()->applications()
                ->with('offer.company')
                ->latest()
                ->paginate($request->per_page ?? 10)
        );
    }

    public function notifications()
    {
        return $this->success(
            Notification::where('user_id', auth()->id())
                ->orderByRaw('is_read asc, created_at desc')
                ->paginate(10)
        );
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);

        return $this->success(null, 'Marked as read');
    }

    public function unreadCount()
    {
        return $this->success([
            'count' => Notification::where('user_id', auth()->id())
                ->where('is_read', false)->count(),
        ]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())->update(['is_read' => true]);
        return $this->success(null, 'All notifications marked as read');
    }

    public function generateDocument($id)
    {
        $s = $this->student();

        if ($this->getCompletion($s) < 80)
            return $this->error('Complete profile to 80%');

        $internship = Internship::where('id', $id)
            ->where('status', Internship::VALIDATED)
            ->where('student_id', $s->id)
            ->firstOrFail();

        $app = Application::where('offer_id', $internship->offer_id)
            ->where('student_id', $s->id)
            ->firstOrFail();

        $existing = Document::where('internship_id', $internship->id)
            ->where('type', 'convention')->first();

        if ($existing) return $this->success($existing, 'Already exists');

        $offer = Offer::with('company')->findOrFail($app->offer_id);

        if (!$offer->is_active) return $this->error('Offer not active');

        \DB::beginTransaction();
        try {
            $pdf = Pdf::loadView('pdf.convention', [
                'internship' => $internship,
                'uni'        => [
                    'name'       => 'Université Ferhat Abbas',
                    'faculty'    => 'Science',
                    'department' => 'Informatique',
                    'logo'       => file_exists(public_path('logo.png'))
                        ? public_path('logo.png') : null,
                ],
            ]);

            $path = 'documents/' . uniqid() . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());

            $doc = Document::create([
                'internship_id' => $internship->id,
                'file_path'     => $path,
                'type'          => 'convention',
                'generated_at'  => now(),
            ]);

            \DB::commit();

            return $this->success($doc, 'Generated');

        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->error('Failed to generate document');
        }
    }

    public function downloadDocument($id)
    {
        $doc = Document::where('id', $id)
            ->whereHas('internship', fn($q) => $q->where('student_id', $this->student()->id))
            ->firstOrFail();

        if (str_contains($doc->file_path, '..'))
            return $this->error('Invalid path');

        if (!str_starts_with($doc->file_path, 'documents/'))
            return $this->error('Invalid file');

        if (!Storage::disk('public')->exists($doc->file_path))
            return $this->error('File not found', 404);

        return Storage::disk('public')->download($doc->file_path);
    }

    public function showApplication($id)
    {
        $app = Application::with('offer.company')
            ->where('id', $id)
            ->where('student_id', $this->student()->id)
            ->firstOrFail();

        return $this->success($app);
    }

    public function saveOffer($offer_id)
    {
        $s     = $this->student();
        $offer = Offer::findOrFail($offer_id);

        if (!$offer->is_active) return $this->error('Offer not active');

        if ($s->savedOffers()->where('offer_id', $offer->id)->exists())
            return $this->error('Already saved');

        $s->savedOffers()->attach($offer->id);

        return $this->success(null, 'Offer saved');
    }

    public function unsaveOffer($offer_id)
    {
        $this->student()->savedOffers()->detach($offer_id);
        return $this->success(null, 'Offer removed');
    }

    public function savedOffers()
    {
        return $this->success(
            $this->student()->savedOffers()
                ->with('company')
                ->orderByPivot('created_at', 'desc')
                ->paginate(10)
        );
    }

    public function stats()
    {
        $s         = $this->student();
        $total     = $s->applications()->count();
        $validated = Internship::where('student_id', $s->id)
            ->where('status', Internship::VALIDATED)->count();

        return $this->success([
            'total_applications' => $total,
            'accepted'           => $s->applications()
                ->where('status', Application::ACCEPTED_BY_COMPANY)->count(),
            'validated'          => $validated,
            'rejected'           => $s->applications()
                ->whereIn('status', [
                    Application::REJECTED_BY_COMPANY,
                    Application::REJECTED_BY_ADMIN,
                ])->count(),
            'success_rate' => $total > 0 ? round(($validated / $total) * 100) : 0,
        ]);
    }

    public function generateCertificate($id)
    {
        $s          = $this->student();
        $internship = Internship::where('id', $id)
            ->where('student_id', $s->id)
            ->where('status', Internship::VALIDATED)
            ->firstOrFail();

        $app = Application::where('student_id', $s->id)
            ->where('offer_id', $internship->offer_id)
            ->firstOrFail();

        $existing = Document::where('internship_id', $internship->id)
            ->where('type', 'certificate')->first();

        if ($existing) return $this->success($existing, 'Already exists');

        \DB::beginTransaction();
        try {
            $pdf  = Pdf::loadView('pdf.certificate', ['internship' => $internship]);
            $path = 'documents/' . uniqid() . '_certificate.pdf';
            Storage::disk('public')->put($path, $pdf->output());

            $doc = Document::create([
                'internship_id' => $internship->id,
                'file_path'     => $path,
                'type'          => 'certificate',
                'generated_at'  => now(),
            ]);

            \DB::commit();

            return $this->success($doc, 'Certificate generated');

        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->error('Failed to generate certificate');
        }
    }

    public function internshipDetails($id)
    {
        $offer = Offer::with('company', 'skills')->findOrFail($id);

        if (!$offer->isAvailable()) return $this->error('Offer not available');

        return $this->success($offer);
    }
    public function downloadCvPdf()
{
    $student = $this->student();

    if (!$student->hasDigitalCv()) {
        return $this->error('Please complete your Digital CV first');
    }

    $pdf = Pdf::loadView('pdf.student-cv', [
        'student' => $student,
        'user'    => auth()->user(),
    ]);

    return $pdf->download('CV_' . auth()->user()->name . '.pdf');
}
}