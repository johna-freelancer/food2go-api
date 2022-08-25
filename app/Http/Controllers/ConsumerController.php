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

class ConsumerController extends Controller
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
    public function test(){
        try {
                 $this->sendNewOrderEvent('New order with order number: ');
                 $this->response_message['message'] = 'sucess';
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
    }

    public function searchStoreByProductOrName (Request $request) {
        try {
            $keyword = $request->query('keyword');
            $inventory_user_ids = Product::select('products.user_id')->distinct()->join('inventories as inventory', function ($join) use ($keyword) {
                $join->on('inventory.product_id', '=', 'products.id')
                    ->where(DB::raw("TRIM(REGEXP_REPLACE(products.name, '[^[:alnum:]]+', ''))"), 'LIKE', '%' . ($this->cleanString($keyword)) . '%')
                    ->orWhere('products.tags', 'LIKE', '%' . ($keyword) . '%');
            })
            ->get()->pluck('user_id');

            $shops = UserShop::with('users')->where('name', 'LIKE', '%' . ($keyword) . '%')
            ->where('is_active', '1')
            ->orWhereIn('user_id', $inventory_user_ids)
            ->get();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Store successfully retrieved.';
            $this->response_message['result'] = $shops;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function getAllAvailableProductByStoreId($store_id) {
        try {

            $userShop = UserShop::where('id', $store_id)->first();
            if (empty($userShop)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Store does not exist';

                return response()->json($this->response_message, 404);
            }
            $inventory = Inventory::
            leftJoin('products as product', function ($join) {
                $join->on('product.id', '=', 'inventories.product_id');
            })
            ->where('inventories.user_id', $userShop->user_id)->get()->toArray();
            $carouselPage = Count($inventory)/5;
            $carouselRemainder = Count($inventory)%5;
            $carouselPage = ($carouselPage < 1 ? 1 : $carouselPage);
            $data = [];
            for($page = 1; $page <= $carouselPage; $page++){
                $products = [];

                for ($product = 0; $product < 5; $product++) {
                    $prod = array_pop($inventory);
                    array_push($products, $prod);
                }

                $item = [
                    'page' => $page,
                    'products' => $products
                ];
                array_push($data, $item);
            }
            if ($carouselRemainder > 0) {
                $item = [
                    'page' => (((int)$carouselPage)+1),
                    'products' => $inventory
                ];
                array_push($data, $item);
            }
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Store successfully retrieved.';
            $this->response_message['result'] = $data;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['status'] = 'failed';
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

}
