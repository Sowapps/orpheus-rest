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
	private $method;
	
	/**
	 * @var string The controller class
	 */
	private $controller;
	
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
	public function generate() {
		return [
			'method'     => $this->method,
			'controller' => $this->controller,
			'output'     => 'json',
		];
	}
	
	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}
	
	/**
	 * @return string
	 */
	public function getController() {
		return $this->controller;
	}
	
}
