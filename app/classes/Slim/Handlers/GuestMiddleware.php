<?php

namespace Slim\Handlers;

use User;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Check user is authenticated !
 */
class GuestMiddleware
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
        // Validate JWT token if not valid token we show a authenticate error.

        $token = false;
        if ($jwt = (string)$request->getHeaderLine('Auth')) {

            $secretKey = $this->container['config']['jwt']['key'];

            try {

                $path = $request->getUri()->getPath();

                /**
                 * Decode jwt
                 */
                $token = \Firebase\JWT\JWT::decode(
                    $jwt,
                    $secretKey, 
                    [$this->container['config']['jwt']['algorithm']]
                );
                $this->container['jwt_user_data'] = (array)$token->data[0];  // Set user data to container

                $container = $this->container;
                /**
                 * Create user class in container
                 */
                $this->container['user'] = function () use ($container) {
                    return new User($container);
                };
                
                $checkPermission = $this->container['user']->hasPermission($path);

                /**
                 * Check permissions
                 */
                if (! $checkPermission) {

                    $validator = $this->container['validator'];
                    $validator->setMessage('You don\'t have permission.');

                    return $response->withJson(
                        [
                            'success' => 0,
                            'messages' => $validator->getMessages(),
                            'role_name' => $this->container['jwt_user_data']['role_name'],
                            'path' => $request->getUri()->getPath()
                        ],
                        200
                    );
                }
                /**
                 * Db configurations
                 */
                $config = $container['db_config'];

                if ($path == '/api/restricted/post/addLike') {
                    if ((int)$config->get('document_like') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Add like method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if ($path == '/api/restricted/post/removeLike') {
                    if ((int)$config->get('document_like') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Add like method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if ($path == '/api/restricted/comment/add') {
                    if ((int)$config->get('document_add_comment') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Add comment method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if ($path == '/api/restricted/comment/update') {
                    if ((int)$config->get('document_update_comment') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Add comment method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if (strpos($path, '/api/restricted/comment/remove') === 0) {
                    if ((int)$config->get('document_remove_comment') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Remove comment method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if ($path == '/api/restricted/comment/addLike') {
                    if ((int)$config->get('comment_like') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Comment like method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if ($path == '/api/restricted/comment/removeLike') {
                    if ((int)$config->get('comment_like') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Comment like method disabled from mongo config."]], 
                            200
                        );
                    }
                }
                if (strpos($path, '/api/restricted/notification') === 0) {
                    if ((int)$config->get('site_notification') != 1) {
                        return $response->withJson(
                            ['success' => 0, 'messages' => ["Notifications disabled from mongo config."]], 
                            200
                        );
                    }
                }

            } catch (Exception $e) {
                return $response->withJson(['success' => 0, 'message' => $e->getMessage()], 200);
            }
        }
        if (is_object($token)) {  // Authorized user
            return $next($request, $response);
        }
        return $response->withJson(['success' => 0,'message' => "Not Authorized."], 200);
    }
}