<?php
/**
 * 
 */
class TGraphicsSelection extends TGraphics {
	private $selection;
	private $original;
	private $x;
	private $y;
	private $is_copied = false;
	private $angle;

	public function __construct(TGraphics $original, $x, $y, $is_copied = false) {
		$this->original = $original;
		$this->x = $x;
		$this->y = $y;
		$this->is_copied = false;
	}

	/*
	Applies all changes made on the selection to the original image
	*/

	public function apply() {
		$w = $this->getWidth();
		$h = $this->getHeight();

		imagecopyresampled($this->original->getImage(), $this->getImage(), $this->x, $this->y, 0, 0, $w, $h, $w, $h);
	}

	/*
	Fills the selection with specified color. Alpha parameter is optional.
	Here is an example how to create a transparent rectangle on the image:

	//Create an object and load image
	$im = new TGraphics;
	$im->load('sample.jpg');

	//Create selection (x = 0, y = 0, width = 100, y = 100)
	$sel = $im->selection(0,0,100,100);

	//Fill the selection with black color and apply transparency level of 50
	$sel->fill(new TGraphicsColor(0,0,0),50);

	//Apply selection to the original image
	$sel->apply();

	//Send image to the browser
	$sel->send();
	*/


	public function fill(TGraphicsColor $color) {
		$r = $color->getRed();
		$g = $color->getGreen();
		$b = $color->getBlue();

		imagefilledrectangle($this->getImage(), 0, 0, $this->getWidth(), $this->getHeight(), $color->allocate($this));
	}

	/*
	Moves the selection to the new x and y coordinates
	*/

	public function move($x = 0, $y = 0) {
		if(!$this->is_copied) {
			$this->delete();
		}
		$this->x = $x;
		$this->y = $y;
	}

	/*
	Deletes the selection
	*/
	

	public function delete() {
		$canvas = $this->original->getCanvas();
		imagefilledrectangle($this->original->getImage(), $this->x, $this->y, $this->x+$this->getWidth()-1, $this->y+$this->getHeight()-1, imagecolorallocate($this->original->getImage(), $canvas['r'], $canvas['g'], $canvas['b']));
	}

	/*
	Copies the selection
	*/

	public function copy() {
		$sel = new TGraphicsSelection($this->original, $this->x, $this->y, true);
		$sel->loadFromResource($this->getClipped(0, 0, $this->getWidth(), $this->getHeight())->getImage());

		return $sel;
	}

	/*
	Rotates the selection
	*/

	public function rotate($angle) {
		$this->angle = $angle;
		parent::rotate($angle);
	}
}
