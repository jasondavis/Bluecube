<?php
/**
 * 
 */
class TGraphics extends TObject {
	private $img_format = 'jpg';
	private $img_w = 0;
	private $img_h = 0;
	private $img;
	private $filename;
	private $canvas = array('r' => 255, 'g' => 255, 'b' => 255);
	private $quality = 75;
	private $filter;

	/* Loads an image, supported types (extensions): jpg, jpeg, jpe, png, gif */

	public function load($name) {
		if (file_exists($name)) {
			$this->filename = $name;
			$this->img_format = strtolower(substr($name,strrpos($name,'.')+1));
			if($this->img_format == 'jpeg' || $this->img_format == 'jpe') $this->img_format = 'jpg';

			switch ($this->img_format) {
				case 'png': $res = @imageCreateFromPNG ($name); break;
				case 'jpg': $res = @imageCreateFromJPEG ($name); break;
				case 'gif': $res = @imageCreateFromGif ($name); break;
				default: throw new InvalidOperationException('Unknown image format: '.$this->img_format);
			}
			if(!is_resource($res)) 
			{
				$res = @imageCreateFromBmp ($name);	
			}
			if(!is_resource($res)) 
			{
				throw new InvalidOperationException('Image corrupted');	
			}
		} else throw new FileNotFoundException('File '.$name.' could not be found');

		$this->loadFromResource($res);
	}

	/* As above, but loads from resource (returned by imagecreate* functions) */

	public function loadFromResource(&$res) {
		$this->img =& $res;
		$this->img_w = imageSX($this->img);
		$this->img_h = imageSY($this->img);
	}

	/*
	Allocates new color in the image. You must provide red, green and blue components.
	Alpha level is optional, by default set to 0.
	Valid values for:
		red, green, blue: 0 - 255
		alpha: 0 (opaque) - 127 (transparent)
	*/

	public function allocateColor($r,$g,$b, $alpha = 0) {
		$color = new TGraphicsColor($r,$g,$b,$alpha);
		return $color->allocate($this);
	}

	/*
	Sets whether anti-aliasing should be turned on or off
	*/

	public function setAntialiasing($on) {
		imageantialias($this->img,$on);
	}

	/*
	Sets image thickness on or off
	*/

	public function setThickness($on) {
		imagesetthickness($this->img,$on);
	}

	/*
	Sets the quality of image (works with JPEG only). Valid values: 0 (worst) - 100 (best)
	*/

	public function setQuality($q) {
		if($q >= 0 && $q <= 100) $this->quality = $q;
		else throw new InvalidOperationException('Quality must be in range of 0 - 100, '.$q.' given');
	}

	/*
	Look: getImage()
	*/

	public function &getImg() {
		return $this->img;
	}

	/*
	Sets the canvas color
	*/

	public function setCanvas($r = 255, $g = 255, $b = 255) {
		$this->canvas['r'] = $r;
		$this->canvas['g'] = $g;
		$this->canvas['b'] = $b;
	}

	/*
	Returns canvas
	*/

	public function getCanvas() {
		return $this->canvas;
	}

	/*
	Sets image format, valid values: jpg, gif, png
	*/

	public function setFormat($f) {
		if(in_array($f,array('jpg','gif','png'))) $this->img_format = $f;
		else throw new InvalidOperationException('Unknown image format: '.$f);
	}

	/*
	Returns image resource
	*/

	public function &getImage() {
		return $this->img;
	}

	/*
	Returns image width
	*/

	public function getWidth() {
		return $this->img_w;
	}

	/*
	Returns image height
	*/

	public function getHeight() {
		return $this->img_h;
	}

	/*
	Returns clipped copy of the image. Arguments are as follows: x, y, width, height
	*/

