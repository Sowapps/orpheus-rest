<?php
/**
 * Created by Florent HAZARD on 04/02/2018
 */

namespace Orpheus\Rest\Controller;

use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\EntityDescriptor\User\UserApiConnectible;
use Orpheus\Exception\ForbiddenException;
use Orpheus\InputController\HTTPController\JSONHTTPResponse;
use Orpheus\InputController\InputRequest;
use Orpheus\InputController\OutputResponse;
use Orpheus\Rest\Controller\Api\RestController;

/**
 * Class UserLoginRestController
 *
 * @package Orpheus\Rest\Controller
 */
class UserLoginRestController extends RestController {
	
	/**
	 * @param InputRequest $request
	 * @return JSONHTTPResponse|OutputResponse|null
	 * @throws ForbiddenException
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
