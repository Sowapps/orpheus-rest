<?php
/**
 * Created by Florent HAZARD on 29/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\EntityDescriptor\Service\EntityService;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Exception\ForbiddenException;
use Orpheus\Exception\NotFoundException;
use Orpheus\InputController\ControllerRoute;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\Publisher\Exception\FieldNotFoundException;

/**
 * Class EntityRestController
 */
abstract class EntityRestController extends RestController {
	
	protected EntityService $entityService;
	
	protected ?PermanentEntity $item = null;
	
	protected ?PermanentEntity $parent = null;
	
	protected ?AbstractUser $filterUser = null;
	
	protected string $pathItemId = 'itemId';
	protected ?string $activePermissionRole = null;
	protected array|string|null $activePermissionOptions = null;
	
	/**
	 * @param HttpRequest $request
	 * @throws ForbiddenException
	 * @throws FieldNotFoundException
	 */
	public function preRun($request): null {
		parent::preRun($request);
		
		$route = $request->getRoute();
		$routeOptions = (object) $route->getOptions();
		
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
			$aliasCallback = strtok($routeOptions->alias->source, '(');
			if( $aliasCallback === '{AuthenticatedUser}' ) {
				$this->item = $this->user;
			} else {
				$itemId = call_user_func($aliasCallback);
			}
		}
		
		if( $itemId ) {
			$this->item = $this->entityService->loadItem($itemId);
		}
		
		[$allowed, $permissionRole, $permissionOptions] = $this->checkRights($route, $routeOptions);
		
		if( !$allowed ) {
			$requireAdvancedRoles = $permissionRole;// ["role"=>"roleOptions"]
			foreach( $requireAdvancedRoles as $advPermissionRole => $advPermissionOptions ) {
				$valid = $this->authenticateAdvancedRole($routeOptions, $request, $advPermissionRole);
				if( $valid ) {
					$permissionRole = $advPermissionRole;
					$permissionOptions = $advPermissionOptions;
					$allowed = true;
					break;
				}
			}
		}
		
		if( !$allowed ) {
			// Not allowed and no advancedRoles
			throw new ForbiddenException('Forbidden access to route');
		}
		
		$this->activePermissionRole = $permissionRole;
		$this->activePermissionOptions = $permissionOptions;
		
		return null;
	}
	
	public function checkRights(ControllerRoute $route, object $options): array {
		$advancedRoles = [];
		if( !empty($options->permissions) ) {
			$userRoles = AbstractUser::getUserRoles();
			foreach( $options->permissions as $permissionRole => $permissionOptions ) {
				if( isset($userRoles[$permissionRole]) ) {
					// Is a valid classic user role ?
					if( AbstractUser::loggedCanAccessToRoute($route->getName(), $permissionRole) ) {
						// At least one valid role allowing access is required
						// Then custom roles are ignored
						return [true, $permissionRole, $permissionOptions];
					}
				} else {
					// Custom role handled after route is loaded
					$advancedRoles[$permissionRole] = $permissionOptions;
				}
			}
		}
		
		return [false, $advancedRoles, null];
	}
	
	public function authenticateAdvancedRole(object $options, HttpRequest $request, string $role): bool {
		if( $role === 'owner' ) {
			if( $this->item instanceof AbstractUser ) {
				return $this->item->equals($this->user);
			}
			return $this->user && $this->user->equals($this->entityService->getOwner($this->item));
		}
		return false;
	}
	
}
