<?php
/**
 * Created by Florent HAZARD on 29/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\EntityService;
use Orpheus\EntityDescriptor\PermanentEntity;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Exception\ForbiddenException;
use Orpheus\InputController\HTTPController\HTTPRequest;
use Orpheus\Publisher\Exception\FieldNotFoundException;

/**
 * Class EntityRestController
 *
 * @package Orpheus\Rest\Controller\Api
 */
abstract class EntityRestController extends RestController {
	
	/**
	 * @var EntityService
	 */
	protected $entityService;
	
	/**
	 * @var PermanentEntity
	 */
	protected $item;
	
	/**
	 * @var AbstractUser
	 */
	protected $filterUser;
	
	/**
	 * @var string
	 */
	protected $pathItemId = 'itemId';
	
	/**
	 * @param HTTPRequest $request
	 * @return null
	 * @throws ForbiddenException
	 * @throws FieldNotFoundException
	 */
	public function preRun($request) {
		parent::preRun($request);
		
		$route = $request->getRoute();
		$options = $route->getOptions();
		if( !empty($options['rights']) ) {
			$checkOwner = false;
			$allowed = false;
			foreach( $options['rights'] as $right ) {
				if( $right === 'owner' ) {
					$checkOwner = true;
				} elseif( AbstractUser::loggedCanAccessToRoute($route->getName(), $right) ) {
					// Role
					$allowed = true;
					break;
				}
			}
			if( !$allowed && $checkOwner ) {
				// Not filtered by role
				// So, we allow it to access having result filtered
				$this->filterUser = $this->user;
				$allowed = true;
			}
			if( !$allowed ) {
				throw new ForbiddenException('Forbidden access to route');
			}
		}
		
		$this->entityService = new EntityService($options['entity']);
		
		$itemId = $request->getPathValue($this->pathItemId);
		if( !$itemId && !empty($options['source']) ) {
			$itemId = call_user_func(strtok($options['source'], '('));
		}
		
		if( $itemId ) {
			$this->item = $this->entityService->loadItem($itemId);
		}
		
		if( $this->filterUser && $this->item->getValue($options['owner_field']) == $this->filterUser->id() ) {
			throw new ForbiddenException('Forbidden access to route');
		}
		
		return null;
	}
}
