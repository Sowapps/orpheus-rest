<?php
/**
 * Created by Florent HAZARD on 05/04/2020
 */

namespace Orpheus\Rest\Controller;

use Orpheus\Config\Yaml\Yaml;
use Orpheus\Exception\ForbiddenException;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpResponse;
use Orpheus\Rest\Controller\Api\RestController;

/**
 * Class TemplateController
 */
class TemplateController extends RestController {
	
	/**
	 * @param HttpRequest $request
	 * @throws ForbiddenException
	 */
	public function run($request): HttpResponse {
		$templateKey = $request->getPathValue('key');
		
		$templateConfig = $this->getTemplateConfig();
		if( $templateConfig[$templateKey] ) {
			$template = $templateConfig[$templateKey];
			$requiredAccess = $template->access ?? 0;
			if( $this->getUserAccess() < $requiredAccess ) {
				throw new ForbiddenException();
			}
		}
		
		return $this->renderHTML('front-template/' . $templateKey);
	}
	
	public function getTemplateConfig(): array {
		$config = Yaml::build('front-templates', true);
		
		return $config->templates;
	}
	
}
