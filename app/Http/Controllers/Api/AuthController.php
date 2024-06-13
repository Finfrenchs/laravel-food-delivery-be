<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request...
        $validated = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|unique:users|max:100',
            'password' => 'required',
            'phone' => 'required',
            'roles' => 'required',
            // Validate optional fields
            'address' => 'nullable|string',
            'license_plate' => 'nullable|string',
            'restaurant_name' => 'nullable|string',
            'restaurant_address' => 'nullable|string',
            'latlong'=> 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Set default values for optional fields if not provided
        $defaultData = [
            'address' => $request->input('address', ''),
            'license_plate' => $request->input('license_plate', ''),
            'restaurant_name' => $request->input('restaurant_name', ''),
            'restaurant_address' => $request->input('restaurant_address', ''),
            'latlong' => $request->input('latlong', ''),
        ];

        // Handle file upload using storeAs
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName(); // Generate a unique filename
            $filePath = $file->storeAs('photos', $filename, 'public');
            $defaultData['photo'] = $filePath;
        } else {
            $defaultData['photo'] = '';
        }

        // Merge validated data with default values
        $data = array_merge($validated, $defaultData);

        // Password encryption
        $data['password'] = Hash::make($validated['password']);

        // Create user
        $user = User::create($data);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return response with all user attributes
        return response()->json([
            'message' => 'Register success',
            'access_token' => $token,
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout success',
        ], 200);
    }

    public function login(Request $request)
    {
        // Validate the request...
        $validated = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Success',
            'access_token' => $token,
            'user' => $user,
        ], 200);
    }

    public function getUserById($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }


        return response()->json([
            'message' => 'Get Data Success',
            'user' => $user,
        ], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'sometimes|required|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required',
            'phone' => 'sometimes|required',
            'address' => 'sometimes|nullable|string',
            'roles' => 'sometimes|required',
            'license_plate' => 'sometimes|nullable|string',
            'restaurant_name' => 'sometimes|nullable|string',
            'restaurant_address' => 'sometimes|nullable|string',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle file upload if present
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('photos', $filename, 'public');
            $validated['photo'] = $filePath;
        }

        // Hash password if present
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Update latitude and longitude if present
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $validated['latlong'] = $validated['latitude'] . ',' . $validated['longitude'];
        }

        // Update user data
        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

}
