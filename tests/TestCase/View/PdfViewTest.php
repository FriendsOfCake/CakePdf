<?php

namespace CakePdf\Test\TestCase\View;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\AbstractPdfEngine;
use CakePdf\View\PdfView;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Dummy engine
 */
class PdfTestEngine extends AbstractPdfEngine
{

    public function output()
    {
        return $this->_Pdf->html();
    }
}

/**
 * PdfViewTest class
 */
class PdfViewTest extends TestCase
{

    /**
     * setup callback
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('CakePdf', [
            'engine' => '\\' . __NAMESPACE__ . '\PdfTestEngine',
        ]);

        $request = new ServerRequest();
        $response = new Response();
        $this->View = new PdfView($request, $response);
        $this->View->setLayoutPath('pdf');
    }

    /**
     * testRender
     *
     */
    public function testConstruct()
    {
        if (version_compare(Configure::version(), '3.6.0', '<')) {
            $result = $this->View->response->type();
        } else {
            $result = $this->View->response->getType();
        }
        $this->assertEquals('application/pdf', $result);

        $result = $this->View->pdfConfig;
        $this->assertEquals(['engine' => '\\' . __NAMESPACE__ . '\PdfTestEngine'], $result);

        $result = $this->View->renderer();
        $this->assertInstanceOf(CakePdf::class, $result);
    }

    /**
     * testRender
     *
     */
    public function testRender()
    {
        $this->View->setTemplatePath('Posts');
        $this->View->set('post', 'This is the post');
        $result = $this->View->render('view', 'default');

        $this->assertContains('<h2>Rendered with default layout</h2>', $result);
        $this->assertContains('Post data: This is the post', $result);
    }

    /**
     * testRenderWithDownload
     *
     * @return void
     */
    public function testRenderWithDownload()
    {
        $this->View->setTemplatePath('Posts');
        $this->View->set('post', 'This is the post');

        $this->View->pdfConfig['download'] = true;

        $result = $this->View->render('view', 'default');
        $this->assertContains('<h2>Rendered with default layout</h2>', $result);
        $this->assertContains('Post data: This is the post', $result);

        $this->assertContains('filename="posts.pdf"', $this->View->response->getHeaderLine('Content-Disposition'));
    }

    /**
     * testRenderWithFilename
     *
     * @return void
     */
    public function testRenderWithFilename()
    {
        $this->View->setTemplatePath('Posts');
        $this->View->set('post', 'This is the post');

        $this->View->pdfConfig['filename'] = 'booyah.pdf';

        $result = $this->View->render('view', 'default');
        $this->assertContains('<h2>Rendered with default layout</h2>', $result);
        $this->assertContains('Post data: This is the post', $result);

        $this->assertContains('filename="booyah.pdf"', $this->View->response->getHeaderLine('Content-Disposition'));
    }

    /**
     * Test rendering a template that does not generate any output
     *
     */
    public function testRenderTemplateWithNoOutput()
    {
        $this->View->setTemplatePath('Posts');
        $result = $this->View->render('empty', 'empty');
        $this->assertSame('', $result);
    }

    /**
     * Test rendering an Error template, which should  default to standard layout
     *
     */
    public function testRenderErrorTemplate()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = new PdfView($request, $response, null, [ 'templatePath' => 'Error' ]);

        $this->assertNull($this->View->subDir);
        $this->assertNull($this->View->layoutPath);

        if (version_compare(Configure::version(), '3.6.0', '<')) {
            $result = $this->View->response->type();
        } else {
            $result = $this->View->response->getType();
        }
        $this->assertEquals('text/html', $result);
    }
}
