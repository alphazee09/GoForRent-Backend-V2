<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["login", "register", "verifyOtp", "resendOtp"]]);
    }

    /**
     * User registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "full_name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users",
            "phone_number" => "required|string|max:50|unique:users",
            "password" => "required|string|min:8|confirmed",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $user = User::create([
            "full_name" => $request->full_name,
            "email" => $request->email,
            "phone_number" => $request->phone_number,
            "password" => Hash::make($request->password),
            "otp" => $otp,
            "otp_expires_at" => $otp_expires_at,
        ]);

        // Send OTP email (actual email sending setup is needed)
        try {
            // Mail::to($user->email)->send(new OtpMail($otp));
            // For now, we will just return the OTP in the response for testing, as email setup is out of scope for this step
        } catch (\Exception $e) {
            // Log email sending failure
        }

        return response()->json([
            "message" => "User successfully registered. Please verify your email with the OTP sent.",
            "user" => $user,
            "otp_for_testing" => $otp // Remove this in production after email is working
        ], 201);
    }

    /**
     * User login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|string|min:8",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only("email", "password");

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(["error" => "Invalid credentials"], 401);
            }
        } catch (JWTException $e) {
            return response()->json(["error" => "Could not create token"], 500);
        }
        
        $user = Auth::user();

        if (!$user->email_verified_at) {
            // Resend OTP if email not verified
            $this->sendOtp($user);
            JWTAuth::invalidate($token); // Invalidate current token as email is not verified
            return response()->json(["message" => "Email not verified. OTP has been resent to your email address.", "otp_for_testing" => $user->otp], 403);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email|exists:users,email",
            "otp" => "required|string|digits:6",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where("email", $request->email)->first();

        if (!$user || $user->otp !== $request->otp || Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(["error" => "Invalid or expired OTP."], 400);
        }

        $user->email_verified_at = Carbon::now();
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();
        
        // Attempt to log in the user and generate a token after successful OTP verification
        try {
            if (! $token = JWTAuth::fromUser($user)) {
                 return response()->json(["error" => "Could not create token after OTP verification"], 500);
            }
        } catch (JWTException $e) {
            return response()->json(["error" => "Could not create token after OTP verification"], 500);
        }

        return $this->respondWithToken($token, "Email successfully verified. You are now logged in.");
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email|exists:users,email",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where("email", $request->email)->first();

        if ($user->email_verified_at) {
            return response()->json(["message" => "Email is already verified."], 400);
        }

        $this->sendOtp($user);

        return response()->json(["message" => "OTP has been resent to your email address.", "otp_for_testing" => $user->otp]);
    }

    protected function sendOtp(User $user)
    {
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $user->otp = $otp;
        $user->otp_expires_at = $otp_expires_at;
        $user->save();

        // Send OTP email (actual email sending setup is needed)
        try {
            // Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Log email sending failure
        }
    }

    /**
     * Get the authenticated User.
     */
    public function userProfile()
    {
        return response()->json(Auth::user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(["message" => "Successfully logged out"]);
        } catch (JWTException $exception) {
            return response()->json(["error" => "Sorry, the user cannot be logged out"], 500);
        }
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        try {
            return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
        } catch (JWTException $exception) {
            return response()->json(["error" => "Token refresh failed"], 500);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token, $message = "Login successful")
    {
        return response()->json([
            "message" => $message,
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => JWTAuth::factory()->getTTL() * 60, // Get TTL in seconds
            "user" => Auth::user()
        ]);
    }

    // Placeholder for updateProfile - to be implemented fully
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            "full_name" => "sometimes|required|string|max:255",
            "phone_number" => "sometimes|required|string|max:50|unique:users,phone_number," . $user->id,
            // Add other updatable fields here
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->fill($request->only(["full_name", "phone_number"])); // Add other fields
        $user->save();

        return response()->json(["message" => "Profile updated successfully", "user" => $user]);
    }

    // Placeholder for changePassword - to be implemented fully
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "current_password" => "required|string",
            "new_password" => "required|string|min:8|confirmed",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(["error" => "Current password does not match"], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(["message" => "Password changed successfully"]);
    }
}

