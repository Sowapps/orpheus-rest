<?php
/**
 * Created by Florent HAZARD on 27/01/2018
 */

namespace Orpheus\Rest;

use Exception;
use Orpheus\Config\YAML\YAML;
use Orpheus\InputController\HttpController\HttpRoute;

/**
 * Class RestApiGenerator
 *
 * @package Orpheus\Rest
 */
class RestApiGenerator {
	
	/**
	 * @var RestRouteGenerator[]
	 */
	private $entityActions;
	
	/**
	 * @var RestRouteGenerator[]
	 */
	private $itemActions;
	
	/**
	 * @var string
	 */
	private $routePrefix = 'api_';
	
	/**
	 * @var string
	 */
	private $entityPath;
	
	/**
	 * RestApiGenerator constructor.
	 */
	public function __construct($entityPath = '/%s') {
		$this->entityPath = $entityPath;
		$this->entityActions = [];
		$this->entityActions['list'] = new RestRouteGenerator(HttpRoute::METHOD_GET, 'Orpheus\Rest\Controller\Api\RestListController');
		$this->entityActions['create'] = new RestRouteGenerator(HttpRoute::METHOD_POST, 'Orpheus\Rest\Controller\Api\RestCreateController');
		$this->itemActions = [];
		$this->itemActions['read'] = new RestRouteGenerator(HttpRoute::METHOD_GET, 'Orpheus\Rest\Controller\Api\RestReadController');
		$this->itemActions['update'] = new RestRouteGenerator(HttpRoute::METHOD_PUT, 'Orpheus\Rest\Controller\Api\RestUpdateController');
		$this->itemActions['delete'] = new RestRouteGenerator(HttpRoute::METHOD_DELETE, 'Orpheus\Rest\Controller\Api\RestDeleteController');
	}
	
	public function getAllActions() {
		return array_merge(array_keys($this->entityActions), array_keys($this->itemActions));
	}
	
	/**
	 * @param string|null $endpoint
	 * @return array
	 * @throws Exception
	 */
	public function getRoutes($endpoint = null) {
		$api = $this->getApiConfig();
		
		if( $endpoint !== null ) {
			$api->endpoint = $endpoint;
		}
		
		$routes = [];
		
		// Loop on entities to extract base data
		foreach( $api->entities as $entityKey => $entityConfig ) {
			$this->generateEntityRoutes($routes, $api, $this->routePrefix . $entityKey . '_', $entityConfig->path, $entityConfig);
		}
		
		// Loop on entities to extract base data
		foreach( $api->aliases as $aliasKey => $aliasConfig ) {
			$this->generateEntityRoutes($routes, $api, $this->routePrefix . $aliasKey . '_', $aliasConfig->path,
				$api->entities[$aliasConfig->entity], (object) [
					'key'    => $aliasKey,
					'source' => $aliasConfig->source,
				]);
		}
		
		return ['http' => $routes];
	}
	
	/**
	 * @return object
	 * @throws Exception
	 */
	public function getApiConfig() {
		$config = (object) $this->getRawConfig();
		// Check & Format well config
		if( !$config->endpoint ) {
			throw new Exception('Endpoint not defined');
		}
		if( $config->entities ) {
			foreach( $config->entities as $entityKey => &$entityConfig ) {
				$entityConfig = (object) $entityConfig;
				if( !isset($entityConfig->path) ) {
					// Use implicit key as path
					$entityConfig->path = $entityKey;
				}
				if( !$entityConfig->class || !class_exists($entityConfig->class, true) ) {
					throw new Exception('Invalid class provided for entity ' . $entityKey);
				}
				if( !isset($entityConfig->owner_field) ) {
					$entityConfig->owner_field = 'create_user_id';
				}
				// Format rights fields
				foreach( $this->getAllActions() as $key ) {
					if( !isset($entityConfig->$key) ) {
						// Not-provided config is initialized to empty
						$entityConfig->$key = ['roles' => []];
					} elseif( is_string($entityConfig->$key) ) {
						// lone string is a role
						$entityConfig->$key = ['roles' => [$entityConfig->$key]];
					} elseif( is_array($entityConfig->$key) && isset($entityConfig->$key[0]) ) {
						// List of roles
						$entityConfig->$key = ['roles' => $entityConfig->$key];
					} // Else well-formatted array
					$entityConfig->$key = (object) $entityConfig->$key;
				}
				// Format Children
				if( !isset($entityConfig->children) ) {
					$entityConfig->children = [];
				}
				foreach( $entityConfig->children as $childKey => &$childConfig ) {
					if( !is_array($childConfig) ) {
						$childConfig = [
							'filter' => [
								$childConfig => [],
							],
						];
					}
					$childConfig = (object) $childConfig;
					if( !isset($childConfig->path) ) {
						// Use implicit key as path
						$childConfig->path = $childKey;
					}
					foreach( $childConfig->filter as $filterKey => &$filterConfig ) {
						$filterConfig = (object) $filterConfig;
						if( !isset($filterConfig->parent_field) ) {
							$filterConfig->parent_field = 'id';
						}
						if( !isset($filterConfig->child_field) ) {
							$filterConfig->child_field = $filterKey;
						}
						if( isset($filterConfig->via) ) {
							if( !$filterConfig->via->class || !class_exists($filterConfig->via->class, true) ) {
								throw new Exception('Invalid class provided for filter ' . $filterKey . ' in entity ' . $entityKey);
							}
							if( !$filterConfig->via->child_field ) {
								throw new Exception('Invalid child_field provided for filter ' . $filterKey . ' in entity ' . $entityKey);
							}
							if( !$filterConfig->via->parent_field ) {
								$filterConfig->via->parent_field = 'id';
							}
						}
					}
				}
				if( !isset($entityConfig->path_item_id) ) {
					$entityConfig->path_item_id = $entityConfig->children ? $entityKey . 'Id' : 'itemId';
				}
			}
		} else {
			$config->entities = [];
		}
		if( $config->aliases ) {
			foreach( $config->aliases as $aliasKey => &$aliasConfig ) {
				$aliasConfig = (object) $aliasConfig;
				if( !isset($aliasConfig->path) ) {
					// Use implicit key as path
					$aliasConfig->path = $aliasKey;
				}
				if( !$aliasConfig->entity || !$config->entities[$aliasConfig->entity] ) {
					throw new Exception('Invalid entity provided for alias ' . $aliasKey);
				}
				if( !$this->isValidCallable($aliasConfig->source) ) {
					throw new Exception('Invalid source provided for alias ' . $aliasKey);
				}
			}
		} else {
			$config->aliases = [];
		}
		if( $config->outsiders ) {
			foreach( $config->outsiders as $outsiderKey => &$outsiderConfig ) {
				$outsiderConfig = (object) $outsiderConfig;
				if( !isset($outsiderConfig->path) ) {
					throw new Exception('Invalid path provided for outsider ' . $outsiderKey);
				}
				if( !isset($outsiderConfig->method) ) {
					$outsiderConfig->method = [HttpRoute::METHOD_GET];
					
				} elseif( $outsiderConfig->method ) {
					$outsiderConfig->method = explode('|', $outsiderConfig->method);
				}
			}
		} else {
			$config->outsiders = [];
		}
		return $config;
	}
	
