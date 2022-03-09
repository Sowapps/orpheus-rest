<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\EntityDescriptor\User;

interface UserApiConnectible {
	
	function getAccessToken(): string;
	
	static function getByAccessToken(string $token): AbstractUser;
	
	static function getByEmail(string $email): AbstractUser;
	
}
