<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JSONHttpResponse;

/**
 * Class RestUpdateController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestUpdateController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 * @return JSONHttpResponse
	 */
	public function run($request): JSONHttpResponse {
		$output = $request->getParameter('output', 'all');
		$input = $request->getInput();
		
		$this->entityService->updateItem($this->item, $input);
		
		$data = $this->entityService->extractPublicArray($this->item, $output);
		
		return new JSONHttpResponse($data);
	}
	
}
