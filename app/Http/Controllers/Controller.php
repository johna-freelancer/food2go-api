<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function respondWithToken($token, $user)
	{
		return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 720,
                'profile' => $user
            ],
            'message' => 'Successfully logged in!'

		], 200);
	}

    protected function cleanString($keyword) {
        return TRIM(preg_replace('/[^a-zA-Z0-9]/', '',$keyword));
    }
}