	/**
	 * @return array
	 * @throws Exception
	 */
	public function getRawConfig() {
		return YAML::buildFrom(null, 'rest-api', true)->asArray();
	}
	
	/**
	 * @param callable $callable
	 * @return bool
	 */
	protected function isValidCallable($callable) {
		if( !$callable ) {
			return false;
		}
		$callable = preg_replace('#([^\(+])\(.*#', '$1', $callable);
		return is_callable($callable);
	}
	
	/**
	 * @param array $routes
	 * @param object $api
	 * @param string $routePrefix
	 * @param string $entityPath
	 * @param object $entityConfig
	 * @param string|null $alias
	 */
	public function generateEntityRoutes(array &$routes, $api, $routePrefix, $entityPath, $entityConfig, $alias = null) {
		$itemOnly = !!$alias;
		
		if( !$itemOnly ) {
			$entityResPath = $api->endpoint . sprintf($this->getEntityPath(), $entityPath);
			foreach( $this->getEntityActions() as $actionKey => $action ) {
				$routes[$routePrefix . $actionKey] = $this->generateRoute($actionKey, $action, $entityResPath, $entityConfig, $alias, null);
			}
		}
		
		$entityItemPath = $api->endpoint . sprintf($itemOnly ? $this->getEntityPath() : $this->getFullItemPath(), $entityPath);
		
		foreach( $this->getItemActions() as $actionKey => $action ) {
			$routes[$routePrefix . $actionKey] = $this->generateRoute($actionKey, $action, $entityItemPath, $entityConfig, $alias, null);
		}
		
		foreach( $entityConfig->children as $childKey => $childConfig ) {
			$childEntityConfig = (object) array_merge((array) $api->entities[$childKey], (array) $childConfig);
			$childPath = $entityItemPath . sprintf($this->getEntityPath(), $childEntityConfig->path);
			foreach( $this->getEntityActions() as $actionKey => $action ) {
				$routes[$routePrefix . $childKey . '_' . $actionKey] = $this->generateRoute($actionKey, $action, $childPath, $childEntityConfig, null, [
					'class'      => $entityConfig->class,
					'pathItemId' => $entityConfig->path_item_id,
					'alias'      => $alias,
				]);
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function getEntityPath() {
		return $this->entityPath;
	}
	
	/**
	 * @return RestRouteGenerator[]
	 */
	public function getEntityActions() {
		return $this->entityActions;
	}
	
	/**
	 * @param string $actionKey
	 * @param RestRouteGenerator $action
	 * @param string $path
	 * @param object $entityConfig
	 * @param string $alias
	 * @return array
	 */
	public function generateRoute($actionKey, $action, $path, $entityConfig, $alias, $parent) {
		$route = $action->generate();
		$route['path'] = $path;
		$route['entity'] = $entityConfig->class;
		$route['parent'] = $parent;
		$route['rights'] = $entityConfig->$actionKey->roles;
		$route['owner_field'] = $entityConfig->owner_field;
		if( isset($entityConfig->$actionKey->controller) ) {
			$route['controller'] = $entityConfig->$actionKey->controller;
		}
		if( isset($entityConfig->update_on_duplicate) ) {
			$route['update_on_duplicate'] = $entityConfig->update_on_duplicate;
		}
		if( $alias ) {
			$route['alias'] = $alias;
		}
		return $route;
	}
	
	/**
	 * @return string
	 */
	public function getFullItemPath() {
		return $this->entityPath . $this->getItemPath();
	}
	
	/**
	 * @return string
	 */
	public function getItemPath() {
		return '/{id:itemId}';
	}
	
	/**
	 * @return RestRouteGenerator[]
	 */
	public function getItemActions() {
		return $this->itemActions;
	}
}
