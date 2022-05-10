<?php

$routes->get('assets/(:any)', '\Bonfire\Assets\Controllers\AssetController::serve/$1');
