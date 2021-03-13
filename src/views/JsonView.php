<?php

namespace EnioLotero\Discographies\Views;

use \Psr\Http\Message\ResponseInterface as Response;

class JsonView {
	private $logger;
	
	public function __construct($logger){
		$this->logger = $logger;
	}
	
	public function makeResponse(
		Response $response,
		$data = [],
		string $message = 'OK',
		int $httpStatus = 200
	){
		$response = $response->withStatus($httpStatus);
		$response = $response->withHeader('Content-Type', 'application/json');
        
        $json = "";
        
		if ($data !== []){
            $json = str_replace("\\", '', json_encode($data, JSON_PRETTY_PRINT));
		}
	
		if (json_last_error() !== JSON_ERROR_NONE){
			$this->logger->error("Error encoding JSON: ".json_last_error_msg(), $data);
			throw new \Exception('JSON error: '.json_last_error_msg());
		}
		
		$response = $response->write($json);
		
		return $response;
	}

	public function returnError(
		Response $response,
		string $action,
		string $method,
		string $message,
		int $httpStatus,
		string $details = ""
	) {
        $this->logger->error($action, [
            'method' => $method,
            'response' => $response,
            'message' => $message,
			'httpStatus' => $httpStatus,
			'details' => $details
        ]);

        return $this->makeResponse($response, [], $message, $httpStatus);
	}

	public function makeImplicitResponse(
		Response $response = null,
		$data = [],
		string $message = 'OK',
		int $httpStatus = 200
	) {
		return $this->makeResponse($response ?? $this->response, $data, $message, $httpStatus);
	}

	public function returnImplicitError(
		string $message,
		int $httpStatus,
		string $details = "",
		Response $response = null,
		string $action = null,
		string $method = null
	) {
		return $this->returnError(
			$response ?? $this->response,
			$action ?? $this->action,
			$method ?? $this->method,
			$message,
			$httpStatus,
			$details
		);
	}

	public function returnImplicitForbidden(string $message) {
		return $this->returnImplicitError(
			$message,
			403
		);
	}

	public function returnImplicitInvalid(string $message) {
		return $this->returnImplicitError(
			$message,
			400
		);
	}

	public function returnNotFound(Response $response) {
		return $this->makeResponse($response, [], 'Resource not found.', 404);
	}

	public function returnImplicitNotFound() {
		return $this->returnNotFound($this->response);
	}

	public function setResponse(Response $response){
		$this->response = $response;
		return $this;
	}

	public function setMethod(string $method){
		$this->method = $method;
		return $this;
	}

	public function setAction(string $action){
		$this->action = $action;
		return $this;
	}
}

?>