<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$loader = new Twig_Loader_Array();
$twig = new Twig_Environment($loader, array(
));

$app->post('/render', function (Request $request) use ($app, $twig) {
    $data = json_decode($request->getContent(), true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        return new JsonResponse(
            [
                'error' => 'json_parse_error',
                'description' => json_last_error_msg()
            ],
            400
        );
    }

    $template = $data['files'][$data['template']];

    return new Response(
        $twig->createTemplate($template)->render($data['parameters']),
        200,
        [
            'Content-type' => 'text/plain'
        ]
    );
});

$app->run();