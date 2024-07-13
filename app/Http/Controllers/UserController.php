<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;
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
    public function __construct() {

    }

    /**
     * Get users with search and filters.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Define default pagination limit
            $limit = $request->input('limit', 10);

            // Build base query for users
            $query = User::query();

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // Apply search
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%$searchTerm%")
                      ->orWhere('last_name', 'like', "%$searchTerm%")
                      ->orWhere('email', 'like', "%$searchTerm%");
                });
            }

            // Execute the query with pagination
            $users = $query->paginate($limit);

            return response()->json($users, 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Failed to fetch users. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a single user by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id)->with(['information','addresses','shops']);

            return response()->json(['user' => $user], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    /**
     * Create/Register a new user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone_number' => 'nullable|string|unique:users,phone_number',
                'password' => 'required|string|min:8',
                'status' => 'required|in:active,inactive,suspended',
                'role' => 'required|string',
                'addresses' => 'required|array',
                'addresses.*.line_1' => 'required|string|max:255',
                'addresses.*.line_2' => 'nullable|string|max:255',
                'addresses.*.city' => 'required|string|max:255',
                'addresses.*.state' => 'required|string|max:255',
                'addresses.*.zipcode' => 'required|string|max:20',
                'addresses.*.country' => 'required|string|max:255',
                'addresses.*.latitude' => 'nullable|numeric',
                'addresses.*.longitude' => 'nullable|numeric',
                'addresses.*.is_primary' => 'boolean',
                'primary_contact' => 'required|string|max:255',
                'secondary_contact' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Begin a transaction
            DB::beginTransaction();

            // Create the user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'status' => $request->status,
                'role' => $request->role,
            ]);

            // Create addresses for the user
            foreach ($request->addresses as $addressData) {
                $address = [
                    'addressable_id' => $user->id,
                    'addressable_type' => User::class,
                    'line_1' => $addressData['line_1'],
                    'line_2' => $addressData['line_2'] ?? null,
                    'city' => $addressData['city'],
                    'state' => $addressData['state'],
                    'zipcode' => $addressData['zipcode'],
                    'country' => $addressData['country'],
                    'latitude' => $addressData['latitude'] ?? null,
                    'longitude' => $addressData['longitude'] ?? null,
                    'is_primary' => $addressData['is_primary'] ?? false,
                ];

                $user->addresses()::create($address);
            }

            // Create the user information
            $userInfo = [
                'user_id' => $user->id,
                'primary_contact' => $request->primary_contact,
                'secondary_contact' => $request->secondary_contact,
            ];

            $user->information()::create($userInfo);

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);

        } catch (Throwable $e) {
            report($e);
            // Rollback the transaction on error
            DB::rollback();
            return response()->json(['error' => 'User creation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update user details.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate user input
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'phone_number' => 'nullable|string|unique:users,phone_number,' . $id,
                'password' => 'nullable|string|min:8',
                'status' => 'required|in:active,inactive,suspended',
                'role' => 'required|string',
                'addresses' => 'required|array',
                'addresses.*.id' => 'nullable|exists:addresses,id',
                'addresses.*.line_1' => 'required|string|max:255',
                'addresses.*.line_2' => 'nullable|string|max:255',
                'addresses.*.city' => 'required|string|max:255',
                'addresses.*.state' => 'required|string|max:255',
                'addresses.*.zipcode' => 'required|string|max:20',
                'addresses.*.country' => 'required|string|max:255',
                'addresses.*.latitude' => 'nullable|numeric',
                'addresses.*.longitude' => 'nullable|numeric',
                'addresses.*.is_primary' => 'boolean',
                'primary_contact' => 'required|string|max:255',
                'secondary_contact' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Begin a transaction
            DB::beginTransaction();

            // Find the user
            $user = User::findOrFail($id);
            
            // Update user details
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => $request->password ? $request->password : $user->password,
                'status' => $request->status,
                'role' => $request->role,
            ]);

             // Update or create addresses for the user
             foreach ($request->addresses as $addressData) {
                Address::updateOrCreate(
                    ['id' => $addressData['id']],
                    [
                        'addressable_id' => $user->id,
                        'addressable_type' => User::class,
                        'line_1' => $addressData['line_1'],
                        'line_2' => $addressData['line_2'] ?? null,
                        'city' => $addressData['city'],
                        'state' => $addressData['state'],
                        'zipcode' => $addressData['zipcode'],
                        'country' => $addressData['country'],
                        'latitude' => $addressData['latitude'] ?? null,
                        'longitude' => $addressData['longitude'] ?? null,
                        'is_primary' => $addressData['is_primary'] ?? false,
                    ]
                );
            }
 
            // Update the user's information
            $userInfo = $user->information()->first();
            if ($userInfo) {
                $userInfo->update([
                    'primary_contact' => $request->primary_contact,
                    'secondary_contact' => $request->secondary_contact,
                ]);
            }

            // Commit the transaction
            DB::commit();
            return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
        } catch (Throwable $e) {
            report($e);
            // Rollback the transaction on error
            DB::rollback();
            return response()->json(['error' => 'User update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id);

            // Delete the user
            $user->delete();

            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'User deletion failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate a unique 6-character verification code.
     *
     * @param string $type
     * @return string
     */
    private function generateUniqueVerificationCode($type)
    {
        do {
            $code = Str::random(6);
            $exists = User::where($type . '_code_verification', $code)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * Verify phone number.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhoneNumber(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string',
                'phone_number_code_verification' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $user = User::where('phone_number', $request->phone_number)
                        ->where('phone_number_code_verification', $request->phone_number_code_verification)
                        ->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid verification code or phone number'], 404);
            }

            $user->phone_number_verified = true;
            $user->phone_number_code_verification = null;
            $user->save();

            return response()->json(['message' => 'Phone number successfully verified']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Phone number verification failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify email address.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'email_code_verification' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $user = User::where('email', $request->email)
                        ->where('email_code_verification', $request->email_code_verification)
                        ->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid verification code or email'], 404);
            }

            $user->email_verified = true;
            $user->email_code_verification = null;
            $user->save();

            return response()->json(['message' => 'Email successfully verified']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'Email verification failed: ' . $e->getMessage()], 500);
        }
    }

    //OLD
    public function indexOld(Request $request) {
        try {
            $request_data = $request->only([
                'length',
                'start',
                'search'
            ]);

            // for pagination
            $page = ($request_data['start'] / $request_data['length']) + 1;
            $searchFilter = $request_data['search'];
            $users = User::where(function ($q) use ($searchFilter) {
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

    public function getall(Request $request) {
        try {
            $request_data = $request->only([
                'search'
            ]);
            $searchFilter = $request_data['search'];
            $users = User::with(['user_informations', 'user_shop'])
                ->where(function ($q) use ($searchFilter) {
                    $q->where('id', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('first_name', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('last_name', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('email', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('role', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('status', 'LIKE', '%' . ($searchFilter) . '%');
                    })
                    ->orderBy('first_name', 'asc')
                    ->get();


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

    public function createBuyer(Request $request) {
        try {
            $this->validate($request, [
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'email' => 'required|email|max:200', 'unique',
                'password' => 'required|max:60',
            ]);
            $user_data = $request->only([
                'first_name',
                'last_name',
                'email',
                'password',
            ]);

            $emailValidation = User::where('email', $user_data['email'])->first();
            if (!empty($emailValidation)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Email already exist';

                return response()->json($this->response_message, 409);
            }
            $user_data['status'] = 'active';
            $user_data['role'] = 'buyer';
            DB::beginTransaction();
            $user = User::create($user_data);

            if (!empty($user)) {

                    $user_information = [
                        "user_id" => $user->id,
                        "complete_address" => $request->complete_address,
                        "primary_contact"=> $request->primary_contact,
                        "secondary_contact"=> ""
                    ];
                    $user['user_informations'] = UserInformation::create($user_information);

                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Registration successfully!';
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

    public function updateOld(Request $request) {
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

    public function deleteOld($id)
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

    public function getOld($id) {
        try {
            $user = User::with(['userInformations', 'userShop'])->where('id', $id)->first();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'User retrieved.';
            $this->response_message['result'] = $user;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function me() {
        try {
            $user = User::with(['userInformations', 'userShop'])->where('id', Auth::id())->first();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'User retrieved.';
            $this->response_message['result'] = $user;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function upload(Request $request, $id) {
        try{
            if($request->hasFile('file')){
                $path = $request->file('file')->store(env('GOOGLE_DRIVE_FOLDER_ID'), 'google');
                $url = Storage::disk('google')->url($path);
                $user_shop = UserShop::where('id', $id)->first();
                DB::beginTransaction();
                if (!empty($user_shop)) {
                    $user_shop->image_url = $url;
                    $user_shop->save();
                    DB::commit();
                    $this->response_message['status'] = 'success';
                    $this->response_message['message'] = 'File upload successfully.';

                    return response()->json($this->response_message, 200);
                }
            }else{
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'File upload failed.';
            }

            return response()->json( $this->response_message);
        } catch (\Exception $e) {
            report($e);

            $this->response_message['message'] = $e->getMessage();
        }
        DB::rollBack();
        return response()->json($this->response_message, 500);
    }

    public function getUserShop($id) {
        try {
            $userShop = UserShop::where('id', $id)->first();

            if (empty($userShop)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'User shop not found.';
                $this->response_message['result'] = $userShop;

                return response()->json($this->response_message, 404);
            }
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'User shop data retrieved.';
            $this->response_message['result'] = $userShop;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }
}
