<?php
/**
 * 
 */
class TGraphicsColor extends TComponent {
	private $r = 0;
	private $g = 0;
	private $b = 0;
	private $alpha = 0;

	public function __construct($r,$g,$b,$alpha = 0) {
		if(!($alpha >= 0 && $alpha <= 127)) throw new InvalidOperationException('Alpha value must be in range of 0 - 127');
		if(!($r >= 0 && $r <= 255)) throw new InvalidOperationException('Red color value must be in range of 0 - 255');
		if(!($g >= 0 && $g <= 255)) throw new InvalidOperationException('Green color value must be in range of 0 - 255');
		if(!($b >= 0 && $b <= 255)) throw new InvalidOperationException('Blue color value must be in range of 0 - 255');

		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
		$this->alpha = $alpha;
	}

	public function getRed() {
		return $this->r;
	}

	public function getBlue() {
		return $this->b;
	}

	public function getGreen() {
		return $this->g;
	}

	public function toArray() {
		return array('r' => $this->r, 'g' => $this->g, 'b' => $this->b, 'alpha' => $this->alpha);
	}

	public function allocate(TGraphics $img) {
		return imagecolorallocatealpha($img->getImage(), $this->r, $this->g, $this->b, $this->alpha);
	}
}
