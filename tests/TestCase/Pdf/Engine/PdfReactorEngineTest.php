<?php

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\PdfReactorEngine;

/**
 *
 * @author jmischer
 *        
 */
class PdfReactorEngineTest extends TestCase {
	/**
	 * 
	 * @var \PHPUnit\Framework\MockObject\MockObject
	 */
	private $pdfReactorMock;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Cake\TestSuite\TestCase::setUp()
	 */
	public function setUp() {
		parent::setUp();
		
		$this->pdfReactorMock = $this->getMockBuilder('PDFreactor')
			->setMethods(['convertAsBinary'])
			->getMock();
		$this->pdfReactorMock->expects($this->once())
			->method('convertAsBinary')
			->will($this->returnCallback(function() {
				return "%PDF-1.4 MOCK ... %%EOF\n";
			}));
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Cake\TestSuite\TestCase::tearDown()
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->pdfReactorMock);
	}
	
	/**
	 * Test output of client gets called.
	 */
	public function testOutput() {
		$Pdf = new CakePdf([
			'engine' => [
				'className' => 'CakePdf.PdfReactor',
				'client' => $this->pdfReactorMock
			]
		]);
		$Pdf->html('<foo>bar</foo>');
		$output = $Pdf->engine()->output();
		$this->assertStringStartsWith('%PDF-1.4 MOCK', $output);
		$this->assertStringEndsWith("%%EOF\n", $output);
	}
	
	/**
	 * Test createInstance gets passed the client config.
	 */
	public function testCreateInstance() {
		// Mock PdfReactorEngine
		$engineClass = $this->getMockClass(PdfReactorEngine::class, ['createInstance']);
		
		// Initialize client configuration
		$client_config = [
			'className' => '\PDFreactor',
			'serviceUrl' => 'http://localhost:9423/service/rest',
		];
		
		$cakePdf = new CakePdf([
			'engine' => [
				'className' => '\\' . $engineClass,
				'client' => $client_config
			],
		]);
		
		// Get the mocked engine from CakePdf instance
		$mock_engine = $cakePdf->engine();
		$mock_engine->expects($this->once())
			->method('createInstance')
			->will($this->returnCallback(function ($options) use ($client_config) {
				$this->assertEquals($options, $client_config);
				return $this->pdfReactorMock;
			}));
		$mock_engine->output();
	}
}

