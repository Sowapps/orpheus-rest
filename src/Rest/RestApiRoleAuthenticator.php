<?php
/**
 * Created by Florent HAZARD on 27/01/2018
 */

namespace Orpheus\Rest;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\EntityDescriptor\User\AbstractUser;

/**
 * Class RestApiGenerator
 */
class RestApiRoleAuthenticator {
	
	protected ?AbstractUser $user = null;
	
	protected ?PermanentEntity $item = null;
	
	public function __construct() {
		$this->user = AbstractUser::getActiveUser();
	}
	
	public function allowRole(string $role, object $options): bool {
		if( $role === 'owner' ) {
			return $this->user && $this->item && $this->item->getValue($options->owner_field) === $this->user->id();
		}
		
		return false;
	}
	
}
