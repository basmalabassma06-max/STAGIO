<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CompanyController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/admin/login', [AdminController::class, 'login'])
    ->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION (PUBLIC)
|--------------------------------------------------------------------------
*/

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {

    $user = User::findOrFail($id);

    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    return redirect('http://localhost:5173/login?verified=1');

})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent'
        ]);
    })->middleware('throttle:6,1');

    /*
    |--------------------------------------------------------------------------
    | OFFERS
    |--------------------------------------------------------------------------
    */

    Route::get('/offers', [StudentController::class, 'offers']);
    Route::get('/offers/{id}', [OfferController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | STUDENT ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:student')->group(function () {

        Route::get('/student/dashboard', [StudentController::class, 'dashboard']);

        Route::get('/student/profile', [StudentController::class, 'profile']);
        Route::post('/student/profile', [StudentController::class, 'updateProfile']);

        Route::delete('/student/cv', [StudentController::class, 'clearDigitalCv']);
        Route::get('/student/cv/download', [StudentController::class, 'downloadCvPdf']);

        Route::post('/student/skills', [StudentController::class, 'addSkills']);
        Route::delete('/student/skills/{id}', [StudentController::class, 'removeSkill']);

        Route::get('/student/recommended-offers', [StudentController::class, 'advancedRecommended']);

        Route::post('/offers/{id}/save', [StudentController::class, 'saveOffer']);
        Route::delete('/offers/{id}/save', [StudentController::class, 'unsaveOffer']);
        Route::get('/student/saved-offers', [StudentController::class, 'savedOffers']);

        Route::post('/apply/{offer_id}', [StudentController::class, 'apply'])
            ->middleware('throttle:30,1');

        Route::get('/applications', [StudentController::class, 'myApplications']);
        Route::get('/applications/{id}', [StudentController::class, 'showApplication']);
        Route::put('/applications/{id}', [StudentController::class, 'updateApplication']);
        Route::delete('/applications/{id}', [StudentController::class, 'withdraw']);

        Route::post('/notifications/read-all', [StudentController::class, 'markAllAsRead']);
        Route::get('/notifications/unread-count', [StudentController::class, 'unreadCount']);
        Route::get('/notifications', [StudentController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [StudentController::class, 'markAsRead']);

        Route::post('/internships/{id}/document', [StudentController::class, 'generateDocument']);
        Route::post('/internships/{id}/certificate', [StudentController::class, 'generateCertificate']);
        Route::get('/documents/{id}/download', [StudentController::class, 'downloadDocument']);

        Route::get('/student/stats', [StudentController::class, 'stats']);
    });

    /*
    |--------------------------------------------------------------------------
    | COMPANY ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:company')->prefix('company')->group(function () {

        Route::get('/stats', [CompanyController::class, 'stats']);

        Route::get('/applications', [CompanyController::class, 'applications']);
        Route::get('/applications/{id}', [CompanyController::class, 'show']);
        Route::post('/applications/{id}/accept', [CompanyController::class, 'accept']);
        Route::post('/applications/{id}/reject', [CompanyController::class, 'reject']);
        Route::get('/applications/{id}/cv', [CompanyController::class, 'downloadStudentCv']);

        Route::get('/offers', [OfferController::class, 'myOffers']);
        Route::post('/offers', [OfferController::class, 'store']);
        Route::put('/offers/{id}', [OfferController::class, 'update']);
        Route::delete('/offers/{id}', [OfferController::class, 'destroy']);
        Route::post('/offers/{id}/toggle', [OfferController::class, 'toggle']);
        Route::get('/offers/{id}', [OfferController::class, 'showMine']);

        Route::get('/notifications', [CompanyController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [CompanyController::class, 'markAsRead']);

        Route::get('/profile', [CompanyController::class, 'profile']);
        Route::post('/profile', [CompanyController::class, 'updateProfile']);
        Route::delete('/logo', [CompanyController::class, 'deleteLogo']);
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->prefix('admin')->group(function () {

        Route::post('/logout', [AdminController::class, 'logout']);

        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users/pending', [AdminController::class, 'pendingUsers']);

        Route::get('/internships', [AdminController::class, 'getInternships']);
        Route::get('/internships/{id}', [AdminController::class, 'showInternship']);
        Route::post('/internships/{id}/validate', [AdminController::class, 'validateInternship']);
        Route::post('/internships/{id}/reject', [AdminController::class, 'rejectInternship']);
        Route::get('/internships/{id}/cv', [AdminController::class, 'downloadCv']);

        Route::post('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::post('/notifications/read-all', [AdminController::class, 'markAllRead']);
        Route::get('/notifications', [AdminController::class, 'getNotifications']);
        Route::post('/notifications/{id}/read', [AdminController::class, 'markAsRead']);

        Route::get('/files', [AdminController::class, 'listFiles']);
        Route::get('/files/{file}', [AdminController::class, 'downloadFile']);
        Route::delete('/files/{file}', [AdminController::class, 'deleteFile']);

        Route::get('/export/stats', [AdminController::class, 'exportStats']);
        Route::get('/export/internships', [AdminController::class, 'exportInternships']);

        Route::get('/logs', [AdminController::class, 'getLogs']);

        Route::get('/companies/pending', [AdminController::class, 'pendingCompanies']);
        Route::post('/companies/{id}/approve', [AdminController::class, 'approveCompany']);
        Route::post('/companies/{id}/reject', [AdminController::class, 'rejectCompany']);
        Route::get('/companies/{id}/agreement', [AdminController::class, 'downloadAgreement']);
    });
});