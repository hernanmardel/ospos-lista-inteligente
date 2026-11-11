<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// The module menu entry links to /{module_id}.
$routes->get('listainteligente', '\App\Plugins\ListaInteligentePlugin\Controllers\ListaInteligenteController::getIndex');
// POST: sends data to an external service, so it stays behind the CSRF filter.
$routes->post('listainteligente/compare', '\App\Plugins\ListaInteligentePlugin\Controllers\ListaInteligenteController::postCompare');

$routes->get('plugins/listainteligente/icon', '\App\Plugins\ListaInteligentePlugin\Controllers\IconController::getIcon');
