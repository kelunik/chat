<?php

namespace AerysChat\Util;

class Set {
	private $data;

	public function __construct (array $data = []) {
		$this->data = [];

		foreach ($data as $element) {
			$this->data[spl_object_hash($element)] = $element;
		}
	}

	public function add (...$elements) {
		foreach ($elements as $element) {
			$this->data[spl_object_hash($element)] = $element;
		}
	}

	public function toArray () {
		return array_values($this->data);
	}
}
