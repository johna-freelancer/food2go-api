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
    *
    * @return void
    */
    public function __construct()
    {


    }


    public function trigger (Request $request) {
        $request_data = $request->only([
            'channel_name',
            'event_name',
            'data'
        ]);

        $this->sendEvent($request_data['channel_name'], $request_data['event_name'], $request_data['data']);
    }


}
