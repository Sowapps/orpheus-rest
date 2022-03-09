<?php

namespace Orpheus\Rest;

use Orpheus\InputController\HttpController\HttpResponse;

/**
 * Class XMLHttpResponse
 *
 * @package Orpheus\Rest
 */
class XMLHttpResponse extends HttpResponse {
	
	/**
	 * Constructor
	 *
	 * @param string|null $body
	 */
	public function __construct(?string $body = null) {
		parent::__construct($body, 'application/xml');
	}
	
}
