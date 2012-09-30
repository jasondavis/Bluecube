<?
/**
 * 
 */
if(!function_exists('imageconvolution')) {
	function imageconvolution($src, $filter, $filter_div, $offset) {
		if ($src==NULL) {
			return 0;
		}
	
		$sx = imagesx($src);
		$sy = imagesy($src);
		$srcback = ImageCreateTrueColor ($sx, $sy);
		ImageCopy($srcback, $src,0,0,0,0,$sx,$sy);
	
		if($srcback==NULL){
			return 0;
		}
		
		for ($y=0; $y<$sy; ++$y) {
			for($x=0; $x<$sx; ++$x) {
				$new_r = $new_g = $new_b = 0;
				$alpha = imagecolorat($srcback, $pxl[0], $pxl[1]);
				$new_a = $alpha >> 24;
			
				for ($j=0; $j<3; ++$j) {
					$yv = min(max($y - 1 + $j, 0), $sy - 1);
					for ($i=0; $i<3; ++$i) {
						$pxl = array(min(max($x - 1 + $i, 0), $sx - 1), $yv);
						$rgb = imagecolorat($srcback, $pxl[0], $pxl[1]);
						$new_r += (($rgb >> 16) & 0xFF) * $filter[$j][$i];
						$new_g += (($rgb >> 8) & 0xFF) * $filter[$j][$i];
						$new_b += ($rgb & 0xFF) * $filter[$j][$i];
					}
				}

				$new_r = ($new_r/$filter_div)+$offset;
				$new_g = ($new_g/$filter_div)+$offset;
				$new_b = ($new_b/$filter_div)+$offset;

				$new_r = ($new_r > 255)? 255 : (($new_r < 0)? 0:$new_r);
				$new_g = ($new_g > 255)? 255 : (($new_g < 0)? 0:$new_g);
				$new_b = ($new_b > 255)? 255 : (($new_b < 0)? 0:$new_b);

				$new_pxl = ImageColorAllocateAlpha($src, (int)$new_r, (int)$new_g, (int)$new_b, $new_a);
				if ($new_pxl == -1) {
					$new_pxl = ImageColorClosestAlpha($src, (int)$new_r, (int)$new_g, (int)$new_b, $new_a);
				}
				if (($y >= 0) && ($y < $sy)) {
					imagesetpixel($src, $x, $y, $new_pxl);
				}
			}
		}
		imagedestroy($srcback);
		return 1;
	}
}

class TGraphicsFilter extends TComponent {
	private $img;
	private $corner = array();
	private $imgLink;

	public function __construct(TGraphics $img) {
		if($img->getImage() != null) {
			$this->img = $img;
			$this->imgLink =& $img->getImage();
		} else throw new InvalidOperationException('First load an image using TGraphics::load() or TGraphics::loadFromResource()');
	}

	public function negative() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagefilter($this->img->getImage(), IMG_FILTER_NEGATE);

