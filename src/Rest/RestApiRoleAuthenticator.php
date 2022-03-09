<?php
/**
 * Created by Florent HAZARD on 27/01/2018
 */

namespace Orpheus\Rest;

use Orpheus\EntityDescriptor\PermanentEntity;
use Orpheus\EntityDescriptor\User\AbstractUser;

/**
 * Class RestApiGenerator
 *
 * @package Orpheus\Rest
 */
class RestApiRoleAuthenticator {
	
	protected ?AbstractUser $user = null;
	
	protected ?PermanentEntity $item = null;
	
	public function __construct() {
		$this->user = AbstractUser::getLoggedUser();
	}
	
	public function allowRole(string $role, object $options): bool {
		if( $role === 'owner' ) {
			return $this->user && $this->item && $this->item->getValue($options->owner_field) === $this->user->id();
		}
		
		return false;
	}
	
}
