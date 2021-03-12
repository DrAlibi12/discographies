<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Spotify Settings
        'spotify' => [
            'ClientID' => 'ea7e6185497a494197020834c9019806',
            'SecretKey' => 'ed99650b4baf496a980366df28370105',
		],
		
	],
];