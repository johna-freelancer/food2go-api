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
                return response()->json($validator->errors(), 422);
            }

            $credentials = $request->only('email', 'password');

            $user = User::where($credentials['email'])
                    ->with(['information'])
                    ->select('id', 'first_name', 'last_name', 'email', 'status', 'role')
                    ->first();
            
            if ($user->status != 'active') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account is not active. Please contact the administrator.'
                ], 401);
            }

            $token = Auth::setTTL(env('TOKEN_EXPIRY'))->attempt($credentials);

            if (!$token) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account is not registered in our system. Please contact the administrator.'
                ], 401);
            }

            return $this->respondWithToken($token, $user);

        } catch (Throwable $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
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
            return response()->json(['message' => 'User is not found.'], 401);
        }

        $me = Auth::user()->with(['information'])->first();

        return response()->json($me, 200);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Logout successfully!'], 200);
    }

}
