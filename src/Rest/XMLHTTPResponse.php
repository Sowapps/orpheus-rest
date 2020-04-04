<?php

namespace Orpheus\Rest;

use Exception;
use Orpheus\Exception\UserException;
use Orpheus\Exception\UserReportsException;
use Orpheus\InputController\HTTPController\HTTPResponse;

/**
 * Class XMLHTTPResponse
 *
 * @package Orpheus\Rest
 */
class XMLHTTPResponse extends HTTPResponse {
	
	/**
	 * The data of the XML response
	 *
	 * @var string
	 */
	protected $data;
	
	/**
	 * Constructor
	 *
	 * @param array $data
	 */
	public function __construct($data = null) {
		parent::__construct(null, 'application/xml');
		$this->setData($data);
	}
	
	public function run() {
		echo $this->data;
	}
	
	/**
	 * Get the data
	 *
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Set the data
	 *
	 * @param mixed $data
	 * @return XMLHTTPResponse
	 */
	public function setData($data) {
		$this->data = $data;
		return $this;
	}
	
	/**
	 * Get a response with the given $data
	 *
	 * @param mixed $data
	 * @return XMLHTTPResponse
	 * @see XMLHTTPResponse::render()
	 */
	public static function returnData($data) {
		// Return success with data
		$response = new static();
		$response->data = $data;
		return $response;
	}
	
	/**
	 * Generate HTMLResponse from Exception
	 *
	 * @param Exception $exception
	 * @param string $action
	 * @return XMLHTTPResponse
	 */
	public static function generateFromException(Exception $exception, $action = 'Handling the request') {
		$code = $exception->getCode();
		if( $code < 100 ) {
			$code = HTTP_INTERNAL_SERVER_ERROR;
		}
		$other = new stdClass();
		$other->code = $exception->getCode();
		$other->message = $exception->getMessage();
		$other->file = $exception->getFile();
		$other->line = $exception->getLine();
		$other->trace = $exception->getTrace();
		$response = static::render('exception', $other, 'global', t('fatalErrorOccurred', 'global'));
		$response->setCode($code);
		return $response;
	}
	
	/**
	 * Render the given data
	 *
	 * @param string $textCode
	 * @param mixed $other
	 * @param string $domain
	 * @param string $description
	 * @return XMLHTTPResponse
	 * @see XMLHTTPResponse::returnData()
	 *
	 * We recommend to use returnData() to return data, that is more RESTful and to use this method only for errors
	 */
	public static function render($textCode, $other = null, $domain = 'global', $description = null) {
		$response = new static();
		$response->collectFrom($textCode, $other, $domain, $description);
		return $response;
	}
	
	/**
	 * @param string $textCode
	 * @param mixed $other
	 * @param string $domain
	 * @param string $description
	 * @see HTTPResponse::collectFrom()
	 */
	public function collectFrom($textCode, $other = null, $domain = 'global', $description = null) {
	}
	
	/**
	 * Generate HTMLResponse from UserException
	 *
	 * @param UserException $exception
	 * @param array $values
	 * @return XMLHTTPResponse
	 */
	public static function generateFromUserException(UserException $exception, $values = []) {
		$code = $exception->getCode();
		if( !$code ) {
			$code = HTTP_BAD_REQUEST;
		}
		if( $exception instanceof UserReportsException ) {
			/* @var $exception UserReportsException */
			$response = static::render($exception->getMessage(), $exception->getReports(), $exception->getDomain());
		} else {
			$response = static::render($exception->getMessage(), null, $exception->getDomain());
		}
		$response->setCode($code);
		return $response;
	}
	
}
