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
	private $pdfReactorClient;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Cake\TestSuite\TestCase::setUp()
	 */
	public function setUp() {
		parent::setUp();
		
		// Create pdf reactor client mock
		$this->pdfReactorClient = $this->getMockBuilder('PDFreactor')
			->setMethods(['convertAsBinary'])
			->getMock();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Cake\TestSuite\TestCase::tearDown()
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->pdfReactorClient);
	}
	
	/**
	 * Test output of client gets called.
	 */
	public function testOutput() {
		// Configure mock
		$this->pdfReactorClient->expects($this->once())
			->method('convertAsBinary')
			->will($this->returnCallback(function() {
				return "%PDF-1.4 MOCK ... %%EOF\n";
			}));
		
		$Pdf = new CakePdf([
			'engine' => [
				'className' => 'CakePdf.PdfReactor',
				'client' => $this->pdfReactorClient
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
		// Configure mock
		$this->pdfReactorClient->expects($this->once())
			->method('convertAsBinary')
			->will($this->returnCallback(function() {
				return "%PDF-1.4 MOCK ... %%EOF\n";
			}));
			
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
				return $this->pdfReactorClient;
			}));
		$mock_engine->output();
	}
	
	/**
	 * Test output of client gets called.
	 */
	public function testException() {
		// Configure mock
		$this->pdfReactorClient->expects($this->once())
			->method('convertAsBinary')
			->will($this->returnCallback(function() {
				throw new \Exception("Foo Bar");
			}));
			
		$Pdf = new CakePdf([
			'engine' => [
				'className' => 'CakePdf.PdfReactor',
				'client' => $this->pdfReactorClient
			]
		]);
		
		$this->expectException(\Cake\Core\Exception\Exception::class);
		$Pdf->engine()->output();
	}
	
	/**
	 * Test Exception client not found
	 */
	public function testExceptionClientNotFound() {
		$Pdf = new CakePdf([
			'engine' => [
				'className' => 'CakePdf.PdfReactor',
				'client' => [
					'className' => 'FooBar',
					'serviceUrl' => 'http://localhost'
				]
			]
		]);
		$this->expectException(\Cake\Core\Exception\Exception::class);
		$Pdf->engine()->output();
	}
	
	/**
	 * Test Exception "missing convertAsBinary"
	 */
	public function testExceptionMissinMethod() {
		$Pdf = new CakePdf([
			'engine' => [
				'className' => 'CakePdf.PdfReactor',
				'client' => new \stdClass()
			]
		]);
		$this->expectException(\Cake\Core\Exception\Exception::class);
		$Pdf->engine()->output();
	}
}

