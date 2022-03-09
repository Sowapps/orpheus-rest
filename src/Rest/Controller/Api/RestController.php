<?php
/**
 * Created by Florent HAZARD on 04/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\EntityDescriptor\User\UserApiConnectible;
use Orpheus\Exception\UserException;
use Orpheus\InputController\HttpController\HttpController;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\InputController\HttpController\JSONHttpResponse;
use Throwable;

/**
 * Class RestController
 *
 * @package Orpheus\Rest\Controller\Api
 */
abstract class RestController extends HttpController {
	
	const HEADER_AUTHORIZATION = 'Authorization';
	const HEADER_ALT_AUTHORIZATION = 'X-Auth';
	
	/** @var int|null */
	protected static ?int $authenticatedUserId = null;
	
	/** @var AbstractUser|UserApiConnectible|null */
	protected ?AbstractUser $user = null;
	
	/**
	 * @param HttpRequest $request
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
	public function getUserAccess(): int {
		return $this->user ? intval($this->user->accesslevel) : -1;
	}
	
	public function renderOutput($data): JSONHttpResponse {
		return new JSONHttpResponse($data);
	}
	
	public function processException(Throwable $exception, $values = []): HttpResponse {
		return JSONHttpResponse::generateFromException($exception);
	}
	
	public function processUserException(UserException $exception, $values = []): HttpResponse {
		return JSONHttpResponse::generateFromUserException($exception, $values);
	}
	
	/**
	 * @return int
	 */
	public static function getAuthenticatedUserId(): ?int {
		return self::$authenticatedUserId;
	}
	
}
