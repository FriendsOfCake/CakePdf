<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\FagusEngine;
use Fagus\Engine as FagusWorker;
use Fagus\Exception\InvalidArgumentException;
use Fagus\Fagus;
use Fagus\Pdf as FagusPdf;
use ReflectionMethod;

/**
 * FagusEngineTest class
 */
class FagusEngineTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Fagus::class)) {
            $this->markTestSkipped('Fagus is not installed');
        }
    }

    /**
     * Tests generating actual output.
     */
    public function testOutput(): void
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.Fagus',
        ]);
        $Pdf->html('<h1>Heading</h1><p>Hello <b>bold</b> and <i>italic</i> world.</p>');

        $output = $Pdf->engine()->output();
        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertStringContainsString('%%EOF', $output);
    }

    /**
     * Tests that the engine runs as expected.
     */
    public function testControlFlow(): void
    {
        $mock = $this->getMockBuilder(FagusEngine::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'createDocument',
                'render',
                'outputPdf',
            ])
            ->getMock();

        $Pdf = new CakePdf([
            'engine' => $mock,
        ]);

        $mock->__construct($Pdf);
        $Pdf->html('<p>test</p>');

        $document = Fagus::html('<p>test</p>');
        $pdf = new FagusPdf('%PDF-1.7 test', 1);

        $Engine = $Pdf->engine();
        $Engine
            ->expects($this->once())
            ->method('createDocument')
            ->with('<p>test</p>')
            ->willReturn($document);
        $Engine
            ->expects($this->once())
            ->method('render')
            ->with($document)
            ->willReturn($pdf);
        $Engine
            ->expects($this->once())
            ->method('outputPdf')
            ->with($pdf)
            ->willReturn('%PDF-1.7 test');

        $this->assertSame('%PDF-1.7 test', $Engine->output());
    }

    /**
     * Tests that the shared Fagus engine is used when no options are configured.
     */
    public function testCreateInstanceWithoutOptions(): void
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.Fagus',
        ]);

        $method = new ReflectionMethod($Pdf->engine(), 'createInstance');

        $this->assertNull($method->invoke($Pdf->engine()));
    }

    /**
     * Tests that the configured options create a Fagus engine instance.
     */
    public function testCreateInstanceWithOptions(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Fagus',
                'options' => [
                    'systemFonts' => true,
                    'locale' => 'de-DE',
                ],
            ],
        ]);

        $method = new ReflectionMethod($Pdf->engine(), 'createInstance');

        $this->assertInstanceOf(FagusWorker::class, $method->invoke($Pdf->engine()));
    }

    /**
     * Tests that an unknown PDF/A level is passed through to Fagus.
     */
    public function testInvalidPdfALevel(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Fagus',
                'document' => [
                    'pdfA' => 'invalid',
                ],
            ],
        ]);
        $Pdf->html('<p>test</p>');

        $this->expectException(InvalidArgumentException::class);

        $Pdf->engine()->output();
    }

    /**
     * Tests rendering an accessible document.
     */
    public function testAccessibleOutput(): void
    {
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Fagus',
                'document' => [
                    'accessible' => true,
                ],
            ],
        ]);
        $Pdf->html('<html lang="en"><head><title>Test</title></head><body><h1>Heading</h1></body></html>');

        $output = $Pdf->engine()->output();
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
