<?php
/**
 * Created by Florent HAZARD on 29/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\Config\Config;
use Orpheus\EntityDescriptor\EntityService;
use Orpheus\EntityDescriptor\PermanentEntity;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Exception\ForbiddenException;
use Orpheus\Exception\NotFoundException;
use Orpheus\InputController\ControllerRoute;
use Orpheus\InputController\HttpController\HttpRequest;
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
	protected EntityService $entityService;
	
	/**
	 * @var PermanentEntity|null
	 */
	protected ?PermanentEntity $item = null;
	
	/**
	 * @var PermanentEntity|null
	 */
	protected ?PermanentEntity $parent = null;
	
	/**
	 * @var AbstractUser|null
	 */
	protected ?AbstractUser $filterUser = null;
	
	/**
	 * @var string
	 */
	protected string $pathItemId = 'itemId';
	
	/**
	 * @param HttpRequest $request
	 * @return null
	 * @throws ForbiddenException
	 * @throws FieldNotFoundException
	 */
	public function preRun($request) {
		parent::preRun($request);
		
		$route = $request->getRoute();
		$routeOptions = (object) $route->getOptions();
		
		$allowed = $this->checkRights($route, $routeOptions, $request);
		if( !$allowed ) {
			// Not allowed and no advancedRoles
			throw new ForbiddenException('Forbidden access to route');
		}
		$advancedRoles = $allowed;
		unset($allowed);
		
		if( !empty($routeOptions->parent) ) {
			$parentConfig = (object) $routeOptions->parent;
			$parentId = $request->getPathValue($parentConfig->pathItemId);
			if( !$parentId ) {
				if( empty($parentConfig->alias) ) {
					throw new NotFoundException(sprintf('Parent %s not found', $parentConfig->pathItemId));
				}
				$parentId = call_user_func(strtok($parentConfig->alias->source, '('));
				if( !$parentId ) {
					throw new NotFoundException(sprintf('Parent alias %s not found', $parentConfig->alias->key));
				}
			}
			$parentService = new EntityService($parentConfig->class);
			$this->parent = $parentService->loadItem($parentId);
		}
		
		$this->entityService = new EntityService($routeOptions->entity);
		
		$itemId = $request->getPathValue($this->pathItemId);
		if( !$itemId && !empty($routeOptions->alias) ) {
			$itemId = call_user_func(strtok($routeOptions->alias->source, '('));
		}
		
		if( $itemId ) {
			$this->item = $this->entityService->loadItem($itemId);
		}
		
		foreach( $advancedRoles as $role ) {
			$this->checkAdvancedRole($routeOptions, $request, $role);
		}
		
		return null;
	}
	
	public function checkRights(ControllerRoute $route, object $options, HttpRequest $request) {
		$advancedRoles = [];
		if( !empty($options->rights) ) {
			$userRoles = Config::get('user_roles');
			//			$checkOwner = false;
			//			$allowed = false;
			foreach( $options->rights as $right ) {
				if( isset($userRoles[$right]) ) {
					// Is right a classic user role ?
					if( AbstractUser::loggedCanAccessToRoute($route->getName(), $right) ) {
						// At least one valid role allowing access is required
						// Then custom roles are ignored
						return true;
						//						$advancedRoles = [];
						//						break;
					}
				} else {
					// Custom role handled after route is loaded
					$advancedRoles[] = $right;
				}
			}
			//			if( $checkOwner && $this->user ) {
			//				// Owner allowed to access this route only if results are filtered
			//				$this->filterUser = $this->user;
			//				$allowed = true;
			//			}
			//			if( !$allowed ) {
			//				throw new ForbiddenException('Forbidden access to route');
			//			}
		}
		
		return $advancedRoles;
	}
	
	public function checkAdvancedRole(object $options, HttpRequest $request, string $role) {
	}
	
}
