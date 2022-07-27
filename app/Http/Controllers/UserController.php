<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    public function index(Request $request) {
        try {
            $request_data = $request->only([
                'length',
                'start',
                'search'
            ]);

            // for pagination
            $page = ($request_data['start'] / $request_data['length']) + 1;
            $searchFilter = $request_data['search'];
            $users = User::
                where(function ($q) use ($searchFilter) {
                    $q->where('id', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('first_name', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('last_name', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('email', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('role', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('status', 'LIKE', '%' . ($searchFilter) . '%');
                    })
                ->paginate($request_data['length'], ['*'], 'page', $page);

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Users retrieved.';
            $this->response_message['result'] = $users;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
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

    public function create(Request $request) {
        try {
            $this->validate($request, [
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'email' => 'required|email|max:200', 'unique',
                'status' => 'required',
                'password' => 'required|max:60',
                'role' => 'max:20',
            ]);
            $user_data = $request->only([
                'first_name',
                'last_name',
                'email',
                'status',
                'password',
                'role'
            ]);
            $emailValidation = User::where('email', $user_data['email'])->first();
            if (!empty($emailValidation)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Email already exist';

                return response()->json($this->response_message, 409);
            }
            DB::beginTransaction();
            $user = User::create($user_data);

            if (!empty($user)) {
                if (isset($request->user_informations)) {
                    $user_informations_data = $request->only([
                        'user_informations'
                    ]);
                    $user_informations_data['user_informations']['user_id'] = $user->id;
                    $user_informations = UserInformation::create($user_informations_data['user_informations']);
                    $user['user_informations'] = $user_informations;
                }
                if (isset($request->user_shop)) {
                    $user_shop_data = $request->only([
                        'user_shop'
                    ]);
                    $user_shop_data['user_shop']['user_id'] = $user->id;
                    $user_shop = UserShop::create($user_shop_data['user_shop']);
                    $user['user_shop'] = $user_shop;
                }
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'User successfully created';
                $this->response_message['result'] = $user;

                return response()->json($this->response_message, 200);
            }

        } catch (ValidationException $e) {
            info($e);
            $errors = $e->errors();
            $this->response_message['message'] = reset($errors)[0];

            return response()->json($this->response_message, 400);

        } catch (\Exception $e) {
            report($e);

            $this->response_message['message'] = $e->getMessage();
        }
        DB::rollBack();

        return response()->json($this->response_message, 500);
    }

    public function update(Request $request) {
        try {
            $this->validate($request, [
                'id' => 'required', 'unique',
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'email' => 'required|email|max:200', 'unique',
                'status' => 'required',
                'password' => 'max:60',
                'role' => 'max:20',
            ]);
            $user_data = $request->only([
                'first_name',
                'last_name',
                'email',
                'status',
                'password',
                'role'
            ]);

            $user = User::where('id', $request->id)->first();
            $emailValidation = User::where('email', $user_data['email'])->where('id', '!=', $user->id)->first();
            if (!empty($emailValidation)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Email already exist';

                return response()->json($this->response_message, 409);
            }
            DB::beginTransaction();

            if (!empty($user)) {

                if ($user->update($user_data)) {
                    if (isset($request->user_informations)) {
                        $user_informations_data = $request->only([
                            'user_informations'
                        ]);
                        $user_informations_data['user_informations']['user_id'] = $user->id;
                        $user_informations = UserInformation::where('user_id', $user->id)->first();
                        if (empty($user_informations)) {
                            $user_informations = UserInformation::create($user_informations_data['user_informations']);
                        } else {
                            $user_informations->update($user_informations_data['user_informations']);
                        }
                        $user['user_informations'] = $user_informations;
                    }
                    if (isset($request->user_shop)) {
                        $user_shop_data = $request->only([
                            'user_shop'
                        ]);
                        $user_shop_data['user_shop']['user_id'] = $user->id;
                        $user_shop = UserShop::where('user_id', $user->id)->first();
                        if (empty($user_shop)) {
                            $user_shop = UserShop::create($user_shop_data['user_shop']);
                        } else {
                            $user_shop->update($user_shop_data['user_shop']);
                        }
                        $user['user_shop'] = $user_shop;
                    }
                    DB::commit();
                    $this->response_message['status'] = 'success';
                    $this->response_message['message'] = 'User successfully updated';
                    $this->response_message['result'] = $user;

                    return response()->json($this->response_message, 200);
                }

            }

            $this->response_message['message'] = 'User not found.';

            return response()->json($this->response_message, 404);

        } catch (ValidationException $e) {
            info($e);
            $errors = $e->errors();
            $this->response_message['message'] = reset($errors)[0];

            return response()->json($this->response_message, 400);

        } catch (\Exception $e) {
            report($e);

            $this->response_message['message'] = $e->getMessage();
        }
        DB::rollBack();

        return response()->json($this->response_message, 500);
    }


    public function delete($id)
    {
        try {
            $user = User::where('id', $id)->first();

            DB::beginTransaction();
            if ( !empty($user) ) {
                $user->delete();
                DB::commit();

                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'User successfully deleted.';

                return response()->json($this->response_message, 200);
            }

            $this->response_message['message'] = 'User not found.';

            return response()->json($this->response_message, 404);
        } catch (\Exception $e) {
            report($e);

            $this->response_message['message'] = $e->getMessage();
        }
        DB::rollBack();

        return response()->json($this->response_message, 500);
    }

    public function get($id) {
        try {
            $user = User::with(['userInformations', 'userShop'])->where('id', $id)->first();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'User role retrieved.';
            $this->response_message['result'] = $user;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }
}
