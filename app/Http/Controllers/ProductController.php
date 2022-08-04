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

class ProductController extends Controller
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
            $products = Product::
                where(function ($q) use ($searchFilter) {
                    $q->where('id', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('name', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('description', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('tags', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('status', 'LIKE', '%' . ($searchFilter) . '%')
                        ->orWhere('price', 'LIKE', '%' . ($searchFilter) . '%');
                    })
                    ->where('user_id', Auth::id())
                ->paginate($request_data['length'], ['*'], 'page', $page);

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Products retrieved.';
            $this->response_message['result'] = $products;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function create(Request $request) {
        try {
            $this->validate($request, [
                'name' => 'required|max:100',
                'description' => 'required|max:255',
                'price' => 'required',
                'status' => 'required',
                'user_id' => 'required',
            ]);
            $request_data = $request->only([
                'name',
                'description',
                'price',
                'status',
                'tags',
                'user_id'

            ]);

            DB::beginTransaction();
            $product = Product::create($request_data);

            if (!empty($product)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product successfully added.';
                $this->response_message['result'] = $product;

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


    public function update(Request $request) {
        try {
            $this->validate($request, [
                'id' => 'required', 'unique',
                'name' => 'required|max:100',
                'description' => 'required|max:255',
                'price' => 'required',
                'status' => 'required',
            ]);
            $request_data = $request->only([
                'id',
                'name',
                'description',
                'price',
                'status',
                'tags',
                'user_id'
            ]);

            DB::beginTransaction();
            $product = Product::where('id', $request_data['id']);

            if ($product->update($request_data)) {
                DB::commit();
                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product successfully updated';
                $this->response_message['result'] = $product;

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
                $product = Product::where('id', $id)->first();
                DB::beginTransaction();
                if (!empty($product)) {
                    $product->image_url = $url;
                    $product->save();
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

    public function delete($id)
    {
        try {
            $product = Product::where('id', $id)->first();

            DB::beginTransaction();
            if ( !empty($product) ) {
                $product->delete();
                DB::commit();

                $this->response_message['status'] = 'success';
                $this->response_message['message'] = 'Product successfully deleted.';

                return response()->json($this->response_message, 200);
            }

            $this->response_message['message'] = 'Product not found.';

            return response()->json($this->response_message, 404);
        } catch (\Exception $e) {
            report($e);

            $this->response_message['message'] = $e->getMessage();
        }
        DB::rollBack();

        return response()->json($this->response_message, 500);
    }

    public function get($id) {
        try {
            $product = Product::where('id', $id)->first();

            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Product retrieved.';
            $this->response_message['result'] = $product;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }

    public function getProductsForInventory(Request $request) {
        try {
            $request_data = $request->only([
                'length',
                'start',
                'search'
            ]);

            // for pagination
            $page = ($request_data['start'] / $request_data['length']) + 1;
            $searchFilter = $request_data['search'];

            $inventory_product_id = Inventory::where('user_id', Auth::id())->get()->toArray();
            $inventory_product_id  = array_column($inventory_product_id, 'product_id');
            $products = Product::where('user_id', Auth::id())
            ->whereNotIn('id', $inventory_product_id)
            ->where(function ($q) use ($searchFilter) {
                $q->where('id', $searchFilter)
                    ->orWhere('name', 'LIKE', '%' . ($searchFilter) . '%');
                })
            ->paginate($request_data['length'], ['*'], 'page', $page);
            $this->response_message['status'] = 'success';
            $this->response_message['message'] = 'Products retrieved.';
            $this->response_message['result'] = $products;

            return response()->json($this->response_message, 200);
        } catch (\Exception $e) {
            report($e);
            $this->response_message['message'] = $e->getMessage();
        }

        return response()->json($this->response_message, 500);
    }
}
