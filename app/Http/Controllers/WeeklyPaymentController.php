<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\UserInformation;
use App\Models\UserShop;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\WeeklyPayment;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class WeeklyPaymentController extends Controller
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
                'search',
                'merchant_id',
                'date_from',
                'date_to',
                'status'
            ]);
            if (strtolower($request_data['status']) == 'all'){
                $statusFilter = ['pending', 'approved'];
            } else {
                $statusFilter = [strtolower($request_data['status'])];
            }

            // for pagination
            $page = ($request_data['start'] / $request_data['length']) + 1;
            $weekly_payment = WeeklyPayment::
                 where('merchant_id', $request_data['merchant_id'])
                 ->where(function ($q) use ($request_data) {
                    $q->whereBetween('date_from', [$request_data['date_from'], $request_data['date_to']])
                        ->orWhere('date_from', '<=', $request_data['date_to']);
                })
                ->where(function ($q) use ($request_data) {
                    $q->whereBetween('date_to', [$request_data['date_from'], $request_data['date_to']])
                        ->orWhere('date_to', '>=', $request_data['date_from']);
                })
                ->whereIn('status', $statusFilter)
                ->orderBy('id', 'desc')
                ->paginate($request_data['length'], ['*'], 'page', $page);
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Record retrieved.';
            $this->response_message['result'] = $weekly_payment;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function create() {
        try {
            DB::beginTransaction();

            $uncollected_orders = Order::where('collected_at', null)->groupBy('merchant_user_id')->get();
            dd($uncollected_orders);

            $request_data['merchant_agreed_at'] = Carbon::now();
            $weekly_payment = WeeklyPayment::create($request_data);

            if (!empty($weekly_payment)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Payment report has been submitted. Please wait for the admin to verify.';
                $this->response_message['result'] = $weekly_payment;

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

    public function approve(Request $request) {
        try {
            $request_data = $request->only([
                'id',
                'status',
            ]);
            DB::beginTransaction();
            $weekly_payment = WeeklyPayment::where('id', $request_data['id']);


            if (!empty($weekly_payment)) {
                $weekly_payment->status = 'approved';
                $weekly_payment->admin_agreed_at = Carbon::now();
                $weekly_payment->save();
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Payment report has been approved!';
                $this->response_message['result'] = $weekly_payment;

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


}
