<?php
use Illuminate\Support\Facades\Storage;
use App\Events\NewOrderEvent;
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
    // event(new NewOrderEvent('testing side'));
    return $router->app->version();
});

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/createBuyer', 'UserController@createBuyer');
    $router->group(['prefix' => 'consumer'], function() use($router) {
        $router->get('search', 'ConsumerController@searchStoreByProductOrName');
        $router->post('products', 'ConsumerController@getAllAvailableProductByStoreId');
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
            $router->get('shop/{id}', 'UserController@getUserShop');
            $router->post('getall', 'UserController@getall');
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
            $router->delete('remove/{product_id}', 'InventoryController@removeProduct');
        });

        //orders
        $router->group(['prefix' => 'orders'], function() use($router) {
            $router->get('getOrder/{order_id}', 'OrderController@getOrderById');
            $router->post('', 'OrderController@getOrdersByMerchantUserId');
            $router->post('add', 'OrderController@addOrder');
            $router->post('move', 'OrderController@changeStatus');
            $router->post('upload/{id}', 'OrderController@upload');
            $router->post('getOrders', 'OrderController@getOrders');
        });

         //dashboard
        $router->group(['prefix' => 'dashboard'], function() use($router) {
            $router->post('getTotalCollectable', 'DashboardController@getTotalCollectableAmount');
            $router->post('getTotalCollected', 'DashboardController@getTotalCollectedAmount');
            $router->get('getActiveMerchantCount', 'DashboardController@getActiveMerchantCount');
            $router->get('getNumberOfCompletedOrders', 'DashboardController@getNumberOfCompletedOrders');
        });

        //Weekly Payment report
        $router->group(['prefix' => 'weeklypayment'], function() use ($router) {
            $router->post('', 'WeeklyPaymentController@index');
            $router->post('send', 'WeeklyPaymentController@create');
            $router->post('approveByMerchant/{id}', 'WeeklyPaymentController@approveByMerchant');
            $router->get('approveByAdmin/{id}', 'WeeklyPaymentController@approveByAdmin');
            $router->get('{id}', 'WeeklyPaymentController@getPendingWeeklyPaymentByMerchantId');
        });

        //Report Controller
        $router->group(['prefix' => 'reports'], function() use ($router) {
            $router->post('/salesReport', 'ReportController@salesReport');
            $router->post('/eodReport', 'ReportController@eodReport');
        });

        $router->group(['prefix' => 'pusher'], function() use ($router) {
            $router->post('trigger', 'PusherController@trigger');
        });

    });
});
