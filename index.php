<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');

ini_set('display_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(
    function () {
        $error = error_get_last();
        if (! empty($error)) {
            echo $error['message']." File: ".$error['file']." Line : ".$error['line'];
        }
    }
);
require 'vendor/autoload.php';

use Validator\Validator;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$container = new \Slim\Container($configuration);
$app = new Slim\App($container);

$container['db_config'] = function () use ($container) {
    return new DbConfig($container);
};
$container['validator'] = function () use ($container) {
    return new Validator($container, $container->get('request'));
};
$container['filter'] = function () {
    return new Utils\Filter;
};
$container['database'] = function () {
    $mongo = new MongoClient("mongodb://10.0.0.1:27017");
    return $mongo->searchDbName;
};
$app->group(
    '/api',
    function () use ($app, $container) {

        /**
         * Advertisement
         */
        $app->group(
            '/advertisement',
            function () use ($app, $container) {
                include 'app/classes/Controller/Public/Advertisement.php';
            }
        );

    }
);

$app->run();