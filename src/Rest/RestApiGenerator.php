<?php
/**
 * Created by Florent HAZARD on 27/01/2018
 */

namespace Orpheus\Rest;

use Exception;
use Orpheus\Config\YAML\YAML;
use Orpheus\InputController\HTTPController\HTTPRoute;

/**
 * Class RestApiGenerator
 *
 * @package Orpheus\Rest
 */
class RestApiGenerator {
	
	const RIGHT_LIST = 'list';
	const RIGHT_CREATE = 'create';
	const RIGHT_READ = 'read';
	const RIGHT_UPDATE = 'update';
	const RIGHT_DELETE = 'delete';
	
	/**
	 * @var array
	 */
	private static $rights = [self::RIGHT_LIST, self::RIGHT_CREATE, self::RIGHT_READ, self::RIGHT_UPDATE, self::RIGHT_DELETE];
	
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
		$this->entityActions['list'] = new RestRouteGenerator(HTTPRoute::METHOD_GET, 'Orpheus\Rest\Controller\Api\RestListController');
		$this->entityActions['create'] = new RestRouteGenerator(HTTPRoute::METHOD_POST, 'Orpheus\Rest\Controller\Api\RestCreateController');
		$this->itemActions = [];
		$this->itemActions['read'] = new RestRouteGenerator(HTTPRoute::METHOD_GET, 'Orpheus\Rest\Controller\Api\RestReadController');
		$this->itemActions['update'] = new RestRouteGenerator(HTTPRoute::METHOD_PUT, 'Orpheus\Rest\Controller\Api\ApiInterestRateListController');
		$this->itemActions['delete'] = new RestRouteGenerator(HTTPRoute::METHOD_DELETE, 'Orpheus\Rest\Controller\Api\RestDeleteController');
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
				$api->entities[$aliasConfig->entity], $aliasConfig->source);
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
					$entityConfig->owner_field = 'user_id';
				}
				// Format rights fields
				foreach( static::$rights as $key ) {
					if( !isset($entityConfig->$key) ) {
						// Initialize right to none
						$entityConfig->$key = [];
					} elseif( !is_array($entityConfig->$key) ) {
						// Convert implicit string to array of string
						$entityConfig->$key = [$entityConfig->$key];
					}
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
					$outsiderConfig->method = [HTTPRoute::METHOD_GET];
					
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
	 * @param string|null $aliasSource
	 */
	public function generateEntityRoutes(array &$routes, $api, $routePrefix, $entityPath, $entityConfig, $aliasSource = null) {
		$itemOnly = !!$aliasSource;
		
		if( !$itemOnly ) {
			$entityResPath = $api->endpoint . sprintf($this->getEntityPath(), $entityPath);
			foreach( $this->getEntityActions() as $actionKey => $action ) {
				$routes[$routePrefix . $actionKey] = $this->generateRoute($actionKey, $action, $entityResPath, $entityConfig, $aliasSource);
			}
		}
		
		$entityItemPath = $api->endpoint . sprintf($itemOnly ? $this->getEntityPath() : $this->getFullItemPath(), $entityPath);
		
		foreach( $this->getItemActions() as $actionKey => $action ) {
			$routes[$routePrefix . $actionKey] = $this->generateRoute($actionKey, $action, $entityItemPath, $entityConfig, $aliasSource);
		}
		
		foreach( $entityConfig->children as $childKey => $childConfig ) {
			$childEntityConfig = $api->entities[$childKey];
			$childPath = $entityItemPath . sprintf($this->getEntityPath(), $childEntityConfig->path);
			foreach( $this->getEntityActions() as $actionKey => $action ) {
				$routes[$routePrefix . $childKey . '_' . $actionKey] = $this->generateRoute($actionKey, $action, $childPath, $childEntityConfig, $aliasSource);
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
	 * @param string $aliasSource
	 * @return array
	 */
	public function generateRoute($actionKey, $action, $path, $entityConfig, $aliasSource) {
		$route = $action->generate();
		$route['path'] = $path;
		$route['entity'] = $entityConfig->class;
		$route['rights'] = $entityConfig->$actionKey;
		$route['owner_field'] = $entityConfig->owner_field;
		if( $aliasSource ) {
			$route['source'] = $aliasSource;
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
