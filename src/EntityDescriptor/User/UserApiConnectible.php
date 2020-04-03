<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\EntityDescriptor\User;

interface UserApiConnectible {
	
	function getAccessToken();
	
	static function getByAccessToken($token);
	
	static function getByEmail($email);
	
}
