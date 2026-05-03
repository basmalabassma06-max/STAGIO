<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Notification;
use App\Models\Internship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
class CompanyController extends Controller
{
    // 📥 Get company
    private function getCompany()
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'company' || !$user->company) {
            abort(403, 'Company not found');
        }

        return $user->company;
    }

    // 🔐 Get application safely (NEW 🔥)
   private function getApplication($id, $company)
{
    return Application::whereHas('offer', function ($q) use ($company) {
        $q->where('company_id', $company->id);
    })
    ->with([
        'student:id,id,user_id,university,wilaya,bio,phone,education,experience,projects,cv_summary,cv_languages,linkedin,portfolio,github_link',
        'student.skills:id,name',
        'student.user:id,name,email',
        'offer'
    ])
    ->findOrFail($id);
}

    // 🟢 List Applications (محسنة 🔥)
 public function applications(Request $request)
{
    $company = $this->getCompany();

    $query = Application::whereHas('offer', function ($q) use ($company) {
        $q->where('company_id', $company->id);
    })
    ->with([
        'student.user:id,name,email',
        'offer:id,title'
    ]);

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('search')) {
        $query->whereHas('student.user', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
        });
    }

    if ($request->filled('date')) {
        $query->whereDate('created_at', $request->date);
    }

    return response()->json([
        'success' => true,
        'data' => $query->latest()->paginate($request->per_page ?? 10)
    ]);
}

    // 🔎 Show Application
    public function show($id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['success' => false], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getApplication($id, $company)
        ]);
    }
 public function downloadStudentCv($id)
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json([
            'success' => false
        ], 403);
    }

    $application = Application::whereHas('offer', function ($q) use ($company) {
        $q->where('company_id', $company->id);
    })
    ->with([
        'student.skills',
        'student.user',
        'offer.company'
    ])
    ->findOrFail($id);

    $student = $application->student;
    $user    = $student->user;

    if (!$student->hasDigitalCv()) {
        return response()->json([
            'success' => false,
            'message' => 'Student CV not completed'
        ], 400);
    }

    $pdf = Pdf::loadView('pdf.student-cv', [
        'student' => $student,
        'user'    => $user,
        'offer'   => $application->offer
    ]);

    return $pdf->download('CV_' . $user->name . '.pdf');
}
    // ✅ Accept Application (محسنة 🔥)
  public function accept($id)
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['success' => false], 403);
    }

    $app = $this->getApplication($id, $company);

    if ($app->status !== Application::PENDING) {
        return response()->json([
            'success' => false,
            'message' => 'Already processed'
        ], 400);
    }

    DB::beginTransaction();

    try {

        // ✅ 1. update status
        $app->update([
    'status' => Application::ACCEPTED_BY_COMPANY
]);

        // ✅ 2. create internship 🔥🔥🔥
  $internship = Internship::updateOrCreate(
    [
        'student_id' => $app->student_id,
        'offer_id'   => $app->offer_id,
    ],
    [
        'company_id' => $app->offer->company_id,
        'status'     => Internship::PENDING_ADMIN
    ]
);

$this->notifyAdmins(
    'pending_validation',
    'New internship needs validation',
    [
        'internship_id' => $internship->id,
        'student_id' => $app->student_id,
        'offer_id' => $app->offer_id
    ]
);
// 🔥 force status
$internship->update([
    'status' => Internship::PENDING_ADMIN
]);

        // ✅ 3. reject others + notify
        $others = Application::where('offer_id', $app->offer_id)
            ->where('id', '!=', $app->id)
            ->where('status', Application::PENDING)
            ->get();

        foreach ($others as $other) {
            $other->update([
                'status' => Application::REJECTED_BY_COMPANY
            ]);

            Notification::create([
                'user_id' => $other->student->user_id,
                'type' => 'application_rejected',
                'message' => 'Your application was rejected ❌',
                'is_read' => false,
                'data' => [
                    'application_id' => $other->id
                ]
            ]);
        }

        // ✅ notify accepted student
        Notification::create([
            'user_id' => $app->student->user_id,
            'type' => 'application_accepted',
            'message' => 'Your application was accepted 🎉',
            'is_read' => false,
            'data' => [
                'application_id' => $app->id
            ]
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Application accepted',
            'data' => [
                'application' => $app,
                'internship' => $internship
            ]
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Accept failed', [
            'error' => $e->getMessage(),
            'application_id' => $id
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error processing request'
        ], 500);
    }
}

    // ❌ Reject Application (محسنة 🔥)
    public function reject($id)
    {
        $company = $this->getCompany();

        if (!$company) {
            return response()->json(['success' => false], 403);
        }

        $app = $this->getApplication($id, $company);

        if ($app->status !== Application::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Already processed'
            ], 400);
        }

        $app->update([
            'status' => Application::REJECTED_BY_COMPANY
        ]);

        Notification::create([
            'user_id' => $app->student->user_id,
            'type' => 'application_status',
            'message' => 'Application rejected ❌',
            'data' => [
                'application_id' => $app->id,
                'offer_id' => $app->offer_id
            ],
            'is_read' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application rejected',
            'data' => $app
        ]);
    }

    // 📊 Stats (محسنة 🔥)
