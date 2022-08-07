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
    $router->post('/test', 'AuthController@test');
    $router->group(['prefix' => 'consumer'], function() use($router) {
        $router->get('search', 'ConsumerController@searchStoreByProductOrName');
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
            $router->delete('{id}', 'UserController@delete');
        });

        // Product
        $router->group(['prefix' => 'product'], function() use($router) {
            $router->get('{id}', 'ProductController@get');
            $router->post('', 'ProductController@create');
            $router->post('list', 'ProductController@index');
            $router->post('upload/{id}', 'ProductController@upload');
            $router->post('getProductsForInventory', 'ProductController@getProductsForInventory');
            $router->put('', 'ProductController@update');
            $router->delete('{id}', 'ProductController@delete');
        });

         // Inventory
         $router->group(['prefix' => 'inventory'], function() use($router) {
            $router->post('list', 'InventoryController@index');
        });
    });
});
