<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JsonHttpResponse;

/**
 * Class RestUpdateController
 */
class RestUpdateController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 */
	public function run($request): JsonHttpResponse {
		$output = $request->getParameter('output', 'public');
		$input = $request->getInput();
		
		$this->entityService->updateItem($this->item, $input);
		
		$data = $this->entityService->extractPublicArray($this->item, $output);
		
		return new JsonHttpResponse($data);
	}
	
}
