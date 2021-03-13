<?php

namespace EnioLotero\Discographies\Controllers;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use EnioLotero\Discographies\Views\JsonView;
use EnioLotero\Discographies\Models\AlbumModel;

class SpotifyController {

    private const tokenFile = __DIR__ . '/../../data';

    public function __construct(Logger $logger, JsonView $jsonView, string $clientID, string $secretKey) {
        $this->logger = $logger;
        $this->jsonView = $jsonView;
        $this->clientID = $clientID;
        $this->secretKey = $secretKey;
        $this->token = null;
    }

    public function validateParameters(array $parameters, array $keys) : array {
        $missingParameters = [];

        $getParameterGetter = function(array $a) {
            return function (string $key) use ($a) {
                if (!isset($a[$key])) {
                    return null;
                } else {
                    return $a[$key];
                }
            };
        };

        $getParameter = $getParameterGetter($parameters);

        for ($i=0; $i < count($keys); $i++) {
            if ($getParameter($keys[$i]) === null) {
                $missingParameters[count($missingParameters)] = $keys[$i];
            }
        }
        
        return $missingParameters;
    }

    public function getNewToken() : ?string {
        $token = null;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $rawCredentials = $this->clientID . ":" . $this->secretKey;
        $encodedCredentials = base64_encode($rawCredentials);
        $headers = array();
        $headers[] = 'Authorization: Basic ' . $encodedCredentials;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $rawData = curl_exec($ch);
        if (curl_errno($ch)) {
            // ERROR GETTING TOKEN
            $this->logger->error(__NAMESPACE__."\\SpotifyController: Error getting Spotify Token from https://accounts.spotify.com/api/token");
            $this->logger->error(__NAMESPACE__."\\SpotifyController: Error: " . curl_error($ch));
        } else {
            $data = json_decode($rawData, true);

            if (isset($data['error'])) {
                $this->logger->error(__NAMESPACE__."\\SpotifyController: Spotify returned error when trying to get token from https://accounts.spotify.com/api/token");
                $this->logger->error(__NAMESPACE__."\\SpotifyController: Response: " . json_encode($data));
            } else {
                $tokenType = $data['token_type'];
                $accessToken = $data['access_token'];
                $expiresIn = $data['expires_in'];

                $token = $tokenType . " " . $accessToken;

                $result = $this->saveToken($token, $expiresIn);
            }
        }

        curl_close($ch);

        return $token;
    }

    public function readToken() : ?string {
        $fileread = file_get_contents(self::tokenFile);

        $contents = explode(':', $fileread);

        $tokenDate = $contents[0];
        $now = time();

        // Validate if token is not expired
        $token = null;
        if ($now < $tokenDate) {
            $token = $contents[1];
        }
        
        return $token;
    }

    public function saveToken (string $token, int $expiresIn) : bool {
        $expireTime = time() + $expiresIn;

        $file = file_put_contents(self::tokenFile , $expireTime . ":" . $token);

        // Return false if file was not written
        if (!$file) {
            // COULDN'T SAVE TOKEN
            $this->logger->error(__NAMESPACE__."\\SpotifyController: Couldn't save token to file ".self::tokenFile);

            return false;
        }

        return true;

    }

    private function setToken(?string $token) {
        $this->token = $token;
    }

    public function getToken(bool $generateIfNull = false) : ?string {
        if ($this->token !== null || !$generateIfNull) return $this->token;
        
        // Tries to read a saved token
        if (file_exists(self::tokenFile)) {
            $this->setToken($this->readToken());
        }

        // If the file didn't exists or thetoken was expired,
        // claim new token
        if ($this->getToken() === null) {
            $this->setToken($this->getNewToken());
        }

        return $this->token;
    }

    public function getAlbumsFromArtist(Request $request, Response $response, array $args) {
        $method = strtoupper($request->getMethod());
        $action = 'Retrieving albums';
        $message = 'OK';
        $httpStatus = 200;

        $params = $request->getParams();

        $missingParameters = $this->validateParameters($params, ['q']);

        if ($missingParameters !== []) {
            $message = 'Invalid or missing parameters: '.implode(', ', $missingParameters);
            $httpStatus = 400;
            return $this->jsonView->returnImplicitError($response, $action, $method, $message, $httpStatus);
        }

        $albumModel = new AlbumModel($this->logger);

        $this->getToken(true);

        if ($this->token === null) {
            // REQUEST ERROR
            $message = "Internal server error";
            $httpStatus = 500;
            $errorDetail = __NAMESPACE__."\\SpotifyController: Couldn't get Spotify Token.";

            return $this->jsonView->returnError($response, $action, $method, $message, $httpStatus, $errorDetail);
        }

        $artist = $params['q'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/search?type=album&q=artist:" . urlencode($artist));
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result   
    
        $headers = array();
        $headers[] = 'Authorization: ' . $this->token;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Fetch and return content, save it.
        $rawData = curl_exec($ch);
        if (curl_errno($ch)) {
            // REQUEST ERROR
            $message = "Internal server error";
            $httpStatus = 500;
            $errorDetail = __NAMESPACE__."\\SpotifyController: Error requesting albums. Error: " . curl_error($ch);

            return $this->jsonView->returnError($response, $action, $method, $message, $httpStatus, $errorDetail);
        } else {
            curl_close($ch);
            
            $data = json_decode($rawData, true);

            if (isset($data['error'])) {
                // ERROR RETRIEVING ALBUMS
                $message = "Internal server error";
                $httpStatus = 500;
                $errorDetail = __NAMESPACE__."\\SpotifyController: Error getting albums. Response: " . json_encode($data);

                return $this->jsonView->returnError($response, $action, $method, $message, $httpStatus, $errorDetail);
            } else {
                $rawAlbums = $data['albums']['items'];
                $albums = $albumModel->mapRawResponseData($rawAlbums);
            }
        }

        return $this->jsonView->makeResponse($response, $albums, $message, $httpStatus);

    }

}

?>