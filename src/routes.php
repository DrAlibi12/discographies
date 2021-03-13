<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/v1/albums', 'SpotifyController:getAlbumsFromArtist');


?>
