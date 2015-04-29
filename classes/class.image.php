<?php

namespace ionmvc\packages\image\classes;

use ionmvc\classes\app;
use ionmvc\classes\autoloader;
use ionmvc\classes\config;
use ionmvc\classes\http;
use ionmvc\classes\output;
use ionmvc\classes\time;
use ionmvc\exceptions\app as app_exception;
use ionmvc\packages\image as image_pkg;

class image {

	const flip_horizontal = 1;
	const flip_vertical = 2;
	const flip_both = 3;

	private $driver = null;

	public static function output_headers( $mime ) {
		output::compression(false);
		http::content_type( $mime );
		if ( app::mode_production() ) {
			http::cache( time::now(),time::future( config::get('image.caching.days'),time::day ) );
		}
	}

	public function __construct() {
		$driver = config::get('image.driver');
		$this->driver = autoloader::class_by_type( $driver,image_pkg::class_type_driver,array(
			'instance' => true
		) );
		if ( $this->driver === false ) {
			throw new app_exception( 'Unable to load image driver: %s',$driver );
		}
	}

	public function driver() {
		return $this->driver;
	}

	public function __call( $method,$args ) {
		if ( !method_exists( $this->driver,$method ) ) {
			throw new app_exception( "Method '%s' not found",$method );
		}
		return call_user_func_array( array( $this->driver,$method ),$args );
	}

}

?>