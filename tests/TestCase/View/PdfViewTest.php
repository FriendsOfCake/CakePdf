<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\View\PdfView;
use TestApp\Pdf\Engine\PdfTestEngine;

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
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('CakePdf', [
            'engine' => PdfTestEngine::class,
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
            $result = $this->View->getResponse()->type();
        } else {
            $result = $this->View->getResponse()->getType();
        }
        $this->assertEquals('application/pdf', $result);

        $result = $this->View->getConfig('pdfConfig');
        $this->assertEquals(['engine' => PdfTestEngine::class], $result);

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

        $this->View->setConfig('pdfConfig.download', true);

        $result = $this->View->render('view', 'default');
        $this->assertContains('<h2>Rendered with default layout</h2>', $result);
        $this->assertContains('Post data: This is the post', $result);

        $this->assertContains('filename="posts.pdf"', $this->View->getResponse()->getHeaderLine('Content-Disposition'));
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

        $this->View->setConfig('pdfConfig.filename', 'booyah.pdf');

        $result = $this->View->render('view', 'default');
        $this->assertContains('<h2>Rendered with default layout</h2>', $result);
        $this->assertContains('Post data: This is the post', $result);

        $this->assertContains(
            'filename="booyah.pdf"',
            $this->View->getResponse()->getHeaderLine('Content-Disposition')
        );
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
        $this->View = new PdfView($request, $response, null, ['templatePath' => 'Error']);

        $this->assertSame('', $this->View->getSubDir());
        $this->assertSame('', $this->View->getLayoutPath());

        $result = $this->View->getResponse()->getType();
        $this->assertEquals('text/html', $result);
    }
}
