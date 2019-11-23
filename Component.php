<?php
namespace Leaf\Veins;

class Component extends Template {
	use Leaf\Veins\Component\Component;

	//  state variable to be used
	public $state;

	public function __construct() {
		$this->state = (object) $this->state;
		self::constructor();
		self::componentDidMount();
		self::render();
	}

	public static function constructor() {
		// something happens when constructor is called
	}

	public static function componentDidMount() {
		// something happens when componentDidMount is called
	}

	public static function render() {
		// something happens when render is called
	}

	public function setState($data) {
		$this->state = $data;
	}
}