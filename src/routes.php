<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/v1/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Discographies API v1");

    return $response;
});

$app->get('/v1/callback', function (Request $request, Response $response, array $args) {
    $output = "Did something";
    
    $response->getBody()->write($output);

    return $response;
});

$app->get('/v1/albums', function (Request $request, Response $response, array $args) {
    $output = "No artist selected";

    // Will do search only if GET param 'q' is set
    if (isset($_GET['q'])) {
        $artist = $_GET['q'];
        
        // STEP 1: Get Token if necessary
        $fileread = file_get_contents('data');

        $contents = explode(':', $fileread);

        $readDate = date('Y/m/d h:i:s', $contents[0]);
        $now = date('Y/m/d h:i:s', time());

        $getNewToken = true;

        //if ($now < $readDate) {
        if (time() < $contents[0]) {
            //echo "<br>still works<br>";
            //echo $now . "<br>";
            //echo $readDate . "<br>";
            $getNewToken = false;
        } else {
            //echo "<br>need new token<br>";
            //echo $now . "<br>";
            //echo $readDate . "<br>";
        }

        $settings = $this->get('settings');

        //if ($getNewToken) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

            $rawCredentials = $settings['spotify']['ClientID'] . ":" . $settings['spotify']['SecretKey'];
            $encodedCredentials = base64_encode($rawCredentials);
            $headers = array();
            $headers[] = 'Authorization: Basic ' . $encodedCredentials;
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $rawData = curl_exec($ch);
            if (curl_errno($ch)) {
                //echo 'Error:' . curl_error($ch);
            } else {
                $spotifyData = json_decode($rawData);

                $expireTime = time() + $spotifyData->{'expires_in'};

                $token = $spotifyData->{'token_type'} . " " . $spotifyData->{'access_token'};
                
                $file = file_put_contents('data' , $expireTime . ":" . $token);

                //var_dump($file);
            }

            curl_close($ch);
        //}

        // STEP 2: Search artist's albums

        //$q = "artist:" . $artist . "%20album:";

        // GET SEARCH
        $ch = curl_init();
        //'Authorization': 'Bearer ' + $accessToken;
        curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/search?type=album&q=artist:" . urlencode($artist));
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result   
    
        $headers = array();
        $headers[] = 'Authorization: ' . $token;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Fetch and return content, save it.
        $rawData = curl_exec($ch);
        curl_close($ch);

        $jsonData = json_decode($rawData, true);
        $allAlbums = $jsonData['albums']['items'];

        $albums = array();
        $album = array();

        for ($i=0; $i < count($allAlbums); $i++) {
            $album = [
                "name" => $allAlbums[$i]['name'],
                "released" => $allAlbums[$i]["release_date"], // FORMATEAR A "10-10-2010",
                "tracks" => $allAlbums[$i]["total_tracks"],
                "cover" => [
                    "height" => $allAlbums[$i]["images"][0]["height"],
                    "width" => $allAlbums[$i]["images"][0]["width"],
                    "url" => $allAlbums[$i]["images"][0]["url"]
                ]
            ];
            $albums[] = $album;
        }
    
        $output = str_replace("\\", '', json_encode($albums, JSON_PRETTY_PRINT));
    }

    // STEP 3
    $response->getBody()->write($output);

    return $response;
});

