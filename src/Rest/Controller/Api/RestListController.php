<?php
/**
 * Created by Florent HAZARD on 31/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\PermanentEntity;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JSONHttpResponse;

/**
 * Class RestListController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestListController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 * @return JSONHttpResponse
	 */
	public function run($request): JSONHttpResponse {
		$output = $request->getParameter('output', 'all');
		
		$query = $this->entityService
			->getSelectQuery($request->getParameter('filter'))
			->asObjectList();
		
		$data = [];
		foreach( $query as $item ) {
			/* @var PermanentEntity $item */
			$data[$item->id()] = $this->entityService->extractPublicArray($item, $output);
		}
		
		return new JSONHttpResponse($data);
	}
	
}