		return $this;
	}

	public function sepia($level = 0) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		$this->toGray();
		$this->colorize(30+30*$level,0+30*$level,-20+30*$level);

		return $this;
	}

	public function toGray() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagefilter($this->img->getImage(), IMG_FILTER_GRAYSCALE);

		return $this;
	}

	public function toBlue() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($x = 0; $x < $this->img->getWidth(); $x++) {
			for($y = 0; $y < $this->img->getHeight(); $y++) {
				$rgb = imageColorAt($this->img->getImage(), $x, $y);
				$b = $rgb & 0xFF;

				imageSetPixel($this->img->getImage(), $x, $y, imagecolorallocate($this->img->getImage(),0,0,$b));
			}
		}
		
		return $this;
	}

	public function toRed() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($x = 0; $x < $this->img->getWidth(); $x++) {
			for($y = 0; $y < $this->img->getHeight(); $y++) {
				$rgb = imageColorAt($this->img->getImage(), $x, $y);
				$r = ($rgb >> 16) & 0xFF;

				imageSetPixel($this->img->getImage(), $x, $y, imagecolorallocate($this->img->getImage(),$r,0,0));
			}
		}
		
		return $this;
	}

	public function toGreen() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($x = 0; $x < $this->img->getWidth(); $x++) {
			for($y = 0; $y < $this->img->getHeight(); $y++) {
				$rgb = imageColorAt($this->img->getImage(), $x, $y);
				$g = ($rgb >> 8) & 0xFF;

				imageSetPixel($this->img->getImage(), $x, $y, imagecolorallocate($this->img->getImage(),0,$g,0));
			}
		}
		
		return $this;
	}

	public function brightness($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagefilter($this->img->getImage(), IMG_FILTER_BRIGHTNESS, $level);

		return $this;
	}

	public function contrast($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagefilter($this->img->getImage(), IMG_FILTER_CONTRAST, $level);

		return $this;
	}

	public function colorize($r = 0, $g = 0, $b = 0) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if(is_object($r) && ($r instanceOf TGraphicsColor)) {
			imagefilter($this->img->getImage(), IMG_FILTER_COLORIZE, $r->getRed(), $r->getGreen(), $r->getBlue());
		} else imagefilter($this->img->getImage(), IMG_FILTER_COLORIZE, $r, $g, $b);

		return $this;
	}

	public function detectEdges($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($i = 0; $i < $level; $i++) imagefilter($this->img->getImage(), IMG_FILTER_EDGEDETECT);

		return $this;
	}

	public function emboss($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($i = 0; $i < $level; $i++) imagefilter($this->img->getImage(), IMG_FILTER_EMBOSS);

		return $this;
	}

	public function gaussianBlur($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($i = 0; $i < $level; $i++) imagefilter($this->img->getImage(), IMG_FILTER_GAUSSIAN_BLUR);

		return $this;
	}

	public function sketch($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($i = 0; $i < $level; $i++) imagefilter($this->img->getImage(), IMG_FILTER_MEAN_REMOVAL);

		return $this;
	}

	public function smooth($level = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagefilter($this->img->getImage(), IMG_FILTER_SMOOTH, $level);

		return $this;
	}

	public function pixelate($pixelSize = 5) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if($pixelSize < 2) throw new InvalidOperationException('Pixel size must not be smaller than 2');
		$rows = ceil($this->img->getHeight()/$pixelSize);
		$cols = ceil($this->img->getWidth()/$pixelSize);

		if($rows == 0 || $cols == 0) throw new InvalidOperationException('Pixel size is too big');

		for($x = 0; $x < $cols; $x++) {
			for($y = 0; $y < $rows; $y++) {
				$sx = $x*$pixelSize;
				$sy = $y*$pixelSize;

				$diff = $sx - $this->img->getWidth();
				if($diff >= 0) $sx -= ($diff+1);

				$diff = $sy - $this->img->getHeight();
				if($diff >= 0) $sy -= ($diff+1);

				$color = ImageColorAt($this->img->getImage(), $sx, $sy);

				imageFilledRectangle($this->img->getImage(), $sx, $sy, $sx+$pixelSize-1, $sy+$pixelSize-1, $color);
			}
		}

		return $this;
	}

	public function glassBlock($blockSize = 20,$offset = 5) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if($offset >= $blockSize) throw new InvalidOperationException('Offset must be smaller than block size');
		$rows = ceil($this->img->getHeight()/$blockSize);
		$cols = ceil($this->img->getWidth()/$blockSize);

		if($rows == 0 || $cols == 0) throw new InvalidOperationException('Block size is too big');

		for($x = 0; $x < $cols; $x++) {
			for($y = 0; $y < $rows; $y++) {
				$sx = $x*$blockSize;
				$sy = $y*$blockSize;

				$diff = $sx - $this->img->getWidth();
				if($diff >= 0) $sx -= ($diff+1);

				$diff = $sy - $this->img->getHeight();
				if($diff >= 0) $sy -= ($diff+1);

				$im = imagecreatetruecolor($blockSize, $blockSize);

				if(($x+$y)%2) {
					imagecopyresampled($im, $this->img->getImage(), 0, 0, $sx, $sy, $blockSize, $blockSize, $blockSize, $blockSize);
					imagecopyresampled($this->img->getImage(), $im, $sx, $sy, 0, 0, $blockSize-$offset, $blockSize-$offset, $blockSize, $blockSize);
				} else {
					imagecopyresampled($im, $this->img->getImage(), 0, 0, $sx, $sy, $blockSize, $blockSize, $blockSize, $blockSize);
					imagecopyresampled($this->img->getImage(), $im, $sx, $sy, 0, 0, $blockSize+$offset, $blockSize+$offset, $blockSize, $blockSize);
				}

				imagedestroy($im);
			}
		}

		return $this;
	}

	public function randomize() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		for($x = 0; $x < $this->img->getWidth(); $x++) {
			for($y = 0; $y < $this->img->getHeight(); $y++) {
				$rand_x = rand(0,$this->img->getWidth()-1);
				$rand_y = rand(0,$this->img->getHeight()-1);

				$color1 = imageColorAt($this->img->getImage(), $rand_x, $rand_y);
				$color2 = imageColorAt($this->img->getImage(), $x, $y);

				imageSetPixel($this->img->getImage(), $x, $y, $color1);
				imageSetPixel($this->img->getImage(), $rand_x, $rand_y, $color2);
			}
		}
	
		return $this;
	}

	public function gamma($new_gamma = 1) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		imagegammacorrect($this->img->getImage(), 1.0, $new_gamma);

		return $this;
	}

	public function customFilter(array $matrix, $offset = 0, $divisor = null) {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		if($divisor == null) {
			$divisor = 0;
			foreach($matrix as $m) {
				foreach($m as $v) $divisor += $v;
			}
		}

		imageconvolution($this->img->getImage(), $matrix, $divisor, $offset);

		return $this;
	}

	public function sharpen() {
		$this->customFilter(
			array(
				array(-1,-1,-1),
				array(-1,16,-1),
				array(-1,-1,-1)
			)
		);

		return $this;
	}

	public function copperplate($light = 205) {
		$this->customFilter(
			array(
				array(-1.2,8.2,-3.2),
				array(-4.3,0.4,4.4),
				array(0.3,-5.7,2.1)
			), $light)->sepia();
		
		return $this;
	}

	public function wetPaint($wetness = 10) {
		for($x = 0; $x < $this->img->getWidth(); $x++) {
			for($y = 0; $y < $this->img->getHeight(); $y++) {
				$color = imageColorAt($this->img->getImage(), $x, $y);
				$height = rand(0,$wetness);
				imageline($this->img->getImage(), $x, $y, $x, $y+$height, $color);
				$y += $height+1;
			}
		}
		return $this;
	}

	public function getImage() {
		if($this->img == null) throw new InvalidOperationException('Image is empty');
		return $this->img->getImage();
	}

} 
