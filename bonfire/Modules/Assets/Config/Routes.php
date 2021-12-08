<?php

namespace Bonfire\Assets\Config;

$routes->get('assets/(:any)', '\Bonfire\Assets\Controllers\AssetController::serve/$1');
