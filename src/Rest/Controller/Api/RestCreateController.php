<?php
/**
 * Created by Florent HAZARD on 31/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\Exception\DuplicateException;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JsonHttpResponse;

/**
 * Class RestCreateController
 */
class RestCreateController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 */
	public function run($request): JsonHttpResponse {
		$output = $request->getParameter('output', 'all');
		$input = $request->getInput();
		$route = $request->getRoute();
		$routeOptions = $route->getOptions();
		if( !empty($routeOptions['owner_field']) && !empty($this->parent) ) {
			$input[$routeOptions['owner_field']] = $this->parent->id();
		}
		try {
			$itemId = $this->entityService->createItem($input);
			$item = $this->entityService->loadItem($itemId);
		} catch( DuplicateException $exception ) {
			$item = $exception->getDuplicate();
			$this->entityService->updateItem($item, $input);
		}
		
		$data = $this->entityService->extractPublicArray($item, $output);
		
		return new JsonHttpResponse($data);
	}
	
}
