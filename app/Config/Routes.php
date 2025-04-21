<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], function ($routes) {
  $routes->resource('users', [
    'controller' => 'Users',
    'only' => ['index', 'show', 'create', 'update', 'delete']
  ]);

  // OR alternatively:
  /*
    $routes->get('users', 'Users::index');
    $routes->get('users/(:segment)', 'Users::show/$1');
    $routes->post('users', 'Users::create');
    $routes->put('users/(:segment)', 'Users::update/$1');
    $routes->delete('users/(:segment)', 'Users::delete/$1');
    */
});
