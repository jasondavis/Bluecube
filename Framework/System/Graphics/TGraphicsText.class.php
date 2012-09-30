<?php
/**
 * 
 */
class TGraphicsText extends TComponent {
	private $font;
	private $size;
	private $width = 0;
	private $height = 0;
	private $text;
	private $is_ttf = false;
	private $color;

	public function __construct($text, $font = null, $size = 3, TGraphicsColor $color = null) {
		$this->text = $text;
		$this->size = $size;

		if($font != null) {
			$font = SYSTEM_DIR.'Fonts'.DIRECTORY_SEPARATOR.$font;
			if(file_exists($font)) {
				$this->font = $font;
				$ext = strtolower(substr($font,strrpos($font,'.')+1));

				switch($ext) {
					case 'ttf':
						$bbox = imageftbbox($size,0,$font,$text);

						$this->width = abs($bbox[0])+abs($bbox[2]);
						$this->height = abs($bbox[1])+abs($bbox[5]);
						$this->is_ttf = true;
					break;
					default:
						throw new InvalidOperationException('TGraphicsText accepts only TTF or built-in fonts');
					break;
				}
			} else throw new InvalidOperationException('File '.$font.' not found');
		} else {
			$this->width = imagefontwidth($size)*strlen($text);
			$this->height = imagefontheight($size);
		}

		if($color == null) {
			$this->color = new TGraphicsColor(0,0,0);
		} else $this->color = $color;
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function draw(TGraphics $img, $position_horizontal = 0, $position_vertical = 0, $padding = 0, TGraphicsColor $shadowColor = null, $shadowOffset = 0) {
		if(is_numeric($position_horizontal)) {
			$x = $position_horizontal;
		} else if(is_string($position_horizontal)) {
			switch($position_horizontal) {
				case 'left':
					$x = $padding;
				break;
				case 'right':
					$x = $img->getWidth()-$this->width-$padding;
				break;
				case 'center':
					$x = round(($img->getWidth()-$this->width)/2);
				break;
				default:
					throw new InvalidOperationException('$position_horizontal must be either numeric or string (left, center, right)');
				break;
			}
		} else throw new InvalidOperationException('$position_horizontal must be either numeric or string (left, center, right)');

		if(is_numeric($position_vertical)) {
			$y = $position_vertical;
		} else if(is_string($position_vertical)) {
			switch($position_vertical) {
				case 'top':
					$y = $padding;
				break;
				case 'bottom':
					$y = $img->getHeight()-$padding;
				break;
				case 'center':
					if($this->is_ttf) {
						$y = round(($img->getHeight()+$this->height/2)/2);
					} else {
						$y = round(($img->getHeight()-$this->height)/2);
					}
				break;
				default:
					throw new InvalidOperationException('$position_vertical must be either numeric or string (top, center, bottom)');
				break;
			}
		} else throw new InvalidOperationException('$position_vertical must be either numeric or string (top, center, bottom)');

		$r = $this->color->getRed();
		$g = $this->color->getGreen();
		$b = $this->color->getBlue();

		if($this->is_ttf) {
			if($shadowColor != null) {
				imagettftext($img->getImg(), $this->size, 0, $x+$shadowOffset, $y+$shadowOffset, $shadowColor->allocate($img), $this->font, $this->text);
			}
			imagettftext($img->getImg(), $this->size, 0, $x, $y, imagecolorallocate($img->getImg(),$r,$g,$b), $this->font, $this->text);
		} else {
			if($shadowColor != null) {
				imagestring($img->getImg(), $this->size, $x+$shadowOffset, $y+$shadowOffset, $this->text, $shadowColor->allocate($img));
			}
			imagestring($img->getImg(), $this->size, $x, $y, $this->text, imagecolorallocate($img->getImg(), $r, $g, $b));
		}
	}
}
