<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ================= BLOCKED DOMAINS =================
    private array $blockedDomains = [
        'mailinator.com', 'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        '10minutemail.com', '10minutemail.net', 'guerrillamail.com',
        'guerrillamail.net', 'guerrillamail.org', 'guerrillamail.de',
        'sharklasers.com', 'guerrillamailblock.com', 'grr.la', 'spam4.me',
        'yopmail.com', 'yopmail.fr', 'cool.fr.nf', 'jetable.fr.nf',
        'nospam.ze.tc', 'nomail.xl.cx', 'mega.zik.dj', 'speed.1s.fr',
        'courriel.fr.nf', 'moncourrier.fr.nf', 'monemail.fr.nf',
        'monmail.fr.nf', 'dispostable.com', 'mailnesia.com',
        'mailnull.com',  // FIX #16: removed duplicate entry
        'spamgourmet.com', 'spamgourmet.net', 'spamgourmet.org',
        'trashmail.at', 'trashmail.io', 'trashmail.me', 'trashmail.net',
        'trashmail.xyz', 'fakeinbox.com', 'throwam.com', 'throwam.net',
        'maildrop.cc', 'spamfree24.org', 'spamfree24.de', 'spamfree24.eu',
        'spamfree24.net', 'spamfree24.info', 'spamfree.eu', 'inoutmail.de',
        'inoutmail.eu', 'inoutmail.info', 'inoutmail.net', 'getnada.com',
        'discard.email', 'spamhide.com', 'owlpic.com', 'drdrb.net',
        'drdrb.com', 'mailexpire.com', 'spamex.com',
    ];

    // ================= REGISTER =================
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email:rfc,dns|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:student,company',
            // FIX #4: Only accept PDF for agreement (formal document)
            'agreement_file' => 'required_if:role,company|file|mimes:pdf|max:4096',
        ]);

        // 🚫 BLOCK FAKE / TEMP EMAILS
        $domain = strtolower(substr(strrchr($request->email, '@'), 1));

        if (in_array($domain, $this->blockedDomains)) {
            return response()->json([
                'message' => 'Temporary or fake email addresses are not allowed.'
            ], 422);
        }

        // Companies start as 'pending', everyone else is 'approved'
        $status = $request->role === 'company'
            ? User::STATUS_PENDING
            : User::STATUS_APPROVED;

        // ================= CREATE USER =================
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => strtolower($request->role),
            'status'   => $status,
        ]);

        // 📩 SEND EMAIL VERIFICATION
        // حطي هذا
dispatch(function () use ($user) {
    event(new \Illuminate\Auth\Events\Registered($user));
})->afterResponse();

        // ================= PROFILE =================
        if ($user->role === 'student') {
            Student::create([
                'user_id'    => $user->id,
                'university' => '',
                'wilaya'     => '',
            ]);
        }

        if ($user->role === 'company') {
            $agreementPath = null;

            if ($request->hasFile('agreement_file')) {
                $agreementPath = $request->file('agreement_file')
                    ->store('agreements', 'local');
            }

            Company::create([
                'user_id'        => $user->id,
                'name'           => $request->name,
                'description'    => '',
                'location'       => '',
                'agreement_file' => $agreementPath,
            ]);
        }

        $message = $user->role === 'company'
            ? 'Registration successful. Your account is under review. You will be notified once approved.'
            : 'Registered successfully. Please verify your email.';

        // FIX #10: Never return a token on registration. User must verify email first.
        return response()->json([
            'message' => $message,
        ], 201);
    }

    // ================= LOGIN =================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email:rfc,dns',
            'password' => 'required',
        ]);

        // FIX #2: Check user existence and status BEFORE issuing a JWT token.
        // This prevents issuing tokens that must immediately be invalidated.
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Email or password incorrect'
            ], 401);
        }

        // 🚫 BLOCK UNVERIFIED USERS
        if (!$user->email_verified_at) {
            return response()->json([
                'error' => 'Please verify your email first.'
            ], 403);
        }

        // FIX #11: Check status for ALL roles, not just company.
        // This ensures no user with a non-approved status can log in.
        if ($user->status === User::STATUS_PENDING) {
            return response()->json([
                'error'   => 'pending',
                'message' => 'Your account is under review. Please wait for admin approval.'
            ], 403);
        }

        if ($user->status === User::STATUS_REJECTED) {
            return response()->json([
                'error'   => 'rejected',
                'message' => 'Your registration has been rejected. Please contact support.'
            ], 403);
        }

        // 🚫 BLOCK DISABLED ACCOUNTS
        if (!$user->is_active) {
            return response()->json([
                'error' => 'Your account has been disabled.'
            ], 403);
        }

        // ✅ All checks passed — now issue the token
        $token = auth()->login($user);

        $user->role = strtolower($user->role);

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    // ================= PROFILE =================
    public function me()
    {
        // FIX #17: Eager-load relations so the frontend gets full profile data
        return response()->json(auth()->user()->load('student', 'company'));
    }

    // ================= LOGOUT =================
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // ================= REFRESH =================
    public function refresh()
    {
        return response()->json([
            'token' => auth()->refresh()
        ]);
    }
}