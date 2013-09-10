<?php
App::uses('CakePdf', 'CakePdf.Pdf');
App::uses('PrinceXmlEngine', 'CakePdf.Pdf/Engine');

/**
 * PrinceXmlEngine class
 *
 * @package       CakePdf.Test.Case.Pdf.Engine
 */
class PrinceXmlEngineTest extends CakeTestCase {

/**
 * Tests that the engine generates the right command
 *
 */
	public function testGetCommand() {
		$class = new ReflectionClass('PrinceXmlEngine');
		$method = $class->getMethod('parseCommand');
		$method->setAccessible(true);

		$Pdf = new CakePdf(array(
			'engine'  => 'PrinceXml',
			'title'   => 'PrinceXML is king'
		));
		
		$result = $method->invoke($Pdf->engine());
		$expected = '/usr/bin/prince --input=auto --input-list=- --baseurl=' . Router::fullBaseUrl() . ' --javascript --output=- --pdf-title="PrinceXML is king"';
		$this->assertEquals($expected, $result);
		
		$Pdf = new CakePdf(array(
			'engine'  => 'PrinceXml',
			'options'	=> array (
				'binary'	=> '/another/location/prince',
				'pdf'		=> array (
					'subject'	=> 'Foobar',
					'key_bits'	=> 48
				)
			)
		));
		
		$result = $method->invoke($Pdf->engine());
		
		$expected = '/another/location/prince --input=auto --input-list=- --baseurl=' . Router::fullBaseUrl() . ' --javascript --output=- --pdf-subject=Foobar';
		$this->assertEquals($expected, $result);
		
		$Pdf = new CakePdf(array(
			'engine'  		=> 'PrinceXml',
			'userPassword'	=> 'foo',
			'ownerPassword'	=> 'bar',
			'options'		=> array (
				'binary'	=> '/another/location/prince',
				'pdf'		=> array (
					'subject'	=> 'Foobar',
					'key_bits'	=> 48
				)
			)
		));
		
		$result = $method->invoke($Pdf->engine());
		
		$expected = '/another/location/prince --input=auto --input-list=- --baseurl=' . Router::fullBaseUrl() . ' --javascript --output=- --pdf-subject=Foobar --key-bits=48 --user-password=foo --owner-password=bar';
		$this->assertEquals($expected, $result);
	}
}
