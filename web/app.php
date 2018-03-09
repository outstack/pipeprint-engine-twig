<?php

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$debug = in_array(
    getenv('APP_DEBUG'),
    [1, true, 'true', 'yes']
);

$app->error(function (\Exception $e) use ($debug) {
    $problemData = [
        'title' => "Unhandled exception",
        'detail' => $e->getMessage()
    ];
    if ($debug) {
        $problemData['trace'] = $e->getTrace();
    }
    return new JsonResponse($problemData, 500, ['Content-type' => 'application/problem+json']);
});

$app->post('/render', function (Request $request) use ($app) {
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

    $dir = sys_get_temp_dir() . "/" . uniqid("", true);
    mkdir($dir);

    $tmpFs = new Filesystem(new \League\Flysystem\Adapter\Local($dir));

    foreach ($data['files'] as $file => $contents) {
        $tmpFs->write($file, $contents);
    }

    $twig = (new Twig_Environment(new Twig_Loader_Filesystem([$dir], $dir)));
    $rendered = $twig->render(
        $data['template'],
        $data['parameters']
    );

    foreach ($data['files'] as $file => $contents) {
        $tmpFs->delete($file);
    }
    foreach ($tmpFs->listContents('', true) as ['path' => $path]) {
        $tmpFs->deleteDir($path);
    }

    unset($tmpFs);
    rmdir($dir);

    return new Response(
        $rendered,
        200,
        [
            'Content-type' => 'text/plain'
        ]
    );
});

$app->run();