public function stats()
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['success'=>false],403);
    }

    $total = Application::whereHas('offer', fn($q) =>
        $q->where('company_id',$company->id)
    )->count();

    $accepted = Application::whereHas('offer', fn($q) =>
        $q->where('company_id',$company->id)
    )->where('status', Application::ACCEPTED_BY_COMPANY)->count();

    $validated = Internship::where('company_id',$company->id)
        ->where('status', Internship::VALIDATED)
        ->count();

    return response()->json([
        'success'=>true,
        'data'=>[
            'total'=>$total,
            'accepted'=>$accepted,
            'validated'=>$validated
        ]
    ]);
}
public function notifications()
{
    return response()->json([
        'success' => true,
        'data' => Notification::where('user_id', auth()->id())
            ->orderByRaw('is_read asc, created_at desc')
            ->paginate(10)
    ]);
}
public function markAsRead($id)
{
    Notification::where('id', $id)
        ->where('user_id', auth()->id())
        ->update(['is_read' => true]);

    return response()->json([
        'success' => true,
        'message' => 'Marked as read'
    ]);
}

// ✅ SHOW PROFILE
public function profile()
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['success' => false], 403);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $company->id,
            'name' => $company->name,
            'description' => $company->description,
            'location' => $company->location,
            'logo_url' => $company->logo 
                ? asset('storage/' . $company->logo) 
                : null
        ]
    ]);
}
public function updateProfile(Request $request)
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['success' => false], 403);
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'location' => 'required|string|max:100',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    ]);

    // ✅ upload logo
    if ($request->hasFile('logo')) {

        // delete old logo
        if ($company->logo && Storage::disk('public')->exists($company->logo)) {
            Storage::disk('public')->delete($company->logo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $company->logo = $path;
    }

    $company->update([
        'name' => $request->name,
        'description' => $request->description,
        'location' => $request->location
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated',
        'data' => [
            'name' => $company->name,
            'description' => $company->description,
            'location' => $company->location,
            'logo_url' => $company->logo 
                ? asset('storage/' . $company->logo) 
                : null
        ]
    ]);
}
public function deleteLogo()
{
    $company = $this->getCompany();

    if (!$company) {
        return response()->json(['success' => false], 403);
    }

    if (!$company->logo) {
        return response()->json([
            'success' => false,
            'message' => 'No logo to delete'
        ]);
    }

    if (Storage::disk('public')->exists($company->logo)) {
        Storage::disk('public')->delete($company->logo);
    }

    $company->update(['logo' => null]);

    return response()->json([
        'success' => true,
        'message' => 'Logo deleted'
    ]);
}
private function notifyAdmins($type, $message, $data = [])
{
    Log::info('NOTIFY ADMINS CALLED');

    $admins = \App\Models\User::where('role', 'admin')->get();

    Log::info('ADMINS COUNT: ' . $admins->count());

    foreach ($admins as $admin) {
        Notification::create([
            'user_id' => $admin->id,
            'type'    => $type,
            'message' => $message,
            'is_read' => false,
            'data'    => $data
        ]);
    }
}}