	public function getClipped($x0 = 0, $y0 = 0, $width = 0, $height = 0) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if($x0 < 0 || $x0 > $this->img_w-1) throw new InvalidOperationException('X coordinate should be in range of 0 - '.($this->img_w-1));
		if($y0 < 0 || $y0 > $this->img_h-1) throw new InvalidOperationException('Y coordinate should be in range of 0 - '.($this->img_h-1));
		if($x0+$width > $this->img_w) throw new InvalidOperationException('Sum of x and width is greater than total width of image (which is '.$this->img_w.')');
		if($y0+$height > $this->img_h) throw new InvalidOperationException('Sum of y and height is greater than total height of image (which is '.$this->img_h.')');

		$new = imagecreatetruecolor($width,$height);

		imagecopyresampled($new,$this->img,0,0,$x0,$y0,$width,$height,$width,$height);

		$ob = new TGraphics;
		$ob->loadFromResource($new);

		return $ob;
	}

	/*
	Creates and returns selection. Type of returned object is TGraphicsSelection.
	Arguments are as follows: x, y, width, height
	*/

	public function selection($x, $y, $width, $height) {
		$c = $this->getClipped($x, $y, $width, $height)->getImage();
		$selection = new TGraphicsSelection($this,$x,$y);
		$selection->loadFromResource($c);
		unset($c);

		return $selection;
	}

	/*
	Clips the current image. Arguments are as follows: x, y, width, height
	*/

	public function clip($x, $y, $width, $height) {
		$new = $this->getClipped($x, $y, $width, $height);
		$this->img_w = $new->getWidth();
		$this->img_h = $new->getHeight();
		$this->img = $new->getImage();

		unset($new);
	}

	/*
	Returns resampled copy of the current image. Arguments are as follows:
	width, height, aspect_ratio (default to false)
	*/

	public function getResized($width,$height,$aspect_ratio = false) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		$imW = $this->img_w;
		$imH = $this->img_h;

		if($aspect_ratio) {
			$factor = $width/$imW;
			$dstW = $width;
			$dstH = $factor*$imH;
		} else {
			$dstW = $width;
			$dstH = $height;
		}

		$dst = imagecreatetruecolor($dstW,$dstH);
		imagecopyresampled($dst,$this->img,0,0,0,0,$dstW,$dstH,$imW,$imH);

		$new = new TGraphics();
		$new->loadFromResource($dst);
		return $new;
	}

	/* Resizes the current image. Arguments are as follows: width, height, aspect_ratio (default to false) */

	public function resize($width, $height, $aspect_ratio = false) {
		$new = $this->getResized($width, $height, $aspect_ratio);
		$this->img_w = $new->getWidth();
		$this->img_h = $new->getHeight();
		$this->img = $new->getImage();

		unset($new);
	}

	/* Flips the current image horizontally */

	public function flipHorizontal() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		$new = imagecreatetruecolor($this->img_w,$this->img_h);
		for($x = 0; $x < $this->img_w; $x++) {
			for($y = 0; $y < $this->img_h; $y++) {
				$color = ImageColorAt($this->img, $x, $y);
				imageSetPixel($new, $this->img_w-$x-1, $y, $color);
			}
		}
		imagedestroy($this->img);

		$this->loadFromResource($new);
	}

	/* Flips the current image vertically */

	public function flipVertical() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		$new = imagecreatetruecolor($this->img_w,$this->img_h);
		for($x = 0; $x < $this->img_w; $x++) {
			for($y = 0; $y < $this->img_h; $y++) {
				$color = ImageColorAt($this->img, $x, $y);
				imageSetPixel($new, $x, $this->img_h-$y-1, $color);
			}
		}
		imagedestroy($this->img);
		
		$this->loadFromResource($new);
	}

	/*
	Rotates the current image. Arguments are as follows:
		angle (-360 - 360), red color, green color, blue color, ignore_transparent = 0
		OR:
		angle (-360 - 360), TGraphicsColor color, ignore_transparent
	*/

	public function rotate($angle = 0, $r = 255, $g = 255, $b = 255, $ignore_transparent = 0) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if(is_object($r) && ($r instanceOf TGraphicsColor)) {
			$ignore_transparent = $g;
			$g = $r->getGreen();
			$b = $r->getBlue();
			$r = $r->getRed();
		}
		$new = imagerotate($this->img, -$angle, imagecolorallocate($this->img,$r,$g,$b), $ignore_transparent);
		imagedestroy($this->img);
	
		$this->loadFromResource($new);
	}

	/*
	Returns the TGraphicsFilter object for the current image
	*/

	public function getFilter() {
		if($this->filter == null) $this->filter = new TGraphicsFilter($this);
		
		return $this->filter;
	}

	/* Sends the current image to browser */

	public function send() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		switch($this->img_format) {
			case 'png':
				header('Content-Type: image/png');
				imagePNG($this->img);
			break;
			case 'jpg':
				header('Content-Type: image/jpeg');
				imageJPEG($this->img);
			break;
			case 'gif':
				header('Content-Type: image/gif');
				imageGIF($this->img);
			break;
		}
	}

	/*
	Saves current image to file. If $filename argument is empty or not set and image
	is loaded from a file, the original file will be overwritten. If $filename is
	set, the image will be saved to that file. The type of image will be automatically
	recognized from file extension (one of: jpg, jpeg, jpe, gif, png)
	*/

	public function save($filename = null) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if($filename == null) $filename = $this->filename;
		if($filename == null) throw new InvalidOperationException('File name not specified');
		$ret = true;

		$ext = strtolower(substr($filename,strrpos($filename,'.')+1));
		if($ext == 'jpe' || $ext == 'jpeg') $ext = 'jpg';

		switch($ext) {
			case 'png':
				$ret = @imagePNG($this->img,$filename);
			break;
			case 'jpg':
				$ret = @imageJPEG($this->img,$filename,$this->quality);
			break;
			case 'gif':
				$ret = @imageGIF($this->img,$filename);
			break;
			default:
				throw new InvalidOperationException('Unknown image format: '.$ext);
			break;
		}
		if(!$ret) throw new InvalidOperationException($filename.' is not writable');
		@imageDestroy($this->img);
		$this->load($filename);
		return true;
	}

	/*
		Frees memory and destroys image
	*/

	public function free() {
		@imagedestroy($this->img);
		$this->filter = null;
	}

	/* Returns new TGraphics object containing histogram of the current image */

	public function getHistogram() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		$values = array();
		$im = imagecreatetruecolor(265,180);

		for($x = 0; $x < $this->img_w; $x++) {
			for($y = 0; $y < $this->img_h; $y++) {

				$rgb = imagecolorat($this->img, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				if(!isset($values[$r])) $values[$r] = 1; else $values[$r]++;
				if(!isset($values[$g])) $values[$g] = 1; else $values[$g]++;
				if(!isset($values[$b])) $values[$b] = 1; else $values[$b]++;
			}
		}
		$max = max($values);
		$p = (155/$max);

		imagefill($im,0,0,imagecolorallocate($im,255,255,255));
		$color = imagecolorallocate($im,0,0,0);
		$line = imagecolorallocate($im,255,0,0);


		for($x = 0; $x < 256; $x++) {
			if(isset($values[$x])) {
				$h = round($values[$x]*$p);
				imageline($im,$x+5,160,$x+5,160-$h,$color);
			}
		}

		unset($values);

		imageline($im,0,161,265,161,$line);
		imageline($im,5,161,5,165,$line);
		imageline($im,260,161,260,165,$line);
		imagestring($im,3,2,166,'0',$line);
		imagestring($im,3,243,166,'255',$line);
		imagerectangle($im,0,0,264,179,$line);
	

		$ob = new TGraphics;
		$ob->loadFromResource($im);
		$ob->setFormat('png');

		return $ob;
	}
}
