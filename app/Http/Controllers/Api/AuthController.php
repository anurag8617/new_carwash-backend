<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Vendor;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite; 

class AuthController extends Controller
{

    // --- Google Login: Step 1 (Redirect) ---
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // --- Google Login: Step 2 (Callback) ---
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Register new user
                $user = User::create([
                    'first_name' => $googleUser->user['given_name'] ?? '',
                    'last_name' => $googleUser->user['family_name'] ?? '',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'role' => 'client',
                    'password' => Hash::make(uniqid()), // Random password
                    'profile_photo' => $googleUser->getAvatar(),
                ]);
                \App\Models\Client::create(['user_id' => $user->id]);
            } else {
                // Link existing user
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Encode user data to pass to frontend
            $userData = base64_encode(json_encode($user));
            
            // Redirect to Frontend (Port 5173)
            return redirect("http://localhost:5173/auth/google/callback?token=$token&user=$userData");

        } catch (\Exception $e) {
            return redirect("http://localhost:5173/login?error=Google Login Failed");
        }
    }
    

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' checks for password_confirmation
            'role' => ['required', Rule::in(['client', 'vendor','staff', 'admin'])],
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'pincode' => 'nullable|string',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'role' => $validatedData['role'],
            'password' => Hash::make($validatedData['password']),
        ]);

        if ($user->role === 'client') {
            \App\Models\Client::create([
            'user_id' => $user->id,
            'address' => $request->address ?? null,
            'city' => $request->city ?? null,
            'pincode' => $request->pincode ?? null,
        ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.'
        ], 201);
    }

    /**
     * Log in the user and create a token.
     */
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validatedData)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $validatedData['email'])->first();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Successfully logged out']);
    }    
    
}