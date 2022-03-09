<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;

/**
 * Class RestDeleteController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestDeleteController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param HttpRequest $request
	 * @return HttpResponse
	 */
	public function run($request): HttpResponse {
		
		$this->entityService->deleteItem($this->item);
		
		return new HttpResponse();
	}
	
}
