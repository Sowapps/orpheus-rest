<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @noinspection PhpComposerExtensionStubsInspection
 */

namespace Orpheus\Rest\Controller;

use Exception;
use Orpheus\InputController\HttpController\HttpController;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\InputController\HttpController\HttpRoute;
use Orpheus\Rest\RestApiGenerator;
use Orpheus\Rest\RestRouteGenerator;
use Orpheus\Rest\XmlHttpResponse;
use SimpleXMLElement;

/**
 * Class RestApiWadlController
 */
class RestApiWadlController extends HttpController {
	
	const ATTR_ID = 'id';
	const ATTR_NAME = 'name';
	const ATTR_STYLE = 'style';
	const ATTR_REQUIRED = 'required';
	const ATTR_PATH = 'path';
	const ATTR_TITLE = 'title';
	const ATTR_MEDIA_TYPE = 'mediaType';
	const ATTR_STATUS = 'status';
	const TAG_RESOURCE_LIST = 'resources';
	const TAG_RESOURCE = 'resource';
	const TAG_METHOD = 'method';
	const TAG_PARAM = 'param';
	const TAG_REQUEST = 'request';
	const TAG_RESPONSE = 'response';
	const TAG_DOC = 'doc';
	const TAG_REPRESENTATION = 'representation';
	const MIMETYPE_JSON = 'application/json';
	const ENDPOINT_URL = WEB_ROOT . 'api';
	
	private object $api;
	
	private string $entityResPath;
	
	private string $itemResPath;
	
	/**
	 * @var RestRouteGenerator[]
	 */
	private array $entityActions;
	
	/**
	 * @var RestRouteGenerator[]
	 */
	private array $itemActions;
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 * @return XmlHttpResponse
	 * @throws Exception
	 */
	public function run($request): HttpResponse {
		$xml = new SimpleXMLElement('<application/>');
		$xml->addAttribute('xmlns', 'http://wadl.dev.java.net/2009/02');
		$this->addTitle($xml, sprintf('%s API', t('app_name')));
		$resourceList = $xml->addChild(self::TAG_RESOURCE_LIST);
		
		$resourceList->addAttribute('base', self::ENDPOINT_URL);
		
		$apiGenerator = new RestApiGenerator();
		$this->api = $apiGenerator->getApiConfig();
		
		$this->entityResPath = $apiGenerator->getEntityPath();
		$this->itemResPath = $apiGenerator->getItemPath();
		$this->entityActions = $apiGenerator->getEntityActions();
		$this->itemActions = $apiGenerator->getItemActions();
		
		foreach( $this->api->entities as $entityKey => $entityConfig ) {
			$this->addAllEntityResources($resourceList, $entityKey, $entityConfig->path, $entityConfig->class, $entityConfig->children);
		}
		
		foreach( $this->api->aliases as $aliasKey => $aliasConfig ) {
			$entityConfig = $this->api->entities[$aliasConfig->entity];
			$this->addAllEntityResources($resourceList, $aliasKey, $aliasConfig->path, $entityConfig->class, $entityConfig->children, true);
		}
		
		foreach( $this->api->outsiders as $outsiderKey => $outsiderConfig ) {
			$this->addResource($resourceList, $outsiderKey, $outsiderConfig->path, $outsiderConfig->method, $this->convertKeyToName($outsiderKey));
		}
		
		return new XmlHttpResponse($xml->asXML());
	}
	
	protected function addTitle(SimpleXMLElement $xml, string $title): void {
		$element = $xml->addChild(self::TAG_DOC);
		$element->addAttribute('xml:lang', 'en', 'xml');
		$element->addAttribute(self::ATTR_TITLE, $title);
	}
	
