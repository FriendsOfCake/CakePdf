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
		$expected = '/usr/bin/prince --input=auto --baseurl=' . Router::fullBaseUrl() . ' --javascript --pdf-title="PrinceXML is king" - -o -';
		$this->assertEquals($expected, $result);
		
		$Pdf = new CakePdf(array(
			'engine'  => 'PrinceXml',
			'binary'	=> '/another/location/prince',
			'options'	=> array (
				'subject'	=> 'Foobar',
				'key_bits'	=> 48
			)
		));
		
		$result = $method->invoke($Pdf->engine());
		
		$expected = '/another/location/prince --input=auto --baseurl=' . Router::fullBaseUrl() . ' --javascript --pdf-subject=Foobar - -o -';
		$this->assertEquals($expected, $result);
		
		$Pdf = new CakePdf(array(
			'engine'  		=> 'PrinceXml',
			'binary'	=> '/another/location/prince',
			'userPassword'	=> 'foo',
			'ownerPassword'	=> 'bar',
			'options'		=> array (
				'subject'	=> 'Foobar',
				'key_bits'	=> 48
			)
		));
		
		$result = $method->invoke($Pdf->engine());
		
		$expected = '/another/location/prince --input=auto --baseurl=' . Router::fullBaseUrl() . ' --javascript --pdf-subject=Foobar --key-bits=48 --user-password=foo --owner-password=bar - -o -';
		$this->assertEquals($expected, $result);
		
		$Pdf = new CakePdf(array(
			'engine'  		=> 'PrinceXml',
			'binary'	=> '/another/location/prince',
			'userPassword'	=> 'foo',
			'options'		=> array (
				'subject'	=> 'Foobar',
				'author'	=> 'God',
				'keywords'	=> 'pdf, html',
				'creator'	=> 'Humanity',
				'key_bits'	=> 48
			)
		));
		
		$result = $method->invoke($Pdf->engine());
		
		$expected = '/another/location/prince --input=auto --baseurl=' . Router::fullBaseUrl() . ' --javascript --pdf-subject=Foobar --pdf-author=God --pdf-keywords="pdf, html" --pdf-creator=Humanity --key-bits=48 --user-password=foo - -o -';
		$this->assertEquals($expected, $result);
	}
}
