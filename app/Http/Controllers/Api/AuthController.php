<?php

namespace App\Http\Controllers\Api;

use App\Events\UserCreated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'dob' => 'nullable|string|date',
            'user_type' => 'nullable|string|in:driver,rider,admin',
            'country' => 'nullable|string',
            'phone_number' => 'nullable|string|min:8',
            'password' => 'required|string|min:8'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'dob' => $fields['dob'],
            'user_type' => request()->is('api/v1/rider/register') ? 'rider' : (request()->is('api/v1/driver/register') ? 'driver' : 'admin'),
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
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'user_type' => $user->user_type, 'country' => $user->country])) {
                // Generate API Token
                $token = $user->createToken('auth_token')->plainTextToken;

                $user = Auth::user();
                $navigateTo  = '/api/login';
                if($user->user_type === 'rider' && $user->country === 'nigeria'){
                   $navigateTo = '/api/v1/ng/rider/dashboard';
                } elseif($user->user_type === 'rider' && $user->country === 'united states'){
                    $navigateTo = '/api/v1/us/rider/dashboard';
                } elseif($user->user_type === 'driver' && $user->country === 'nigeria'){
                    $navigateTo = '/api/v1/ng/driver/dashboard';
                } elseif($user->user_type === 'driver' && $user->country === 'united states'){
                    $navigateTo = '/api/v1/us/driver/dashboard';
                }
                  return response()->json([
                    'message' => 'Login successful',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'redirect_to' => $navigateTo // Frontend reads this to navigate
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
    public function updateProfileRider(Request $request)
    {
        // 1. Fetch the authenticated user instance
    $user = $request->user();

    // 2. Validate incoming profile inputs dynamically
    // The email unique rule ignores the current user's ID to prevent validation failure
    $validated = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        'phone_number' => ['required', 'string', 'min:8'],
        'dob' => ['required', 'date'],
        'city' => ['required', 'string'],
        'country' => ['required', 'string', 'in:united states,nigeria'],
        'status' => ['nullable', 'string', 'in:online,offline,on_trip'],
        'curr_lat' => ['nullable', 'numeric', 'between:-90,90', 'regex:/^-?\d{1,2}(\.\d{1,8})?$/'],
        'curr_long' => ['nullable', 'numeric', 'between:-180,180', 'regex:/^-?\d{1,2}(\.\d{1,8})?$/'],
        'address' => ['nullable', 'string'],
        'zip_code' => ['nullable', 'string'],
        'state' => ['nullable', 'string'],
    ]);

            // 3. Persist the changes directly to your database table fields
            $user->update($validated);

            // 4. Return a clean API JSON success response
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh() // Returns the freshly updated user record
            ], 200);

    }
    public function updateProfileDriver(Request $request){

        $user = $request->user(); // Get the authenticated user
         $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['required', 'string', 'min:8'],
            'dob' => ['required', 'date'],
            'city' => ['required', 'string'],
            'country' => ['required', 'string', 'in:united states,nigeria'],
            'status' => ['nullable', 'string', 'in:online,offline,on_trip'],
            'curr_lat' => ['nullable', 'numeric', 'between:-90,90', 'regex:/^-?\d{1,2}(\.\d{1,8})?$/'],
            'curr_long' => ['nullable', 'numeric', 'between:-180,180', 'regex:/^-?\d{1,2}(\.\d{1,8})?$/'],
            'address' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string'],
            'state' => ['required', 'string'],
            'license_number' => ['required', 'string'],
            'vehicle_type' => ['required', 'string'],
            'vehicle_model' => ['required', 'string'],
            'vehicle_color' => ['required', 'string'],
            'vehicle_plate_number' => ['required', 'string'],
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
         // 1. Validate the form inputs
       // 1. Validate the incoming JSON payload
    $validated = $request->validate([
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
    ]);

    // 2. Fetch the authenticated user instance
    $user = $request->user();

    // 3. Assign the securely hashed new password directly to the attribute
    $user->password = Hash::make($validated['password']);

    // 4. Persist the changes directly to your database table
    $user->save();
    return response()->json([
        'success' => true,
        'message' => 'Password updated successfully.',
        'data' => $user
    ], 200);
    }
    // change image or update image
    public function changeImage(Request $request){
         $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048', // Max size 2MB
        ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $user = $request->user(); // Get the authenticated user
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/images', $filename);
                // Update user's image path in the database
                $user->update(['profile_image' => $filename]);
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

    public function changePasswordDriver(Request $request){
         $user = $request->user(); // Get the authenticated user
          // 1. Validate the form inputs
       // 1. Validate the incoming JSON payload
    $validated = $request->validate([
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
    ]);

    // 2. Fetch the authenticated user instance
    $user = $request->user();

    // 3. Assign the securely hashed new password directly to the attribute
    $user->password = Hash::make($validated['password']);

    // 4. Persist the changes directly to your database table
           $user->save();
        // 4. Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'password updated successfully.',
            'data' => $user
        ], 200);

    }

    public function changeImageDriver(Request $request){
         $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048', // Max size 2MB
        ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $user = $request->user(); // Get the authenticated user
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/images', $filename);
                // Update user's image path in the database
                $user->update(['profile_image' => $filename]);
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

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = PasswordFacade::sendResetLink(
            $request->only('email')
        );

        return $status === PasswordFacade::RESET_LINK_SENT
                    ? response()->json(['message' => __($status)], 200)
                    : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'code' => 'required|string|min:6|max:6',
        'password' => 'required|string|min:8|confirmed',
    ]);

    // 1. Retrieve the token record from the database table
    $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

    if (!$record) {
        return response()->json(['message' => 'Invalid request or token expired.'], 400);
    }

    // 2. Regenerate your 6-digit verification logic to check if it matches the token on file
    $expectedPin = crc32($record->token) % 1000000;
    $expectedPin = str_pad(abs($expectedPin), 6, '0', STR_PAD_LEFT);

    if ($request->code !== $expectedPin) {
        return response()->json(['message' => 'The reset code provided is incorrect.'], 400);
    }

    // 3. Update the user password
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // 4. Delete the used token record
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['message' => 'Password reset successful! You can now log in.']);
}

}
