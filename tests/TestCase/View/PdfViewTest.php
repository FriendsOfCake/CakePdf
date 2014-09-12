<?php

namespace CakePdf\Test\TestCase\View;

use CakePdf\Pdf\Engine\AbstractPdfEngine;
use CakePdf\View\PdfView;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;

/**
 * Dummy engine
 */
class PdfTestEngine extends AbstractPdfEngine {

	public function output() {
		return $this->_Pdf->html();
	}

}

/**
 * Dummy controller
 */
class PdfTestPostsController extends Controller {

	public $name = 'Posts';

	public $pdfConfig = array('engine' => 'PdfTest');

}

/**
 * PdfViewTest class
 *
 * @package       CakePdf.Test.Case.View
 */
class PdfViewTest extends TestCase {

/**
 * setup callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('CakePdf', [
			'engine' => '\\' . __NAMESPACE__ . '\PdfTestEngine'
		]);


		$request = new Request();
		$Controller = new PdfTestPostsController($request);
		$this->View = new PdfView($request);
		$this->View->layoutPath = 'pdf';
	}

/**
 * testRender
 *
 */
	public function testConstruct() {
		$result = $this->View->response->type();
		$this->assertEquals('application/pdf', $result);

		$result = $this->View->pdfConfig;
		$this->assertEquals(array('engine' => '\\' . __NAMESPACE__ . '\PdfTestEngine'), $result);

		$result = $this->View->renderer();
		$this->assertInstanceOf('CakePdf\Pdf\CakePdf', $result);
	}

/**
 * testRender
 *
 */
	public function testRender() {
		$this->View->viewPath = 'Posts';
		$this->View->set('post', 'This is the post');
		$result = $this->View->render('view', 'default');

		$this->assertTrue(strpos($result, '<h2>Rendered with default layout</h2>') !== false);
		$this->assertTrue(strpos($result, 'Post data: This is the post') !== false);
	}

}
