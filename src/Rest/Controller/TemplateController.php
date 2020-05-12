<?php
/**
 * Created by Florent HAZARD on 05/04/2020
 */

namespace Orpheus\Rest\Controller;

use Orpheus\Config\YAML\YAML;
use Orpheus\Exception\ForbiddenException;
use Orpheus\InputController\InputRequest;
use Orpheus\InputController\OutputResponse;
use Orpheus\Rest\Controller\Api\RestController;

/**
 * Class TemplateController
 *
 * @package Orpheus\Rest\Controller
 */
class TemplateController extends RestController {
	
	/**
	 * @param InputRequest $request
	 * @return OutputResponse
	 * @throws ForbiddenException
	 */
	public function run($request) {
		$templateKey = $request->getPathValue('key');
		
		$templateConfig = $this->getTemplateConfig();
		if( $templateConfig[$templateKey] ) {
			$template = $templateConfig[$templateKey];
			$requiredAccess = isset($template->access) ? $template->access : 0;
			if( $this->getUserAccess() < $requiredAccess ) {
				throw new ForbiddenException();
			}
		}
		
		return $this->renderHTML('front-template/' . $templateKey);
	}
	
	public function getTemplateConfig() {
		$config = YAML::build('front-templates', true);
		return $config->templates;
	}
}
