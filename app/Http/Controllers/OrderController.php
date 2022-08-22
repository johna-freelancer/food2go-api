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


    public function addOrder(Request $request) {
        try {
            $request_data = $request->only([
                'merchant_user_id',
                'user_shops_id',
                'address',
                'contact',
                'mode_of_payment',
                'delivery_charge',
                'convenience_fee',
                'note',
                'order_list'
            ]);
            DB::beginTransaction();
            $order_entity = [];
            $order_entity['customer_user_id'] = Auth::id();
            $order_entity['merchant_user_id'] = $request_data['merchant_user_id'];
            $order_entity['user_shops_id'] = $request_data['user_shops_id'];
            $order_entity['address'] = $request_data['address'];
            $order_entity['contact'] = $request_data['contact'];
            $order_entity['mode_of_payment'] = $request_data['mode_of_payment'];
            $order_entity['delivery_charge'] = $request_data['delivery_charge'];
            $order_entity['convenience_fee'] = $request_data['convenience_fee'];
            $order_entity['note'] = $request_data['note'];
            $order = Order::create($order_entity);
            $total = 0;
            $delivery_charge = 0;
            $convenience_fee = 0;
            $user_shop = UserShop::where('id', $request_data['user_shops_id'])->first();
            $admin_setting = AdminSetting::where('name', 'convenience_fee')->first();
            if(!empty($user_shop)) {
                $delivery_charge = $user_shop->delivery_charge;
            }
            if(!empty($admin_setting)) {
                $convenience_fee = $admin_setting->value;
            }

            if (!empty($order)) {
                foreach($request_data['order_list'] as $item) {
                    $item_data = [];
                    $item_data['orders_id'] = $order->id;
                    $item_data['product_id'] = $item['product_id'];
                    $item_data['product_name'] = $item['product_name'];
                    $item_data['product_price'] = $item['price'];
                    $item_data['quantity'] = $item['quantity'];
                    $order_list = OrderList::create($item_data);
                    $total += $order_list->subtotal;
                }
                $order['total'] = $total;
                $order->save();
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Your order is now in queue.<br>Order number: ' . $order->id;
                $this->response_message['result'] = $order;

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

    public function changeStatus(Request $request) {
        try {
            if (!isset($request->order_id)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Order number is required';
                return response()->json($this->response_message, 400);
            }
            if (!isset($request->status)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Status is required';
                return response()->json($this->response_message, 400);
            } else {
                if (!isset($request->remarks)) {
                    $this->response_message['status'] = 'failed';
                    $this->response_message['message'] = 'Remarks is required';
                    return response()->json($this->response_message, 400);
                }
            }
            $order_id = $request->order_id;
            $status = $request->status;
            $remarks = $request->remarks;
            DB::beginTransaction();
            $order = Order::where('id', $order_id)->first();
            if (!empty($order)) {
                if($status == 'rejected') {
                    $order->remarks = $remarks;
                    $response_message['message'] = "Order #".$order_id.' was rejected.';
                } else if ($status == 'preparing') {
                    $response_message['message'] = "Order #".$order_id.' is now preparing.';
                } else if ($status == 'outfordelivery') {
                    $response_message['message'] = "Order #".$order_id.' is out for delivery.';
                } else {
                    $response_message['message'] = "Order #".$order_id.' is complete.';
                }
                $order->status = $status;
                $order->save();

                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['result'] = $order;
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

    public function upload(Request $request, $id) {
        try{
            if($request->hasFile('file')){
                $path = $request->file('file')->store(env('GOOGLE_DRIVE_FOLDER_ID'), 'google');
                $url = Storage::disk('google')->url($path);
                $order = Order::where('id', $id)->first();
                DB::beginTransaction();
                if (!empty($order)) {
                    $order->proof_url = $url;
                    $order->save();
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

    public function trackOrder($order_id) {
        try {
            $order = Order::where('id', $order_id)->select('status', 'remarks')
                    ->first();
            if (!empty($order)) {
                if ($order->status == 'pending') {
                    $this->response_message['message'] = 'Your order is in queue.';
                } else if ($order->status == 'preparing') {
                    $this->response_message['message'] = 'Your order is now preparing.';
                } else if ($order->status == 'outfordeliver') {
                    $this->response_message['message'] = 'Your order is out for delivery.';
                } else if ($order->status == 'rejected') {
                    $this->response_message['message'] = 'Your order is rejected.<br>note:' . $order->remarks;
                } else {
                    $this->response_message['message'] = 'This order is already delivered.';
                }
                $this->response_message['status'] = 'success';
                return response()->json($this->response_message, 200);
            } else {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'No order found';
                return response()->json($this->response_message, 404);
            }

        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

}
