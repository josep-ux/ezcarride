<?php

namespace App\Http\Controllers\Api;

use App\Events\UserCreated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password'])
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;
          // Trigger real-time alert to admin
        broadcast(new UserCreated($user))->toOthers();

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // Login user and return token
    public function login(Request $request)
    {
          // 1. Validate the incoming payload
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'name' => 'nullable|string' // Required only if creating a new account
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Check if the user already exists in the database
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // 3. User exists -> Attempt to log them in
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                // Generate API Token
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Logged in successfully',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ], 200);
            }

            // If Auth::attempt fails, the password provided is incorrect
            return response()->json([
                'message' => 'Invalid credentials. Password does not match our records.'
            ], 401);
        }

        // 4. User does not exist -> Automatically create a new account
        $newUser = User::create([
            'name' => $request->name ?? explode('@', $request->email)[0], // Fallback if no name provided
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate API Token for the newly registered user
        $token = $newUser->createToken('auth_token')->plainTextToken;
        // Trigger real-time alert to admin
        broadcast(new UserCreated($newUser))->toOthers();

        return response()->json([
            'message' => 'Account created and logged in successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $newUser
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
         $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone_number' => 'required|string|min:8',
            'dob' => 'nullable|string|date',
            'user_type' => 'nullable|string',
            //'password' => 'nullable|string'
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
         // 2. Handle password update separately if provided
        // if (!empty($validator['password'])) {
        //     $validator['password'] = Hash::make($validator['password']);
        // } else {
        //     unset($validator['password']); // Do not overwrite with null
        // }

        // 3. Update user database record
        $user->update($request->all());

        // 4. Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user
        ], 200);
    }
    // Logout user (Revoke token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully!']);
    }

}
