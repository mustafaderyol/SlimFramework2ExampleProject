<?php

$app->post(
    '/insert',
    function ($request, $response) use ($container) {

        $advertisement = new Model\Advertisement($container);
        
        $data = $request->getParsedBody();

        if (empty($data)) {
            return $response->withJson(
                [
                    'success' => 0,
                    'messages' => [
                        "Post body cannot be empty."
                    ]
                ],
                200
            );
        }

        $result = $advertisement->insert($data);

        if (is_array($result) && isset($result['error'])) {
            return $response->withJson(
                ['success' => 0, 'messages' => [$result['error']]],
                200
            );
        }
        return $response->withJson(['success' => 1, '_id' => $result], 200);
    }
);

$app->get(
    '/getAll/{limit}/{ofset}',
    function ($request, $response, $args = null) use ($container) {

        $comment  = new Model\Advertisement($container);
        $result = $comment->getAll($args['limit'],$args['ofset']);

        $results = [
            'success' => ($result) ? 1 : 0,
            'results' => $result
        ];
        return $response->withJson($results, 200);
    }
);

$app->get(
    '/getAllCount',
    function ($request, $response, $args = null) use ($container) {

        $comment  = new Model\Advertisement($container);
        $result = $comment->getAllCount();

        $results = [
            'success' => ($result) ? 1 : 0,
            'count' => $result
        ];
        return $response->withJson($results, 200);
    }
);

$app->get(
    '/getById/{id}',
    function ($request, $response, $args = null) use ($container) {

        $adv  = new Model\Advertisement($container);
        $result = $adv->getById($args['id']);

        $results = [
            'success' => ($result) ? 1 : 0,
            'results' => $result
        ];
        return $response->withJson($results, 200);
    }
);



$app->put(
    '/update',
    function ($request, $response) use ($container) {

        $comment = new Model\Advertisement($container);
        $data = $request->getParsedBody();

        if (empty($data['id'])) {
            return $response->withJson(
                [
                    'success' => 0,
                    'messages' => [
                        "Field comment_id required."
                    ]
                ],
                200
            );
        }
        if (! $comment->update($data, $data['id'])) {
            return $response->withJson(
                [
                    'success'  => 0,
                    'messages' => $container->get('validator')->getMessages(),
                    'errors'   => $container->get('validator')->getErrors()
                ],
                200
            );
        }
        return $response->withJson(['success' => 1], 200);
    }
);

$app->delete(
    '/remove/{id}',
    function ($request, $response, $args) use ($container) {

        $posts = new Model\Advertisement($container);

        if (empty($args['id'])) {
            return $response->withJson(
                ['success' => 0, 'messages' => ["Field id required."]],
                200
            );
        }
        if (! $posts->remove($args['id'])) {
            return $response->withJson(
                [
                    'success'  => 0,
                    'messages' => ["Post not found."]
                ],
                200
            );
        }
        return $response->withJson(['success' => 1], 200);
    }
);