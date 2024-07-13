<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'failed', 'error' => $validator->errors()], 422);
            }

            $credentials = $request->only('email', 'password');

            $user = User::where('email', $credentials['email'])
                    ->with('information', 'addresses', 'shops')
                    ->select('id', 'first_name', 'last_name', 'email', 'status', 'role')
                    ->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account is not registered in our system. Please contact the administrator.'
                ], 400);
            }

            if ($user->status != 'active') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account is not active. Please contact the administrator.'
                ], 400);
            }

            $token = Auth::setTTL(env('TOKEN_EXPIRY'))->attempt($credentials);

            if (!$token) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account is not registered in our system. Please contact the administrator.'
                ], 400);
            }

            return $this->respondWithToken($token, $user);

        } catch (Throwable $e) {
            return response()->json(['status' => 'failed', 'error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        if (!Auth::user()) {
            return response()->json([
                'status' => 'failed',
                'data' => null,
                'message' => 'User is not found.'
            ], 401);
        }

        $me = Auth::user()->with(['information', 'addresses', 'shops'])->first();

        return response()->json([
            'status' => 'success',
            'data' => $me,
            'message' => 'User loaded.'
        ], 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Logout successfully!'
        ], 200);
    }

}
