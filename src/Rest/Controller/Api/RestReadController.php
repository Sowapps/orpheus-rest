<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JSONHttpResponse;

/**
 * Class RestReadController
 */
class RestReadController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 */
	public function run($request): JSONHttpResponse {
		$output = $request->getParameter('output', 'all');
		
		$data = $this->entityService->extractPublicArray($this->item, $output);
		
		return new JSONHttpResponse($data);
	}
	
}
