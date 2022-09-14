<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\AdminSetting;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReportController extends Controller
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

    public function salesReport(Request $request) {
        try {
            $request_data = $request->only([
                'date_from',
                'date_to',
                'user_id'
            ]);
            $total_orders = 0;
            $total_sales = 0;
            $total_delivery_charge = 0;
            $total_convenience_fee = 0;

            $orders = Order::where('merchant_user_id', $request_data['user_id'])
                    ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), '>=', $request_data['date_from'])
                    ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), '<=', $request_data['date_to'])
                    ->where('status', 'completed');
            if(count($orders->get()) > 0) {
                $total_orders = count($orders->get());
                $total_sales = $orders->sum('total');
                $total_delivery_charge = $orders->sum('delivery_charge');
                $total_convenience_fee = $orders->sum('convenience_fee');
            }
            $this->response_message['status']='success';
            $this->response_message['message']='Sales report for the date of ' . $request_data['date_from'] . ' to ' . $request_data['date_to'];
            $this->response_message['result'] = [
                'total_orders' => $total_orders,
                'total_sales' => $total_sales,
                'total_delivery_charge' => $total_delivery_charge,
                'total_convenience_fee' => $total_convenience_fee
            ];
            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }


    public function eodReport(Request $request) {
        try {
            $request_data = $request->only([
                'date',
                'user_id'
            ]);
            $total_orders = 0;
            $total_sales = 0;
            $total_delivery_charge = 0;
            $total_convenience_fee = 0;
            $orders = Order::where('merchant_user_id', $request_data['user_id'])
                    ->where(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m-%d')"), $request_data['date'])
                    ->where('status', 'completed');
            if(count($orders->get()) > 0) {
                $total_orders = count($orders->get());
                $total_sales = $orders->sum('total');
                $total_delivery_charge = $orders->sum('delivery_charge');
                $total_convenience_fee = $orders->sum('convenience_fee');
            }
            $this->response_message['status']='success';
            $this->response_message['message']='EOD report for the date of ' . $request_data['date_from'] . ' to ' . $request_data['date_to'];
            $this->response_message['result'] = [
                'total_orders' => $total_orders,
                'total_sales' => $total_sales,
                'total_delivery_charge' => $total_delivery_charge,
                'total_convenience_fee' => $total_convenience_fee,

            ];
            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }


}
