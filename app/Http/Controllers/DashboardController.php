<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\UserInformation;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class DashboardController extends Controller
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

    public function getTotalCollectableAmount(Request $request) {
        try {

            $request_data = $request->only([
                'range'
            ]);

            if ($request_data['range'] == '30days') {

            } else if ($request_data['range'] == '7days') {

            } else { //today
                $orders = Order::where('collected_at', null)
                ->where(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m-%d')"), date('y-m-d'))
                ->where('status', 'completed')
                ->get();
                $amount = 0;
                foreach($orders as $order) {
                    $amount += $order->convenience_fee;
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Store successfully retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => ['Today'],
                    'series' => [
                            [
                                'data' => [$amount],
                                'name' => 'Total Collectable Amount'
                            ]
                        ]
                ];

                return response()->json($this->response_message, 200);
            }

            return [];
        }catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }
    }



}
