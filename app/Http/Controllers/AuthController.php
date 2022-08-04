<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);
        $email = $request->only(['email']);

        $user = User::where($email)
                    ->with(['userInformations'])
                    ->select('id', 'first_name', 'last_name', 'email', 'status', 'role')
                    ->first();
        if (!$token = Auth::setTTL(env('TOKEN_EXPIRY'))->attempt($credentials)) {
            return response()->json(['message' => 'Your account is not registered in our system. Please contact the administrator.'], 401);
        }

        if ($user->status != 'active') {
            return response()->json(['message' => 'Your account is not active. Please contact the administrator.'], 401);
        }

        return $this->respondWithToken($token, $user);

    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Logout successfully!']);
    }

    public function test() {
        dd('test');
    }

}
