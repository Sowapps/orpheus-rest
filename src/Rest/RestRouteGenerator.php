<?php
/**
 * Created by Florent HAZARD on 28/01/2018
 */

namespace Orpheus\Rest;

/**
 * Class RestRouteGenerator
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
	 */
	public function __construct(string $method, string $controller) {
		$this->method = $method;
		$this->controller = $controller;
	}
	
	public function generate(): array {
		return [
			'method'     => $this->method,
			'controller' => $this->controller,
			'output'     => 'json',
		];
	}
	
	public function getMethod(): string {
		return $this->method;
	}
	
	public function getController(): string {
		return $this->controller;
	}
	
}
