<?php

App::uses('CakePdf', 'CakePdf.Lib/Pdf');

/**
 * CakePdfTest class
 *
 * @package       CakePdf.Test.Case.Lib.CakePdf
 */
class CakePdfTest extends CakeTestCase {

	public function testMargin() {
		$pdf = new CakePdf();
		$pdf->margin(15, 20, 25, 30);
		$expected = array(
			'bottom' => 15,
			'left' => 20,
			'right' => 25,
			'top' => 30
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf();
		$pdf->margin(75);
		$expected = array(
			'bottom' => 75,
			'left' => 75,
			'right' => 75,
			'top' => 75
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf();
		$pdf->margin(20, 50);
		$expected = array(
			'bottom' => 20,
			'left' => 50,
			'right' => 50,
			'top' => 20
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf();
		$pdf->margin(array('left' => 120, 'right' => 30, 'top' => 34, 'bottom' => 15));
		$expected = array(
			'bottom' => 15,
			'left' => 120,
			'right' => 30,
			'top' => 34
		);
		$this->assertEqual($expected, $pdf->margin());
	}

	public function testMarginFromConfig() {
		$config = array(
			'margin' => array(
				'bottom' => 15,
				'left' => 50,
				'right' => 30,
				'top' => 45
			)
		);
		$pdf = new CakePdf($config);
		$expected = array(
			'bottom' => 15,
			'left' => 50,
			'right' => 30,
			'top' => 45
		);
		$this->assertEqual($expected, $pdf->margin());
	}
}
