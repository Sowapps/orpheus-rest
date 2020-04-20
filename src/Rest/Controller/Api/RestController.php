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
	
	/** @var int */
	protected static $authenticatedUserId;
	
	/**
	 * @param HTTPRequest $request
	 * @return null
	 */
	public function preRun($request) {
		
		$_SESSION['USER_ID'] = null;
		
		// Authenticated user
		$headers = $request->getHeaders();
		$token = null;
		if( !empty($headers[self::HEADER_ALT_AUTHORIZATION]) || !empty($headers[self::HEADER_AUTHORIZATION]) ) {
			$authHeader = !empty($headers[self::HEADER_ALT_AUTHORIZATION]) ? $headers[self::HEADER_ALT_AUTHORIZATION] : $headers[self::HEADER_AUTHORIZATION];
			[, $token] = explodeList(' ', $authHeader, 2);
		} elseif( $request->hasParameter('aat') ) {
			$token = $request->getParameter('aat');
		}
		if( $token ) {
			/** @var UserApiConnectible $userClass */
			$userClass = AbstractUser::getUserClass();
			$this->user = $userClass::getByAccessToken($token);
			// Compatibility with all user system
			if( $this->user ) {
				$this->user->login(true);
				static::$authenticatedUserId = $this->user->id();
			}
		}
		
		return null;
	}
	
	/**
	 * Get current user access level
	 * If anonymous, the access is -1
	 *
	 * @return int
	 */
	public function getUserAccess() {
		return $this->user ? intval($this->user->accesslevel) : -1;
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
	
	/**
	 * @return int
	 */
	public static function getAuthenticatedUserId() {
		return self::$authenticatedUserId;
	}
}
