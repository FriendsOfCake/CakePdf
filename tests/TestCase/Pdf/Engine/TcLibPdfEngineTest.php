<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use Com\Tecnick\Pdf\Tcpdf;
use ReflectionMethod;

/**
 * TcLibPdfEngineTest class
 */
class TcLibPdfEngineTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Tcpdf::class)) {
            $this->markTestSkipped('tc-lib-pdf is not loaded');
        }

        // tc-lib-pdf ships no fonts; point it at the generated core-font fixtures.
        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'fonts');
        }
    }

    /**
     * Tests that the page options honor the engine `options.margins` fallback.
     */
    public function testPageOptions(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TcLibPdf',
                'options' => [
                    'margins' => [
                        'left' => 10,
                        'top' => 20,
                        'right' => 30,
                        'bottom' => 40,
                    ],
                ],
            ],
            'pageSize' => 'A4',
            'orientation' => 'landscape',
        ]);

        $engine = $Pdf->engine();
        $method = new ReflectionMethod($engine, '_pageOptions');
        $options = $method->invoke($engine);

        $this->assertSame('A4', $options['format']);
        $this->assertSame('L', $options['orientation']);
        $this->assertSame(10.0, $options['margin']['PL']);
        $this->assertSame(20.0, $options['margin']['PT']);
        $this->assertSame(30.0, $options['margin']['PR']);
        $this->assertSame(40.0, $options['margin']['PB']);
        $this->assertSame(20.0, $options['margin']['CT']);
        $this->assertSame(40.0, $options['margin']['CB']);
    }

    /**
     * Tests that the standard CakePdf `margin` setting takes precedence over the
     * engine `options.margins` fallback.
     */
    public function testStandardMarginTakesPrecedence(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TcLibPdf',
                'options' => [
                    'margins' => [
                        'left' => 10,
                        'top' => 20,
                        'right' => 30,
                        'bottom' => 40,
                    ],
                ],
            ],
            'margin' => [
                'bottom' => 5,
                'left' => 6,
                'right' => 7,
                'top' => 8,
            ],
        ]);

        $engine = $Pdf->engine();
        $method = new ReflectionMethod($engine, '_pageOptions');
        $options = $method->invoke($engine);

        $this->assertSame(6.0, $options['margin']['PL']);
        $this->assertSame(8.0, $options['margin']['PT']);
        $this->assertSame(7.0, $options['margin']['PR']);
        $this->assertSame(5.0, $options['margin']['PB']);
    }

    /**
     * Tests that portrait orientation maps to 'P'.
     */
    public function testPortraitOrientation(): void
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.TcLibPdf',
            'orientation' => 'portrait',
        ]);

        $engine = $Pdf->engine();
        $method = new ReflectionMethod($engine, '_pageOptions');
        $options = $method->invoke($engine);

        $this->assertSame('P', $options['orientation']);
    }

    /**
     * Tests that the configured instance options are applied.
     */
    public function testCreateInstanceUsesConfig(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TcLibPdf',
                'options' => [
                    'unit' => 'pt',
                    'compress' => false,
                ],
            ],
        ]);

        $engine = $Pdf->engine();
        $this->assertSame('pt', $engine->getConfig('options.unit'));
        $this->assertFalse($engine->getConfig('options.compress'));

        $method = new ReflectionMethod($engine, '_createInstance');
        $instance = $method->invoke($engine);
        $this->assertInstanceOf(Tcpdf::class, $instance);
    }

    /**
     * Tests generating actual output.
     */
    public function testOutput(): void
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.TcLibPdf',
        ]);
        $Pdf->html('<h1>Heading</h1><p>Hello <b>bold</b> and <i>italic</i> world.</p>');

        $output = $Pdf->engine()->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
    }

    /**
     * Tests that long HTML flows across multiple pages.
     */
    public function testOutputMultiPage(): void
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.TcLibPdf',
        ]);
        $Pdf->html('<p>' . str_repeat('Lorem ipsum dolor sit amet. ', 600) . '</p>');

        $output = $Pdf->engine()->output();
        $this->assertStringStartsWith('%PDF-', $output);
    }

    /**
     * Tests that document metadata is applied without error.
     */
    public function testOutputWithMetadata(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TcLibPdf',
                'options' => [
                    'metadata' => [
                        'author' => 'CakePHP',
                        'creator' => 'CakePdf',
                        'subject' => 'Test',
                        'keywords' => 'pdf test',
                    ],
                ],
            ],
            'title' => 'My Document',
        ]);
        $Pdf->html('<p>With metadata.</p>');

        $output = $Pdf->engine()->output();
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
