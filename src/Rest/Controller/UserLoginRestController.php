<?php
/**
 * Created by Florent HAZARD on 04/02/2018
 */

namespace Orpheus\Rest\Controller;

use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\EntityDescriptor\User\UserApiConnectible;
use Orpheus\Exception\ForbiddenException;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\Rest\Controller\Api\RestController;

/**
 * Class UserLoginRestController
 */
class UserLoginRestController extends RestController {
	
	/**
	 * @param HttpRequest $request
	 */
	public function run($request): HttpResponse {
		$userEmail = $request->getInputValue('email');
		/** @var UserApiConnectible|AbstractUser $userClass */
		$userClass = AbstractUser::getUserClass();
		/** @var UserApiConnectible|AbstractUser $user */
		$user = $userClass::getByEmail($userEmail);
		if( !$user ) {
			throw new ForbiddenException($userClass::text('invalidAuthenticationUser'));
		}
		if( $user->password !== hashString($request->getInputValue('password')) ) {
			throw new ForbiddenException($userClass::text('invalidAuthenticationPassword'));
		}
		if( !$user->published ) {
			throw new ForbiddenException($userClass::text('disabledUser'));
		}
		
		return $this->renderOutput(['accesstoken' => $user->getAccessToken()]);
	}
	
}
