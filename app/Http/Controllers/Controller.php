<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Pusher\Pusher;
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

    protected function sendNewOrderEvent($msg) {

        $options = array(
            'cluster' => 'ap1',
            'useTLS' => true
        );

        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
          );

          $data['message'] = $msg;
          $pusher->trigger(env('CHANNEL_NAME'), env('EVENT_NAME'), $data);
    }
}
