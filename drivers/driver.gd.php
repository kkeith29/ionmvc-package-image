<?php

namespace ionmvc\packages\image\drivers;

use ionmvc\exceptions\app as app_exception;

class gd {

	private $file = null;
	private $info = null;
	private $image = null;
	private $width = 0;
	private $height = 0;
	private $org_image = null;

	public function __construct() {
		if ( !extension_loaded('gd') ) {
			throw new app_exception('GD extension not loaded');
		}
	}

	public function load_file( $file ) {
		if ( !file_exists( $file ) ) {
			throw new app_exception('Unable to find file');
		}
		if ( !is_readable( $file ) ) {
			throw new app_exception('Unable to read file');
		}
		$this->file = $file;
		$img = getimagesize( $file );
		switch( $img['mime'] ) {
			case 'image/png':
				$image = imagecreatefrompng( $file );
			break;
			case 'image/jpeg':
				$image = imagecreatefromjpeg( $file );
			break;
			case 'image/gif':
				$old_id = imagecreatefromgif( $file ); 
				$image  = imagecreatetruecolor( $img[0],$img[1] ); 
				imagecopy( $image,$old_id,0,0,0,0,$img[0],$img[1] ); 
			break;
			default:
				throw new app_exception( 'Unable to handle mime type %s',$img['mime'] );
			break;
		}
		$this->info		  = $img;
		$this->width	  = imagesx( $image );
		$this->height	  = imagesy( $image );
		$this->org_width  = $this->width;
		$this->org_height = $this->height;
		$this->image	  = $this->org_image = $image;
		return $this;
	}

	public function get_info() {
		return $this->info;
	}

	public function resize( $nwidth,$nheight,$prop=true,$box=false ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$width = $nwidth;
		$height = $nheight;
		if ( $nwidth == 'auto' ) {
			$nwidth = $this->width;
			$prop = true;
		}
		$ratio1 = ( $this->width / $nwidth );
		if ( $nheight == 'auto' ) {
			$nheight = $this->height;
			$prop = true;
		}
		$ratio2 = ( $this->height / $nheight );
		if ( $prop == true ) {
			if( $ratio1 > $ratio2 ) {
				$nheight = ( $this->height / $ratio1 );
			}
			else {
				$nwidth = ( $this->width / $ratio2 );
			}
		}
		if ( $box == false ) {
			$image = imagecreatetruecolor( $nwidth,$nheight );
		}
		else {
			$image = imagecreatetruecolor( $width,$height );
		}
		if ( isset( $this->info['mime'] ) && $this->info['mime'] == 'image/png' ) {
			imagealphablending( $image,false );
			imagesavealpha( $image,true );
			$bg = imagecolorallocatealpha( $image,255,255,255,127 );
			imagecolortransparent( $image,$bg );
		}
		$xpos = 0;
		$ypos = 0;
		if ( $box == true ) {
			$xpos = ( ( $width - $nwidth ) / 2 );
			$ypos = ( ( $height - $nheight ) / 2 );
			if ( !isset( $bg ) ) {
				$bg = imagecolorallocate( $image,255,255,255 );
			}
			imagefilledrectangle( $image,0,0,$width,$height,$bg );
		}
		else {
			imagealphablending( $image,false );
		}
		imagecopyresampled( $image,$this->image,$xpos,$ypos,0,0,$nwidth,$nheight,$this->width,$this->height );
		$this->width = ( $box == false ? $nwidth : $width );
		$this->height = ( $box == false ? $nheight : $height );
		$this->image = $image;
		return $this;
	}

	public function flip( $type ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$image = imagecreatetruecolor( $this->width,$this->height );
		switch( $type ) {
			case image::flip_horizontal:
				for( $x=0;$x < $this->width;$x++ ) {
					imagecopy( $image,$this->image,($this->width - $x - 1),0,$x,0,1,$this->height );
				}
			break;
			case image::flip_vertical:
				for( $y=0;$y < $this->height;$y++ ) {
					imagecopy( $image,$this->image,0,($this->height - $y - 1),0,$y,$this->width,1 );
				}
			break;
			case image::flip_both:
				for( $x=0;$x < $this->width;$x++ ) {
					imagecopy( $image,$this->image,($this->width - $x - 1),0,$x,0,1,$this->height );
				}
				$buff = imagecreatetruecolor( $this->width,1 );
				for( $y=0;$y < ( $this->height / 2 );$y++ ) {
					imagecopy( $buff,$image,0,0,0,($this->height - $y - 1),$this->width,1 );
					imagecopy( $image,$image,0,($this->height - $y - 1),0,$y,$this->width,1 );
					imagecopy( $image,$buff,0,$y,0,0,$this->width,1 );
				}
				imagedestroy( $buff );
			break;
		}
		$this->image = $image;
		return $this;
	}

	public function crop( $from_x,$from_y,$to_x,$to_y ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$width = ( $to_x - $from_x );
		$height = ( $to_y - $from_y );
		$image = imagecreatetruecolor( $width,$height );
		imagealphablending( $image,false );
		imagecopy( $image,$this->image,0,0,$from_x,$from_y,$width,$height );
		$this->image = $image;
		return $this;
	}

	public function rotate( $angle,$bg=0 ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$this->image = imagerotate( $this->image,$angle,$bg );
		return $this;
	}

	public function save_image( $file,$extn=null,$destroy=true ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$extn = ( is_null( $extn ) ? file::get_extension( $file ) : $extn );
		switch( $extn ) {
			case 'png':
				imagepng( $this->image,$file );
			break;
			case 'jpeg':
			case 'jpg':
				imagejpeg( $this->image,$file,100 );
			break;
			case 'gif':
				imagegif( $this->image,$file );
			break;
		}
		if ( $destroy == true ) {
			$this->destroy();
		}
		return false;
	}

	public function show( $headers=true,$destroy=true ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		if ( $headers === true ) {
			header("Content-Type: {$this->info['mime']}");
		}
		switch( $this->info['mime'] ) {
			case 'image/png':
				imagepng( $this->image );
			break;
			case 'image/jpeg':
				imagejpeg( $this->image,null,100 );
			break;
			case 'image/gif':
				imagegif( $this->image );
			break;
		}
		if ( $destroy == true ) {
			$this->destroy();
		}
		return $this;
	}

	public function data( $destory=true ) {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		ob_start();
		$this->show( false,$destory );
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}

	public function restore() {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		$this->image  = $this->org_image;
		$this->width  = $this->org_width;
		$this->height = $this->org_height;
		return $this;
	}

	public function destroy() {
		if ( is_null( $this->image ) ) {
			throw new app_exception('No image loaded');
		}
		if ( is_resource( $this->image ) ) {
			imagedestroy( $this->image );
		}
		if ( is_resource( $this->org_image ) ) {
			imagedestroy( $this->org_image );
		}
	}

}

?>