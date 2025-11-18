<?php

/**
 * Web Routes
 *
 * Define your application routes here.
 * Routes are loaded by the Application class.
 */

use Core\Routing\Router;

$router = app(Router::class);

// Example routes

// Home page
$router->get('/', function () {
    return view('welcome');
});

// Twig test page
$router->get('/test', function () {
    return view('test', [
        'message' => 'Twig is working perfectly!'
    ]);
});

// Simple closure route
$router->get('/hello', function () {
    return 'Hello, World!';
});

// Route with parameters
$router->get('/user/{id}', function ($id) {
    return "User ID: " . e($id);
});

// Route with optional parameters
$router->get('/post/{id}/comment/{commentId?}', function ($id, $commentId = null) {
    if ($commentId) {
        return "Post {$id}, Comment {$commentId}";
    }
    return "Post {$id}";
});

// Controller route example
// $router->get('/users', 'UserController@index');
// $router->get('/users/{id}', 'UserController@show');
// $router->post('/users', 'UserController@store');
// $router->put('/users/{id}', 'UserController@update');
// $router->delete('/users/{id}', 'UserController@destroy');

// Route with middleware
// $router->get('/dashboard', 'DashboardController@index')->middleware('auth');

// Route groups with prefix
// $router->group(['prefix' => 'api'], function ($router) {
//     $router->get('/users', 'Api\UserController@index');
//     $router->post('/users', 'Api\UserController@store');
// });

// API route group with middleware
// $router->group(['prefix' => 'api/v1', 'middleware' => 'api'], function ($router) {
//     $router->get('/products', 'Api\ProductController@index');
//     $router->post('/products', 'Api\ProductController@store');
// });

// JSON response example
$router->get('/api/status', function () {
    return ['status' => 'ok', 'message' => 'API is running'];
});
