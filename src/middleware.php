<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$middlewares['example'] = function ($request, $response, $next)
{
    $response->getBody()->write('BEFORE');
    $response = $next($request, $response);
    $response->getBody()->write('AFTER');

    return $response;
};
/*
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
*/
$app->add(function ($request, $response, $next) {
    $finalResponse = $next($request, $response);
    return $finalResponse
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET');
});
