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
            'dob' => 'nullable|string|date',
            'user_type' => 'nullable|string',
            'country' => 'nullable|string',
            'phone_number' => 'nullable|string|min:8',
            'password' => 'required|string|min:8'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'dob' => $fields['dob'],
            'user_type' => request()->is('api/rider/register') ? 'rider' : (request()->is('api/driver/register') ? 'driver' : 'admin'),
            'country' => $fields['country'],
            'phone_number' => $fields['phone_number'],
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
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'user_type' => $user->user_type])) {
                // Generate API Token
                $token = $user->createToken('auth_token')->plainTextToken;

                $user = Auth::user();
                if($user->user_type === 'rider'){
                    return response()->json([
                    'message' => 'Logged in successfully',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ], 200);
                } elseif($user->user_type === 'driver'){
                   return response()->json([
                    'message' => 'Logged in successfully',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ], 200);
                }

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
            'message' => 'Account created successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $newUser
        ], 201);
    }
    //update profile
    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
         $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone_number' => 'required|string|min:8',
            'dob' => 'nullable|string|date',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'status' => 'online|offline|on_trip',
            'curr_lat' => 'nullable|numeric',
            'curr_long' => 'nullable|numeric',
            'address' => 'nullable|string',
            'zip_code' => 'nullable|string',
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
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
    //change your password
    public function changePassword(Request $request){
         $user = $request->user(); // Get the authenticated user
         $validator = Validator::make($request->all(), [
            'password' => 'nullable|string|min:8',
        ]);
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
         // 2. Handle password update separately if provided
        if (!empty($validator['password'])) {
            $validator['password'] = Hash::make($validator['password']);
        } else {
            unset($validator['password']); // Do not overwrite with null
        }
         // 3. Update user database record
        $user->update($validator->validated());

        // 4. Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'password updated successfully.',
            'data' => $user
        ], 200);

    }
    // change image or update image
    public function changeImage(Request $request){
         $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048', // Max size 2MB
        ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $user = $request->user(); // Get the authenticated user
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/images', $filename);
                // Update user's image path in the database
                $user->update(['image' => $filename]);
                return response()->json([
                    'success' => true,
                    'message' => 'Image updated successfully.',
                    'data' => $user
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No image file provided.'
                ], 400);
            }
    }

}
