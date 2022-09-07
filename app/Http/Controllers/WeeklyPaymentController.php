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
                $statusFilter = ['pending', 'merchant_approved', 'admin_approved'];
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

    public function create(Request $request) {
        try {
            $request_data = $request->only([
                'from',
                'to'
            ]);
            $merchants = User::select('id', 'full_name')->where('role', 'client')->get();
            $data = [];
            foreach ($merchants as $merchant) {
                $amount = 0;
                $orders = Order::where('merchant_user_id', $merchant->id)
                ->where(function ($q) use ($request_data) {
                    $q->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), '>=', $request_data['from'])
                        ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), '<=', $request_data['to']);
                })->where('collected_at', null)->get();
                foreach($orders as $order) {
                    $amount += $order->convenience_fee != null ? $order->convenience_fee : 0;
                }
                if ($amount == 0) {
                    continue;
                }
                $data[$merchant->id] = [
                    'amount' => $amount,
                    'date_from' => $request_data['from'],
                    'to' => $request_data['to'],
                    'merchant_name' => $merchant->full_name,
                    'merchant_id' => $merchant->id
                ];
            }


            DB::beginTransaction();
            $logs = [];
            foreach($data as $entity) {
                $weekly_payment = WeeklyPayment::create($entity);
                if (empty($weekly_payment)) {
                    array_push($logs, [
                        'merchant' => $entity,
                        'not sent'
                    ]);
                } else {
                    array_push($logs, [
                        'merchant' => $entity,
                        'sent'
                    ]);
                }
            }

            DB::commit();
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Payment report has been sent to all merchant.';
            $this->response_message['logs'] = $logs;

            return response()->json($this->response_message, 200);

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
