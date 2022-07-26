<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $response_message = [
        'status' => 'error',
        'message' => ''
    ];

    /**
    * Instantiate a new UserController instance.
    *
    * @return void
    */
    public function __construct()
    {
    }

    public function getRole() {
        try {
            $userRole = User::where('id',  Auth::id())->select('role')->first();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'User role retrieved.';
            $this->response_message['result'] = $userRole;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }
}
