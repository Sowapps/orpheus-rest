<?php
/**
 * Created by Florent HAZARD on 31/01/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\HTTPController\JSONHTTPResponse;
use Orpheus\InputController\InputRequest;
use Orpheus\InputController\OutputResponse;

/**
 * Class RestCreateController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestCreateController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param InputRequest $request
	 * @return OutputResponse|null
	 */
	public function run($request) {
		
		$input = $request->getInput();
		
		$itemId = $this->entityService->createItem($input);
		
		$item = $this->entityService->loadItem($itemId);
		
		$data = $this->entityService->extractPublicArray($item);
		
		return new JSONHTTPResponse($data);
	}
}
