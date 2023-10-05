<?php

namespace Orpheus\Rest;

use Orpheus\InputController\HttpController\HttpResponse;

/**
 * Class XMLHttpResponse
 */
class XmlHttpResponse extends HttpResponse {
	
	/**
	 * Constructor
	 */
	public function __construct(?string $body = null) {
		parent::__construct($body, 'application/xml');
	}
	
}
