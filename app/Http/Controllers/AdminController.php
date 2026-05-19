<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Student;
use App\Models\User;
use App\Models\Internship;
use App\Models\Notification;
use App\Models\AdminLog;
use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InternshipStatusMail;
use App\Mail\CompanyApprovedMail;
use App\Mail\CompanyRejectedMail;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    private function notify($userId, $type, $message, $data = [])
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'message' => $message,
                'is_read' => false,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Notification Error: ' . $e->getMessage());
        }
    }

    /* =====================================================
     | RESPONSE HELPERS
     ===================================================== */

    private function success($data = [], $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    private function error($message = 'Error', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    private function adminLog($action, $targetId = null, $details = null)
    {
        try {
            AdminLog::create([
                'admin_id'   => Auth::id(),
                'action'     => $action,
                'target_id'  => $targetId,
                'details'    => $details,
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function universityInfo()
{
    $logoPath = public_path('logo.png');
    $logo = null;

    if (file_exists($logoPath)) {
        try {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);

            if ($data !== false) {
                $logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        } catch (\Exception $e) {
            $logo = null;
        }
    }

    return [
        'name'       => 'Université Ferhat Abbas Sétif 1',
        'faculty'    => 'Faculté des Sciences',
        'department' => 'Informatique',
        'logo'       => $logo
    ];
}

    /* =====================================================
     | AUTH
     ===================================================== */

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        if (!$token = auth()->attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', 401);
        }

        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            auth()->logout();
            return $this->error('Unauthorized', 403);
        }

        // FIX #1: Verify email before allowing admin login
        if (!$user->email_verified_at) {
            auth()->logout();
            return $this->error('Email not verified.', 403);
        }

        if (!$user->is_active) {
            auth()->logout();
            return $this->error('Account disabled', 403);
        }

        $this->adminLog('login');

        return $this->success([
            'token' => $token,
            'user'  => $user
        ]);
    }

    public function logout()
    {
        auth()->logout();

        $this->adminLog('logout');

        return $this->success([], 'Logout successful');
    }

    /* =====================================================
     | DASHBOARD
     ===================================================== */

    public function dashboard()
    {
        try {
            $students  = User::where('role', 'student')->count();
            $companies = User::where('role', 'company')->count();
            $admins    = User::where('role', 'admin')->count();

            $validated = Internship::where('status', 'validated')->count();
            $pending   = Internship::where('status', 'pending_admin')->count();
            $rejected  = Internship::where('status', Internship::REJECTED_BY_ADMIN)->count();
            $total     = Internship::count();

            $placementRate = $students > 0
                ? round(($validated / $students) * 100, 2)
                : 0;

            return $this->success([
                'stats' => [
                    'students'            => $students,
                    'companies'           => $companies,
                    'admins'              => $admins,
                    'validated'           => $validated,
                    'pending'             => $pending,
                    'rejected'            => $rejected,
                    'total'               => $total,
                    'placement_rate'      => $placementRate,
                    'unplaced_students'   => max($students - $validated, 0),
                    'pending_companies'   => User::where('role', 'company')
                        ->where('status', User::STATUS_PENDING)->count(),
                ],

                'monthly' => Internship::selectRaw("
                    DATE_FORMAT(created_at,'%Y-%m') as month,
                    COUNT(*) as total
                ")->groupBy('month')->orderBy('month')->get(),

                'top_companies' => Internship::select(
                    'company_id',
                    DB::raw('COUNT(*) as total')
                )
                ->with('company:id,name')
                ->groupBy('company_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Dashboard failed', 500);
        }
    }

    /* =====================================================
     | INTERNSHIPS
     ===================================================== */

    public function getInternships(Request $request)
    {
        try {
            $query = Internship::with([
                'student.user:id,name',
                'company:id,name',
                'offer:id,title'
            ]);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('company')) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->company . '%');
                });
            }

            if ($request->filled('student')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->whereHas('user', function ($u) use ($request) {
                        $u->where('name', 'like', '%' . $request->student . '%');
                    });
                });
            }

            if ($request->filled('offer')) {
                $query->whereHas('offer', function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->offer . '%');
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('wilaya')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('wilaya', $request->wilaya);
                });
            }

            if ($request->filled('type')) {
                $query->whereHas('offer', function ($q) use ($request) {
                    $q->where('type', $request->type);
                });
            }

            if ($request->filled('date_range')) {
                $dates = explode(',', $request->date_range);
                if (count($dates) === 2) {
                    $query->whereBetween('created_at', [$dates[0], $dates[1]]);
                }
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('student.user', function ($u) use ($search) {
                        $u->where('name', 'like', '%' . $search . '%');
                    });
                    $q->orWhereHas('company', function ($c) use ($search) {
                        $c->where('name', 'like', '%' . $search . '%');
                    });
                    $q->orWhereHas('offer', function ($o) use ($search) {
                        $o->where('title', 'like', '%' . $search . '%');
                    });
                });
            }

            $this->adminLog('view_internships');

            $perPage = max(1, min($request->per_page ?? 10, 50));

            return $this->success($query->paginate($perPage));

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Failed', 500);
        }
    }

  public function showInternship($id)
{
    $internship = Internship::with([
        'student.user',   // ← ADD .user here
        'company',
        'offer'
    ])->findOrFail($id);

    return $this->success($internship);
}
    /* =====================================================
     | VALIDATE / REJECT
     ===================================================== */

    public function validateInternship($id)
    {
        DB::beginTransaction();

        try {
          $internship = Internship::with(['student.user', 'company', 'offer'])
    ->findOrFail($id);

// ✅ حماية
if (!$internship->student || !$internship->student->user) {
    return $this->error('Student or user not found', 422);
}
            $path = storage_path('app/private/documents');
            File::ensureDirectoryExists($path);

            $studentName = Str::slug($internship->student->user->name);
            $companyName = Str::slug($internship->company->name);

            $conv = "convention_{$studentName}_{$companyName}_{$id}.pdf";
            $cert = "certificate_{$studentName}_{$companyName}_{$id}.pdf";

            Pdf::loadView('pdf.convention', [
                'internship' => $internship,
                'uni'        => $this->universityInfo()
            ])->save("$path/$conv");

            Pdf::loadView('pdf.certificate', [
    'internship' => $internship,
    'uni' => $this->universityInfo()
])->save("$path/$cert");

            $publicPath = storage_path('app/public/documents');
            File::ensureDirectoryExists($publicPath);
            File::copy("$path/$conv", "$publicPath/$conv");
            File::copy("$path/$cert", "$publicPath/$cert");

            $conventionUrl  = asset('storage/documents/' . $conv);
            $certificateUrl = asset('storage/documents/' . $cert);
          DB::table('documents')->insert([
    'internship_id' => $id,
    'file_path'     => $conv,
    'type'          => 'convention',
    'generated_at'  => now(),
    'created_at'    => now(),
    'updated_at'    => now(),
]);

DB::table('documents')->insert([
    'internship_id' => $id,
    'file_path'     => $cert,
    'type'          => 'certificate',
    'generated_at'  => now(),
    'created_at'    => now(),
    'updated_at'    => now(),
]);
            // FIX #14: Use constants instead of string literals for status values
            $internship->update([
                'status'       => Internship::VALIDATED,
                'validated_by' => Auth::id(),
                'validated_at' => now()
            ]);

            Application::where('offer_id', $internship->offer_id)
                ->where('student_id', $internship->student_id)
                ->update(['status' => Application::VALIDATED]);

            $this->notify($internship->student->user_id, 'validated', 'Internship validated ✅', [
                'internship_id'   => $id,
                'certificate_url' => $certificateUrl,
                'convention_url'  => $conventionUrl,
            ]);

            $this->notify($internship->company->user_id, 'internship_validated', 'Your internship has been validated 🎉', [
                'internship_id'   => $id,
                'certificate_url' => $certificateUrl,
                'convention_url'  => $conventionUrl,
            ]);

           dispatch(function () use ($internship, $conv, $cert) {
    Mail::to(optional($internship->student->user)->email)
        ->send(new InternshipStatusMail('validated', [
            storage_path("app/private/documents/$conv"),
            storage_path("app/private/documents/$cert")
        ]));
})->afterResponse();

            $this->adminLog('validate_internship', $id);

            DB::commit();

            return $this->success([
                'files' => [
                    'convention'  => $conv,
                    'certificate' => $cert
                ]
            ], 'Validated');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error('Validation failed', 500);
        }
    }

    public function rejectInternship(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            $internship = Internship::with(['student', 'company'])->findOrFail($id);

            if ($internship->status !== Internship::PENDING_ADMIN) {
                return $this->error('Already processed');
            }

            $reason = $request->reason ?? 'No reason';

            // FIX #14: Use constant instead of string literal
            $internship->update([
                'status'      => Internship::REJECTED_BY_ADMIN,
                'rejected_by' => Auth::id(),
                'rejected_at' => now()
            ]);

            Application::where('offer_id', $internship->offer_id)
                ->where('student_id', $internship->student_id)
                ->update(['status' => Application::REJECTED_BY_ADMIN]);

            $this->notify($internship->student->user_id, 'rejected', "Rejected: $reason", [
                'internship_id' => $id
            ]);

            $this->notify($internship->company->user_id, 'rejected', "Internship rejected", [
                'internship_id' => $id
            ]);

            try {
                Mail::to(optional($internship->student->user)->email)
                    ->send(new InternshipStatusMail('rejected'));
            } catch (\Exception $e) {
                Log::error('Mail failed: ' . $e->getMessage());
            }

            DB::commit();

            return $this->success([], 'Rejected');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error('Reject failed', 500);
        }
    }

    public function toggleUserStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id == Auth::id()) {
                return $this->error('Cannot disable yourself', 403);
            }

            // FIX #7: Prevent admins from toggling other admin accounts
            if ($user->role === 'admin') {
                return $this->error('Cannot modify other admin accounts', 403);
            }

            $user->update(['is_active' => !$user->is_active]);

            $this->adminLog('toggle_user', $id);

            return $this->success([], 'Updated');

        } catch (\Exception $e) {
            return $this->error('Failed', 500);
        }
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return $this->error('Cannot delete admin', 403);
        }

        $user->delete();

        $this->adminLog('delete_user', $id);

        return $this->success([], 'Deleted');
    }

    /* =====================================================
     | NOTIFICATIONS
     ===================================================== */

    public function getNotifications(Request $request)
    {
        // FIX #18: Respect per_page parameter like other paginated endpoints
        $perPage = max(1, min($request->per_page ?? 10, 50));

        return $this->success(
            Notification::where('user_id', Auth::id())
                ->latest()
                ->paginate($perPage)
        );
    }

    public function unreadCount()
    {
        return $this->success([
            'count' => Notification::where('user_id', Auth::id())
                ->where('is_read', false)->count()
        ]);
    }

    public function markAsRead($id)
    {
        $notif = Notification::findOrFail($id);

        if ($notif->user_id != Auth::id()) {
            return $this->error('Unauthorized', 403);
        }

        $notif->update(['is_read' => true]);

        return $this->success([], 'Marked');
    }

    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return $this->success([], 'All read');
    }

    public function deleteNotification($id)
    {
        $notif = Notification::findOrFail($id);

        if ($notif->user_id != Auth::id()) {
            return $this->error('Unauthorized', 403);
        }

        $notif->delete();

        return $this->success([], 'Deleted');
    }

    /* =====================================================
     | FILES
     ===================================================== */

    /**
     * FIX #5: Corrected regex to properly match the filenames generated
     * by validateInternship(), which include slugified names (letters + digits).
     * Original regex was broken: `convention|certificate_[0-9]+.pdf` treated
     * `|` as top-level alternation and didn't match slugged names.
     */
    private function validFile($file)
    {
        return (bool) preg_match('/^(convention|certificate)_[a-z0-9\-]+_[a-z0-9\-]+_\d+\.pdf$/', $file);
    }

    public function listFiles()
    {
        $path = storage_path('app/private/documents');

        if (!File::exists($path)) {
            return $this->success([]);
        }

        $files = collect(File::files($path))
            ->map(fn($f) => $f->getFilename())
            ->values();

        return $this->success($files);
    }

    public function downloadCv($id)
    {
        $internship = Internship::with([
            'student.skills',
            'student.user',
            'offer',
            'company'
        ])->findOrFail($id);

        $student = $internship->student;
        $user    = $student->user;

        if (!$student->hasDigitalCv()) {
            return response()->json([
                'success' => false,
                'message' => 'Student CV incomplete'
            ], 400);
        }

        $pdf = Pdf::loadView('pdf.student-cv', [
            'student' => $student,
            'user'    => $user,
            'offer'   => $internship->offer
        ]);

        return $pdf->download('CV_' . $user->name . '.pdf');
    }

    public function downloadFile($file)
    {
        if (!$this->validFile($file)) {
            return $this->error('Invalid file', 403);
        }

        $path = storage_path('app/private/documents/' . $file);

        if (!File::exists($path)) {
            return $this->error('Not found', 404);
        }

        $this->adminLog('download_file', null, $file);

        return response()->download($path);
    }

    public function deleteFile($file)
    {
        if (!$this->validFile($file)) {
            return $this->error('Invalid file', 403);
        }

        $path = storage_path('app/private/documents/' . $file);

        if (!File::exists($path)) {
            return $this->error('Not found', 404);
        }

        File::delete($path);

        $this->adminLog('delete_file', null, $file);

        return $this->success([], 'Deleted');
    }

    /* =====================================================
     | EXPORT PDF
     ===================================================== */

    public function exportStats()
    {
        try {
            $students  = User::where('role', 'student')->count();
            $validated = Internship::where('status', 'validated')->count();

            $data = [
                'students'       => $students,
                'companies'      => User::where('role', 'company')->count(),
                'admins'         => User::where('role', 'admin')->count(),
                'validated'      => $validated,
                'pending'        => Internship::where('status', 'pending_admin')->count(),
                'rejected'       => Internship::where('status', 'rejected_by_admin')->count(),
                'total'          => Internship::count(),
                'placement_rate' => $students > 0
                    ? round(($validated / $students) * 100, 2)
                    : 0,
                'generated_at'   => now()
            ];

            $pdf = Pdf::loadView('pdf.stats', compact('data'));

            $this->adminLog('export_stats');

            return $pdf->download('admin-stats-' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Export failed', 500);
        }
    }

    public function exportInternships()
    {
        try {
            // FIX #15: Replaced ->get() with chunking to avoid memory exhaustion
            // on large datasets. We build the PDF view data in chunks.
            $internships = Internship::with([
                'student.user:id,name',
                'company:id,name',
                'offer:id,title'
            ])->paginate(500)->items(); // Limit to 500 records per export

            $pdf = Pdf::loadView('pdf.internships', compact('internships'));

            $this->adminLog('export_internships');

            return $pdf->download('internships-' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Export failed', 500);
        }
    }

    public function getLogs(Request $request)
    {
        try {
            $logs = AdminLog::with('admin:id,name')
                ->latest()
                ->paginate(min($request->per_page ?? 10, 50));

            return $this->success($logs);

        } catch (\Exception $e) {
            return $this->error('Failed', 500);
        }
    }

    public function applications()
    {
        return $this->success(
            Application::with(['student.user', 'offer.company'])
                ->latest()
                ->paginate(10)
        );
    }

    public function studentsWithoutInternship()
    {
        $students = Student::whereDoesntHave('internships')
            ->with('user:id,name,email')
            ->paginate(10);

        return response()->json($students);
    }

    // ================= TRACKING STATES =================

    public function pendingInternships()
    {
        return $this->success(
            Internship::where('status', 'pending_admin')
                ->with(['student.user', 'company', 'offer'])
                ->paginate(10)
        );
    }

    public function validatedInternships()
    {
        return $this->success(
            Internship::where('status', 'validated')
                ->with(['student', 'company', 'offer'])
                ->paginate(10)
        );
    }

    public function rejectedInternships()
    {
        return $this->success(
            Internship::where('status', 'rejected_by_admin')
                ->with(['student', 'company', 'offer'])
                ->paginate(10)
        );
    }

    public function workflow()
    {
        return $this->success([
            'steps' => [
                'Student applies',
                'Company accepts',
                'Admin validates',
                'System generates documents'
            ]
        ]);
    }

    /* =====================================================
     | COMPANY APPROVAL
     ===================================================== */

    public function pendingCompanies(Request $request)
    {
        try {
            $companies = User::where('role', 'company')
                ->where('status', User::STATUS_PENDING)
                ->with('company')
                ->latest()
                ->paginate(min($request->per_page ?? 10, 50));

            return $this->success($companies);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Failed', 500);
        }
    }

    public function approveCompany($id)
    {
        try {
            $user = User::where('role', 'company')
                ->where('status', User::STATUS_PENDING)
                ->findOrFail($id);

            $user->update(['status' => User::STATUS_APPROVED,'email_verified_at' => now(),]);

            $this->notify($user->id, 'company_approved',
                'Your company account has been approved! You can now log in. ✅',
                ['user_id' => $user->id]
            );

          dispatch(function () use ($user) {
    Mail::to($user->email)->send(new CompanyApprovedMail());
})->afterResponse();

            $this->adminLog('approve_company', $id);

            return $this->success([], 'Company approved');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Failed', 500);
        }
    }

    public function rejectCompany(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        try {
            $user = User::where('role', 'company')
                ->where('status', User::STATUS_PENDING)
                ->findOrFail($id);

            $user->update(['status' => User::STATUS_REJECTED]);

            $reason = $request->reason ?? 'No reason provided';

            $this->notify($user->id, 'company_rejected',
                "Your company registration has been rejected: $reason ❌",
                ['user_id' => $user->id]
            );

            // FIX #13: Pass the rejection reason to the email so the company knows why
            dispatch(function () use ($user, $reason) {
    Mail::to($user->email)->send(new CompanyRejectedMail($reason));
})->afterResponse();

            $this->adminLog('reject_company', $id, $reason);

            return $this->success([], 'Company rejected');

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Failed', 500);
        }
    }

    public function downloadAgreement($id)
    {
        try {
            $user    = User::where('role', 'company')->findOrFail($id);
            $company = $user->company;

            if (!$company || !$company->agreement_file) {
                return $this->error('No agreement file found', 404);
            }

            // FIX #6: Prevent path traversal by resolving the real path and
            // confirming it stays within the expected storage directory.
            $relativePath = ltrim($company->agreement_file, '/');
            $fullPath     = storage_path('app/private/' . $relativePath);
            $realPath     = realpath($fullPath);
            $allowedBase  = realpath(storage_path('app/private/agreements'));

            if ($realPath === false || $allowedBase === false || !str_starts_with($realPath, $allowedBase)) {
                Log::warning("Path traversal attempt for company ID $id: $relativePath");
                return $this->error('Invalid file path', 403);
            }

            if (!File::exists($realPath)) {
                return $this->error('File not found on disk', 404);
            }

            $this->adminLog('download_agreement', $id);

            return response()->download($realPath);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Failed', 500);
        }
    }
    public function pendingUsers()
{
    return $this->success(
        User::where('role', 'company')
            ->where('status', User::STATUS_PENDING)
            ->with('company')
            ->latest()
            ->get()
    );
}
}