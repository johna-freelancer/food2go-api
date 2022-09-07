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

    public function addProduct($product_id) {
        try {
            $validate = Inventory::where('product_id', $product_id)->first();
            if (!empty($validate)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Product is already in inventory.';
                return response()->json($this->response_message, 409);
            }
            DB::beginTransaction();
            $inventory_data['product_id'] = $product_id;
            $inventory_data['quantity'] = 0;
            $inventory_data['user_id'] = Auth::id();
            $inventory = Inventory::create($inventory_data);

            if (!empty($inventory)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product has been added in inventory.';
                $this->response_message['result'] = $inventory;

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

    public function removeProduct($product_id) {
        try {
            $inventory = Inventory::where('product_id', $product_id)->where('user_id', Auth::id())->first();
            if (empty($inventory)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'No product found in inventory.';
                return response()->json($this->response_message, 409);
            }
            DB::beginTransaction();
            if (!empty($inventory)) {
                $inventory->delete();
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product has been removed in inventory.';
                $this->response_message['result'] = $inventory;

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

    public function addProductWithQuantity(Request $request) {
        try {
            $request_data = $request->only([
                'quantity',
                'product_id'
            ]);
            $validate = Inventory::where('product_id', $request_data['product_id'])->first();
            if (!empty($validate)) {
                $this->response_message['status'] = 'failed';
                $this->response_message['message'] = 'Product is already in inventory.';
                return response()->json($this->response_message, 409);
            }
            DB::beginTransaction();
            $inventory_data['product_id'] = $request_data['product_id'];
            $inventory_data['quantity'] = $request_data['quantity'];
            $inventory_data['user_id'] = Auth::id();
            $inventory = Inventory::create($inventory_data);

            if (!empty($inventory)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product has been added in inventory.';
                $this->response_message['result'] = $inventory;

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

    public function changeQuantity(Request $request) {
        try {
            $request_data = $request->only([
                'quantity',
                'product_id'
            ]);

             DB::beginTransaction();
             $inventory = Inventory::where('product_id', $request_data['product_id'])->first();


            if ($inventory->update($request_data)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Inventory updated.';
                $this->response_message['result'] = $inventory;

                return response()->json($this->response_message, 200);
            }
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

}