	protected function addAllEntityResources(SimpleXMLElement $xml, string $entityKey, string $entityPath, string $class, array $children, bool $itemOnly = false): void {
		// Declare resource, needs key & path
		// Declare params, needs path (parsed)
		// Declare methods, needs key
		$entityName = ucfirst($entityKey);
		
		if( $itemOnly ) {
			$entityResource = $xml;
			$entityItemPath = sprintf($this->entityResPath, $entityPath);
			$itemName = $entityName;
			
		} else {
			$entityResPath = sprintf($this->entityResPath, $entityPath);
			$entityResource = $this->addEntityResource($xml, $entityKey, $entityResPath, $entityName);
			
			$entityItemPath = sprintf($this->itemResPath, $entityPath);
			$itemName = $entityName . ' Item';
		}
		
		// Item Resource - Metadata
		$itemKey = $this->concatSlug($entityKey, 'item');
		$resource = $entityResource->addChild(self::TAG_RESOURCE);
		$resource->addAttribute(self::ATTR_ID, $itemKey);
		// Calculate valid path for WADL, replacing {id:itemId} by {itemId}
		$resource->addAttribute(self::ATTR_PATH, preg_replace('#\{[^:]+:([^\}]+)\}#', '{$1}', $entityItemPath));
		$this->addTitle($resource, $itemName);
		preg_match_all('#\{(?:[^:]+:)?([^\}]+)\}#', $entityItemPath, $paramMatches, PREG_SET_ORDER);
		
		// Item Resource - Path Parameters
		foreach( $paramMatches as $paramMatch ) {
			$paramKey = $paramMatch[1];
			$param = $resource->addChild(self::TAG_PARAM);
			$param->addAttribute(self::ATTR_ID, $this->concatSlug($itemKey, strtolower($paramKey)));
			$param->addAttribute(self::ATTR_NAME, $paramKey);
			$param->addAttribute(self::ATTR_STYLE, 'template');
			$param->addAttribute(self::ATTR_REQUIRED, 'true');
		}
		
		// Item Resource - Actions
		foreach( $this->itemActions as $actionKey => $action ) {
			$actionName = ucfirst($actionKey);
			$method = $resource->addChild(self::TAG_METHOD);
			$method->addAttribute(self::ATTR_ID, $this->concatSlug($itemKey, $actionKey));
			$method->addAttribute(self::ATTR_NAME, $action->getMethod());
			$this->addTitle($method, $actionName);
			if( $action->getMethod() === HttpRoute::METHOD_PUT ) {
				// Only POST & PUT allow input
				$this->addJsonRepresentation($method->addChild(self::TAG_REQUEST));
			}
			
		}
		
		// Item Resource - Add children
		foreach( $children as $childKey => $childConfig ) {
			$this->addEntityResource($resource, $this->concatSlug($entityKey, $childKey), sprintf($this->entityResPath, $childConfig->path), $itemName . ' ' . ucfirst($childKey));
		}
		
	}
	
	protected function addEntityResource(SimpleXMLElement $xml, $entityKey, $entityPath, $entityName): ?SimpleXMLElement {
		$resource = $xml->addChild(self::TAG_RESOURCE);
		$resource->addAttribute(self::ATTR_ID, $entityKey);
		$resource->addAttribute(self::ATTR_PATH, $entityPath);
		$this->addTitle($resource, $entityName);
		
		foreach( $this->entityActions as $actionKey => $action ) {
			$actionName = ucfirst($actionKey);
			$method = $resource->addChild(self::TAG_METHOD);
			$method->addAttribute(self::ATTR_ID, $entityKey . '-' . $actionKey);
			$method->addAttribute(self::ATTR_NAME, $action->getMethod());
			$this->addTitle($method, $actionName);
			if( $action->getMethod() === HttpRoute::METHOD_POST ) {
				// Only POST & PUT allow input
				$this->addJsonRepresentation($method->addChild(self::TAG_REQUEST));
			}
			
		}
		
		return $resource;
	}
	
	protected function addJsonRepresentation(SimpleXMLElement $xml): void {
		$element = $xml->addChild(self::TAG_REPRESENTATION);
		$element->addAttribute(self::ATTR_MEDIA_TYPE, self::MIMETYPE_JSON);
	}
	
	protected function concatSlug(string $before, string $after): string {
		return $before . '-' . $after;
	}
	
	protected function addResource(SimpleXMLElement $xml, string $key, string $path, array $methods, string $name): ?SimpleXMLElement {
		$resource = $xml->addChild(self::TAG_RESOURCE);
		$resource->addAttribute(self::ATTR_ID, $key);
		$resource->addAttribute(self::ATTR_PATH, $path);
		$this->addTitle($resource, $name);
		
		// array('POST') or array('POST' => 'Create')
		foreach( $methods as $methodKey => $methodName ) {
			$methodKey = is_int($methodKey) ? $methodName : $methodKey;
			$method = $resource->addChild(self::TAG_METHOD);
			$method->addAttribute(self::ATTR_ID, $key . '-' . $methodKey);
			$method->addAttribute(self::ATTR_NAME, $methodKey);
			$this->addTitle($method, $methodName);
			if( in_array($methodKey, [HttpRoute::METHOD_POST, HttpRoute::METHOD_PUT], true) ) {
				// Only POST & PUT allow input
				$this->addJsonRepresentation($method->addChild(self::TAG_REQUEST));
			}
		}
		
		return $resource;
	}
	
	protected function convertKeyToName(string $key): string {
		return ucwords(str_replace(['api_', '_'], ['', ' '], $key));
	}
	
	protected function addResponse(SimpleXMLElement $xml, string $status): void {
		$element = $xml->addChild(self::TAG_RESPONSE);
		$element->addAttribute(self::ATTR_STATUS, $status);
		$this->addJsonRepresentation($element);
	}
	
	public function getApi(): object {
		return $this->api;
	}
	
}
