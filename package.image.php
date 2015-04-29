<?php

namespace ionmvc\packages;

use ionmvc\classes\app;

class image extends \ionmvc\classes\package {

	const version = '1.0.0';
	const class_type_driver = 'ionmvc.image_driver';

	public function setup() {
		$this->add_type('driver',array(
			'type' => self::class_type_driver,
			'type_config' => array(
				'file_prefix' => 'driver'
			),
			'path' => 'drivers'
		));
	}

	public static function package_info() {
		return array(
			'author'      => 'Kyle Keith',
			'version'     => self::version,
			'description' => 'Image handler'
		);
	}

}

?>