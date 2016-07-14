<?php

namespace Slim\Handlers;

use User;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Check user is authenticated !
 */
class OptionMiddleware
{
    /**
     * Container
     * 
     * @var object
     */
    protected $container;

    /**
     * Constructor
     * 
     * @param container $container container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request  The most recent Request object
     * @param ResponseInterface      $response The most recent Response object
     * @param ResponseInterface      $next     callable next  
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, Callable $next)
    {
        if ($request->getMethod() == 'OPTIONS') {

            $server  = $request->getServerParams();
            // $headers = $request->getHeaders();

            // // var_dump($headers);
            // var_dump($server);
            // die;

            // if (isset($server['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            //     $method  = $server['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
            //     $request = $request->withMethod($method);
            // }
                     // = $server['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];

//             $request = $request
//                 ->withAddedHeader('Access-Control-Allow-Origin', '*')
//                 ->withAddedHeader('Access-Control-Allow-Credentials', true)
//                 ->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With')
//                 ->withAddedHeader('Access-Control-Allow-Headers', 'Auth')
//                 ->withAddedHeader('Access-Control-Allow-Headers', 'Content-Type')
//                 ->withAddedHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE, PUT');


//             // if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
//             //     if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && (   
//             //        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST' || 
//             //        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'DELETE' || 
//             //        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'PUT' )) {

//             //       }
//             // }



// // http://stackoverflow.com/questions/9559947/cross-origin-authorization-header-with-jquery-ajax

// // Access-Control-Allow-Origin: *
// // Access-Control-Allow-Methods: GET, POST, PUT, DELETE
// // Access-Control-Allow-Headers: Authorization

//             $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*')
//                 ->withAddedHeader('Access-Control-Allow-Headers', 'Auth'); //Allow JSON data to be consumed


                // , X-Requested-With, X-authentication, X-client'
        }

        return $next($request, $response);
    }
}