<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\JsonHttpResponse;

/**
 * Class RestReadController
 */
class RestReadController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 */
	public function run($request): JsonHttpResponse {
		$output = $request->getParameter('output', 'all');
		if( is_string($this->activePermissionOptions) ) {
			// Restricted by configuration
			$output = $this->activePermissionOptions;
		}
		
		$data = $this->entityService->extractPublicArray($this->item, $output);
		
		return new JsonHttpResponse($data);
	}
	
}
