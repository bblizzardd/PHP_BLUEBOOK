<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Gửi email xác minh
        $user->notify(new VerifyEmailNotification());

        \Illuminate\Support\Facades\Log::info('User info:', $user->toArray());

        Auth::login($user);

        /** @var \App\Models\User $user */
        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng xác minh email của bạn.',
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'boolean'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        $user = Auth::user();

        /** @var \App\Models\User $user */
        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'user' => $user,
            'email_verified' => $user->hasVerifiedEmail(),
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the request...
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();

        return response()->json(['message' => 'Đã đăng xuất thành công.']);
    }

    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification())
        )) {
            return response()->json(['message' => 'Link xác minh không hợp lệ.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã được xác minh trước đó.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email đã được xác minh thành công!']);
    }

    public function verifyEmailWeb(Request $request)
    {
        try {
            $user = User::findOrFail($request->route('id'));

            if (!hash_equals(
                (string) $request->route('hash'),
                sha1($user->getEmailForVerification())
            )) {
                // Invalid hash - redirect to login with error
                return redirect()->route('login')
                    ->with('error', 'Link xác minh không hợp lệ hoặc đã hết hạn.');
            }

            if ($user->hasVerifiedEmail()) {
                // Already verified - login and go to dashboard
                Auth::login($user, remember: true);
                return redirect()->route('dashboard')
                    ->with('info', 'Email đã được xác minh rồi.');
            }

            // Mark email as verified
            $user->markEmailAsVerified();

            // Login the user
            Auth::login($user, remember: true);

            \Illuminate\Support\Facades\Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Email đã được xác minh thành công! Chào mừng bạn.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Email verification error', [
                'error' => $e->getMessage(),
                'id' => $request->route('id'),
            ]);

            return redirect()->route('login')
                ->with('error', 'Đã xảy ra lỗi khi xác minh email. Vui lòng thử lại.');
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã được xác minh rồi.']);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Email xác minh đã được gửi lại.']);
    }

    public function registerWeb(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('RegisterWeb called', $request->all());
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            \Illuminate\Support\Facades\Log::info('User created', $user->toArray());

            // Gửi email xác minh
            $user->notify(new VerifyEmailNotification());

            \Illuminate\Support\Facades\Log::info('User registered:', $user->toArray());

            Auth::login($user);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Đăng ký thành công! Vui lòng xác minh email để tiếp tục.',
                    'user' => $user,
                    'token' => $user->createToken('api-token')->plainTextToken,
                    'redirect' => route('verify.email.notice')
                ], 201);
            }

            return redirect()->route('verify.email.notice')
                ->with('warning', 'Đăng ký thành công! Vui lòng xác minh email để tiếp tục.');
        } catch (\Exception $e) {
            $message = $e->getMessage();

            \Illuminate\Support\Facades\Log::error('Register error details:', [
                'error' => $message,
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message
                ], 422);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => $message]);
        }
    }

    public function loginWeb(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
                'remember' => 'boolean'
            ]);

            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            if (!Auth::attempt($credentials, $remember)) {
                throw ValidationException::withMessages([
                    'email' => ['Thông tin đăng nhập không chính xác.'],
                ]);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                // Return JSON for AJAX requests or redirect for form submissions
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Email của bạn chưa được xác minh.',
                        'redirect' => route('verify.email.notice')
                    ], 403);
                }

                return redirect()->route('verify.email.notice')
                    ->with('warning', 'Email của bạn chưa được xác minh.');
            }

            // Return JSON response with token for successful login
            /** @var \App\Models\User $user */
            return response()->json([
                'message' => 'Đăng nhập thành công.',
                'user' => $user,
                'email_verified' => $user->hasVerifiedEmail(),
                'token' => $user->createToken('api-token')->plainTextToken,
            ], 200);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e instanceof ValidationException) {
                $message = $e->validator->errors()->first();
            }

            \Illuminate\Support\Facades\Log::error('Login error:', [
                'error' => $message,
                'email' => $request->email ?? null,
            ]);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message
                ], 422);
            }

            return redirect()->back()
                ->withInput($request->except('password'))
                ->withErrors(['error' => $message]);
        }
    }
}
