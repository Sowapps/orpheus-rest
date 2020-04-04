<?php
/**
 * Created by Florent HAZARD on 04/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Exception;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\EntityDescriptor\User\UserApiConnectible;
use Orpheus\Exception\UserException;
use Orpheus\InputController\HTTPController\HTTPController;
use Orpheus\InputController\HTTPController\HTTPRequest;
use Orpheus\InputController\HTTPController\JSONHTTPResponse;

/**
 * Class RestController
 *
 * @package Orpheus\Rest\Controller\Api
 */
abstract class RestController extends HTTPController {
	
	const HEADER_AUTHORIZATION = 'Authorization';
	const HEADER_ALT_AUTHORIZATION = 'X-Auth';
	
	/** @var AbstractUser|UserApiConnectible */
	protected $user;
	
	/**
	 * @param HTTPRequest $request
	 * @return null
	 */
	public function preRun($request) {
		
		// Authenticated user
		$headers = $request->getHeaders();
		if( !empty($headers[self::HEADER_ALT_AUTHORIZATION]) || !empty($headers[self::HEADER_AUTHORIZATION]) ) {
			$authHeader = !empty($headers[self::HEADER_ALT_AUTHORIZATION]) ? $headers[self::HEADER_ALT_AUTHORIZATION] : $headers[self::HEADER_AUTHORIZATION];
			[, $token] = explodeList(' ', $authHeader, 2);
			/** @var UserApiConnectible $userClass */
			$userClass = AbstractUser::getUserClass();
			$this->user = $userClass::getByAccessToken($token);
			// Compatibility with all user system
			if( $this->user ) {
				$this->user->login(true);
			}
		}
		
		return null;
	}
	
	public function renderOutput($data) {
		return new JSONHTTPResponse($data);
	}
	
	public function processException(Exception $exception, $values = []) {
		return JSONHTTPResponse::generateFromException($exception);
	}
	
	public function processUserException(UserException $exception, $values = []) {
		return JSONHTTPResponse::generateFromUserException($exception, $values);
	}
}
