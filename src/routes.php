<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/v1/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Discographies API v1");

    return $response;
});

$app->get('/v1/albums', function (Request $request, Response $response, array $args) {
    $output = "Coming soon.";

    if (isset($_GET['q'])) {
        $artist = $_GET['q'];
        $output = "More info about $artist coming soon.";
    }

    $response->getBody()->write($output);

    return $response;
});
