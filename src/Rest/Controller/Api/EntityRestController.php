<?php
/**
 * Created by Florent HAZARD on 29/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\EntityService;
use Orpheus\EntityDescriptor\PermanentEntity;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Exception\ForbiddenException;
use Orpheus\Exception\NotFoundException;
use Orpheus\InputController\ControllerRoute;
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
	 * @var PermanentEntity
	 */
	protected $parent;
	
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
		$routeOptions = (object) $route->getOptions();
		
		$this->checkRights($route, $routeOptions, $request);
		
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
		
		$this->checkOwner($routeOptions, $request);
		
		return null;
	}
	
	public function checkRights(ControllerRoute $route, $options, $request) {
		if( !empty($options->rights) ) {
			$checkOwner = false;
			$allowed = false;
			foreach( $options->rights as $right ) {
				if( $right === 'owner' ) {
					$checkOwner = true;
				} elseif( AbstractUser::loggedCanAccessToRoute($route->getName(), $right) ) {
					// Role
					$allowed = true;
					break;
				}
			}
			if( $checkOwner && $this->user ) {
				// Owner allowed to access this route only if results are filtered
				$this->filterUser = $this->user;
				$allowed = true;
			}
			if( !$allowed ) {
				throw new ForbiddenException('Forbidden access to route');
			}
		}
	}
	
	public function checkOwner($options, $request) {
		if( $this->filterUser && $this->item->getValue($options->owner_field) !== $this->filterUser->id() ) {
			throw new ForbiddenException('Forbidden access to route');
		}
	}
}
