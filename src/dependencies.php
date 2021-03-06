<?php
// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

/***************************************************************************/

// json view
$container['JsonView'] = function ($c) {
	return new EnioLotero\Discographies\Views\JsonView($c->get('logger'));
};

// spotify controller
$container['SpotifyController'] = function ($c) {
    $settings = $c->get('settings')['spotify'];
    return new EnioLotero\Discographies\Controllers\SpotifyController(
		$c->get('logger'),
        $c->get('JsonView'),
        $settings['ClientID'],
        $settings['SecretKey']
	);
};

