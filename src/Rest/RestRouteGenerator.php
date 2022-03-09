<?php
/**
 * Created by Florent HAZARD on 28/01/2018
 */

namespace Orpheus\Rest;

/**
 * Class RestRouteGenerator
 *
 * @package Orpheus\Rest
 */
class RestRouteGenerator {
	
	/**
	 * @var string The method
	 * @see constants in HttpRoute
	 */
	private string $method;
	
	/**
	 * @var string The controller class
	 */
	private string $controller;
	
	/**
	 * RestRouteGenerator constructor.
	 *
	 * @param string $method
	 * @param string $controller
	 */
	public function __construct($method, $controller) {
		$this->method = $method;
		$this->controller = $controller;
	}
	
	/**
	 * @return array
	 */
	public function generate(): array {
		return [
			'method'     => $this->method,
			'controller' => $this->controller,
			'output'     => 'json',
		];
	}
	
	/**
	 * @return string
	 */
	public function getMethod(): string {
		return $this->method;
	}
	
	/**
	 * @return string
	 */
	public function getController(): string {
		return $this->controller;
	}
	
}
