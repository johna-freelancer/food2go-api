<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\UserInformation;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderController extends BaseController
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

    public function getOrdersByMerchantUserId(Request $request) {
        try {
            $request_data = $request->only([
                'merchant_user_id',
                'status',
                'date_from',
                'date_to',
                'search'
            ]);
            $orders = [];
            $searchFilter = $request_data['search'];
            $raw_orders = Order::with('users.userInformations')
                    ->select('orders.*')
                    ->leftJoin('users as customer', function ($join) {
                        $join->on('customer.id', '=', 'orders.customer_user_id');
                    })
                    ->where('merchant_user_id', $request_data['merchant_user_id'])
                    ->where(function ($q) use ($searchFilter) {
                        $q->where('orders.id', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('customer.full_name', 'LIKE', '%' . ($searchFilter) . '%');
                    })
                    ->where(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m-%d')"), '>=', $request_data['date_from'])
                    ->where(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m-%d')"), '<=', $request_data['date_to'])
                    ->where('orders.status', $request_data['status'])
                    ->get()->toArray();

            foreach($raw_orders as $raw_order) {
                $items = OrderList::where('orders_id', $raw_order['id'])->get();
                $output = [
                    'order' => $raw_order,
                    'order_list' => $items
                ];
                array_push($orders, $output);
            }
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Orders retrieved.';
            $this->response_message['result'] = $orders;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }



}
