<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HTTPController\JSONHTTPResponse;
use Orpheus\InputController\InputRequest;

/**
 * Class RestReadController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestReadController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param InputRequest $request
	 * @return JSONHTTPResponse
	 */
	public function run($request): HttpResponse {
		$output = $request->getParameter('output', 'all');
		
		$data = $this->entityService->extractPublicArray($this->item, $output);
		
		return new JSONHTTPResponse($data);
	}
}
