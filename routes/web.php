<?php
use Illuminate\Support\Facades\Storage;
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/createBuyer', 'UserController@createBuyer');
    $router->group(['prefix' => 'consumer'], function() use($router) {
        $router->get('search', 'ConsumerController@searchStoreByProductOrName');
        $router->get('products/{store_id}', 'ConsumerController@getAllAvailableProductByStoreId');
        $router->get('track/{order_id}', 'OrderController@trackOrder');

    });
    $router->group(['middleware' => 'auth'], function() use($router) {
        // Auth
        $router->group(['prefix' => 'auth'], function() use($router) {
            $router->get('', 'AuthController@me');
            $router->get('logout', 'AuthController@logout');
        });
        // User
        $router->group(['prefix' => 'user'], function() use($router) {
            $router->get('getRole', 'UserController@getRole');
            $router->get('{id}', 'UserController@get');
            $router->get('', 'UserController@me');
            $router->post('list', 'UserController@index');
            $router->post('', 'UserController@create');
            $router->post('update', 'UserController@update');
            $router->post('upload/{id}', 'UserController@upload');
            $router->delete('{id}', 'UserController@delete');
        });

        // Product
        $router->group(['prefix' => 'product'], function() use($router) {
            $router->get('{id}', 'ProductController@get');
            $router->post('', 'ProductController@create');
            $router->post('list', 'ProductController@index');
            $router->post('upload/{id}', 'ProductController@upload');
            $router->post('getProductsForInventory', 'ProductController@getProductsForInventory');
            $router->post('update', 'ProductController@update');
            $router->delete('{id}', 'ProductController@delete');
        });

         // Inventory
         $router->group(['prefix' => 'inventory'], function() use($router) {
            $router->post('list', 'InventoryController@index');
            $router->post('add/{product_id}', 'InventoryController@addProduct');
            $router->post('modifyQuantity', 'InventoryController@changeQuantity');
            $router->post('add', 'InventoryController@addProductWithQuantity');
        });

        //orders
        $router->group(['prefix' => 'orders'], function() use($router) {
            $router->post('', 'OrderController@getOrdersByMerchantUserId');
            $router->post('add', 'OrderController@addOrder');
            $router->post('move', 'OrderController@changeStatus');
            $router->post('upload/{id}', 'OrderController@upload');
        });

    });
});
