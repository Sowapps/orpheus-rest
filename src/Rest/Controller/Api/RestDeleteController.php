<?php
/**
 * Created by Florent HAZARD on 11/02/2018
 */

namespace Orpheus\Rest\Controller\Api;

use Orpheus\InputController\InputRequest;
use Orpheus\InputController\OutputResponse;

/**
 * Class RestDeleteController
 *
 * @package Orpheus\Rest\Controller\Api
 */
class RestDeleteController extends EntityRestController {
	
	/**
	 * Run this controller
	 *
	 * @param InputRequest $request
	 * @return OutputResponse|null
	 */
	public function run($request) {
		
		$this->entityService->deleteItem($this->item);
		
		return null;
	}
}
