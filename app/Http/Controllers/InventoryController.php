<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\UserInformation;
use App\Models\UserShop;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryController extends BaseController
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
            $products = Inventory::
                    leftJoin('products as product', function ($join) {
                        $join->on('product.id', '=', 'inventories.product_id');
                    })
                    ->where(function ($q) use ($searchFilter) {
                        $q->where('product.id', 'LIKE', '%' . ($searchFilter) . '%')
                            ->orWhere('product.name', 'LIKE', '%' . ($searchFilter) . '%')
                            ->orWhere('product.description', 'LIKE', '%' . ($searchFilter) . '%')
                            ->orWhere('product.price', 'LIKE', '%' . ($searchFilter) . '%');
                    })
                    ->where('inventories.user_id', Auth::id())
                ->paginate($request_data['length'], ['*'], 'page', $page);
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Inventory products retrieved.';
            $this->response_message['result'] = $products;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

}
