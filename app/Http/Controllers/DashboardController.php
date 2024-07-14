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
use Carbon\CarbonPeriod;

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

    public function getTotalCollectableAmount(Request $request)
    {
        try {

            $request_data = $request->only([
                'range'
            ]);

            if ($request_data['range'] == '30days') {
                $periods = CarbonPeriod::create(Carbon::now()->subDays(29)->toDateString(), Carbon::now()->toDateString());
                $amount = 0;
                $data = [];
                $labels = [];
                foreach ($periods as $date) {
                    $date = $date->toDateString();
                    $orders = Order::where('collected_at', null)
                        ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), $date)
                        ->where('status', 'completed')
                        ->get();
                    if (count($orders) > 0) {
                        foreach ($orders as $order) {
                            $amount += $order->convenience_fee;
                        }
                        array_push($data, $order->convenience_fee);
                        array_push($labels, $date);
                    } else {
                        array_push($data, 0);
                        array_push($labels, $date);
                    }
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collectable Amount Retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => $labels,
                    'series' => [
                        [
                            'data' => $data,
                            'name' => 'Total Collectable Amount'
                        ]
                    ]
                ];

                return response()->json($this->response_message, 200);
            } else if ($request_data['range'] == '7days') {
                $periods = CarbonPeriod::create(Carbon::now()->subDays(6)->toDateString(), Carbon::now()->toDateString());
                $amount = 0;
                $data = [];
                $labels = [];
                foreach ($periods as $date) {
                    $date = $date->toDateString();
                    $orders = Order::where('collected_at', null)
                        ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), $date)
                        ->where('status', 'completed')
                        ->get();
                    if (count($orders) > 0) {
                        foreach ($orders as $order) {
                            $amount += $order->convenience_fee;
                        }
                        array_push($data, $order->convenience_fee);
                        array_push($labels, $date);
                    } else {
                        array_push($data, 0);
                        array_push($labels, $date);
                    }
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collectable Amount Retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => $labels,
                    'series' => [
                        [
                            'data' => $data,
                            'name' => 'Total Collectable Amount'
                        ]
                    ]
                ];

                return response()->json($this->response_message, 200);
            } else { //today
                $orders = Order::where('collected_at', null)
                    ->where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), date('y-m-d'))
                    ->where('status', 'completed')
                    ->get();
                $amount = 0;
                foreach ($orders as $order) {
                    $amount += $order->convenience_fee;
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collectable Amount Retrieved.';
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
        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }
    }


    public function getTotalCollectedAmount(Request $request)
    {
        try {

            $request_data = $request->only([
                'range'
            ]);

            if ($request_data['range'] == '30days') {
                $periods = CarbonPeriod::create(Carbon::now()->subDays(29)->toDateString(), Carbon::now()->toDateString());
                $amount = 0;
                $data = [];
                $labels = [];
                foreach ($periods as $date) {
                    $date = $date->toDateString();
                    $orders = Order::where('collected_at', '!=', null)
                        ->where(DB::raw("DATE_FORMAT(collected_at, '%Y-%m-%d')"), $date)
                        ->where('status', 'completed')
                        ->get();
                    if (count($orders) > 0) {
                        foreach ($orders as $order) {
                            $amount += $order->convenience_fee;
                        }
                        array_push($data, $order->convenience_fee);
                        array_push($labels, $date);
                    } else {
                        array_push($data, 0);
                        array_push($labels, $date);
                    }
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collected Amount Retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => $labels,
                    'series' => [
                        [
                            'data' => $data,
                            'name' => 'Total Collected Amount'
                        ]
                    ]
                ];

                return response()->json($this->response_message, 200);
            } else if ($request_data['range'] == '7days') {
                $periods = CarbonPeriod::create(Carbon::now()->subDays(6)->toDateString(), Carbon::now()->toDateString());
                $amount = 0;
                $data = [];
                $labels = [];
                foreach ($periods as $date) {
                    $date = $date->toDateString();
                    $orders = Order::where('collected_at', '!=', null)
                        ->where(DB::raw("DATE_FORMAT(collected_at, '%Y-%m-%d')"), $date)
                        ->where('status', 'completed')
                        ->get();

                    if (count($orders) > 0) {
                        foreach ($orders as $order) {
                            $amount += $order->convenience_fee;
                        }
                        array_push($data, $order->convenience_fee);
                        array_push($labels, $date);
                    } else {
                        array_push($data, 0);
                        array_push($labels, $date);
                    }
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collected Amount Retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => $labels,
                    'series' => [
                        [
                            'data' => $data,
                            'name' => 'Total Collected Amount'
                        ]
                    ]
                ];

                return response()->json($this->response_message, 200);
            } else { //today
                $orders = Order::where('collected_at', '!=', null)
                    ->where(DB::raw("DATE_FORMAT(collected_at, '%Y-%m-%d')"), date('y-m-d'))
                    ->where('status', 'completed')
                    ->get();
                $amount = 0;
                foreach ($orders as $order) {
                    $amount += $order->convenience_fee;
                }
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Total Collected Amount Retrieved.';
                $this->response_message['result'] = [
                    'amount' => $amount,
                    'labels' => ['Today'],
                    'series' => [
                        [
                            'data' => [$amount],
                            'name' => 'Total Collected Amount'
                        ]
                    ]
                ];

                return response()->json($this->response_message, 200);
            }

            return [];
        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }
    }


    public function getActiveMerchantCount(Request $request)
    {
        try {

            $users = User::where('status', 'active')
                ->where('role', 'client')
                ->get();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Number of active merchant retrieved.';
            $this->response_message['result'] = [
                'amount' => count($users),
                'labels' => ['Today'],
                'series' => [
                    [
                        'data' => [count($users)],
                        'name' => 'Number of Active Merchants'
                    ]
                ]
            ];

            return response()->json($this->response_message, 200);

        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }
    }


    public function getNumberOfCompletedOrders()
    {
        try {

            $periods = CarbonPeriod::create(Carbon::Now()->startOfMonth()->toDateString(), Carbon::Now()->endOfMonth()->toDateString());
            $amount = 0;
            $data = [];
            $labels = [];
            foreach ($periods as $date) {
                $date = $date->toDateString();
                $order = Order::where(DB::raw("DATE_FORMAT(orders.changed_at_completed, '%Y-%m-%d')"), $date)
                    ->where('status', 'completed')
                    ->get();
                if (!empty($order)) {
                    $amount += count($order);
                    array_push($data, ['x' => $date, 'y' => count($order)]);
                    array_push($labels, $date);
                } else {
                    array_push($data, ['x' => $date, 'y' => 0]);
                    array_push($labels, $date);
                }
            }
            $res = [];
            $resitem = [];
            array_push($resitem, [
                'data' => $data,
                'name' => 'Number of Completed Orders'
            ]);
            $res['current-month'] = $resitem;
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Number of Completed Orders';
            $this->response_message['result'] = [
                'amount' => $amount,
                'series' => $res
            ];

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }
    }

}
