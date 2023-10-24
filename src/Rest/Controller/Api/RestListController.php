<?php
/**
 * Created by Florent HAZARD on 31/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JsonHttpResponse;

/**
 * Class RestListController
 */
class RestListController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 */
	public function run($request): JsonHttpResponse {
		$output = $request->getParameter('output', 'all');
		
		$query = $this->entityService
			->getSelectQuery($request->getParameter('filter'))
			->asObjectList();
		
		$data = [];
		foreach( $query as $item ) {
			/* @var PermanentEntity $item */
			$data[$item->id()] = $this->entityService->extractPublicArray($item, $output);
		}
		
		return new JsonHttpResponse($data);
	}
	
}
