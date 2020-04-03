<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HTTPController\JSONHTTPResponse;
use Orpheus\InputController\InputRequest;
use Orpheus\InputController\OutputResponse;

/**
 * Class RestUpdateController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestUpdateController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param InputRequest $request
	 * @return OutputResponse|null
	 */
	public function run($request) {
		
		$input = $request->getInput();
		
		$this->entityService->updateItem($this->item, $input);
		
		$data = $this->entityService->extractPublicArray($this->item);
		
		return new JSONHTTPResponse($data);
	}
}
