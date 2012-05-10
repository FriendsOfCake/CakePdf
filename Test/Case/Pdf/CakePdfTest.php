<?php

App::uses('CakePdf', 'CakePdf.Pdf');
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');

/**
 * Dummy engine
 */
class PdfTestEngine extends AbstractPdfEngine {

	public function output() {
		return 'Pdf data';
	}
}

/**
 * CakePdfTest class
 *
 * @package       CakePdf.Test.Case.Lib.CakePdf
 */
class CakePdfTest extends CakeTestCase {

/**
 *
 */
	public static function provider() {
		return array(array(array(
				'engine' => 'PdfTest',
				'margin' => array(
					'bottom' => 15,
					'left' => 50,
					'right' => 30,
					'top' => 45
		))));
	}

/**
 *
 * @dataProvider provider
 */
	public function testMargin($config) {
		$pdf = new CakePdf($config);
		$pdf->margin(15, 20, 25, 30);
		$expected = array(
			'bottom' => 15,
			'left' => 20,
			'right' => 25,
			'top' => 30
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf($config);
		$pdf->margin(75);
		$expected = array(
			'bottom' => 75,
			'left' => 75,
			'right' => 75,
			'top' => 75
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf($config);
		$pdf->margin(20, 50);
		$expected = array(
			'bottom' => 20,
			'left' => 50,
			'right' => 50,
			'top' => 20
		);
		$this->assertEqual($expected, $pdf->margin());

		$pdf = new CakePdf($config);
		$pdf->margin(array('left' => 120, 'right' => 30, 'top' => 34, 'bottom' => 15));
		$expected = array(
			'bottom' => 15,
			'left' => 120,
			'right' => 30,
			'top' => 34
		);
		$this->assertEqual($expected, $pdf->margin());

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
