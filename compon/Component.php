<?php
namespace Leaf\Veins\Component;

trait Component {
	public function __construct() {
		// called automatically, runs first
		$constructor = new LifeCycle\Constructor;

		// called after constructor
		$componentDidMount = new LifeCycle\ComponentDidMount;

		// called after constructor and component did mount
		$render = new LifeCycle\Render;
	}
}