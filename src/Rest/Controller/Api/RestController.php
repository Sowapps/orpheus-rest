<?php
/**
 * Created by Florent HAZARD on 04/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Exception\UserException;
use Orpheus\InputController\HttpController\HttpController;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\InputController\HttpController\JsonHttpResponse;
use Orpheus\Service\SecurityService;
use Throwable;

/**
 * Class RestController
 */
abstract class RestController extends HttpController {
	
	const HEADER_AUTHORIZATION = 'Authorization';
	const HEADER_ALT_AUTHORIZATION = 'X-Auth';
	
	protected static ?int $authenticatedUserId = null;
	
	protected ?AbstractUser $user = null;
	
	/**
	 * @param HttpRequest $request
	 */
	public function preRun($request): null {
		$this->user = SecurityService::get()->getActiveUser();
		
		return null;
	}
	
	/**
	 * Get current user access level
	 * If anonymous, the access is -1
	 */
	public function getUserAccess(): int {
		return $this->user ? intval($this->user->accesslevel) : -1;
	}
	
	public function renderOutput($data): JsonHttpResponse {
		return new JsonHttpResponse($data);
	}
	
	public function processException(Throwable $exception, $values = []): HttpResponse {
		return JsonHttpResponse::generateFromException($exception);
	}
	
	public function processUserException(UserException $exception, $values = []): HttpResponse {
		return JsonHttpResponse::generateFromUserException($exception, $values);
	}
	
	public static function getAuthenticatedUserId(): ?int {
		return self::$authenticatedUserId;
	}
	
}
