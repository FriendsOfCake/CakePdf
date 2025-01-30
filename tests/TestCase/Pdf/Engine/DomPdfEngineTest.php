<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\DomPdfEngine;
use Dompdf\Dompdf;

/**
 * DomPdfEngineTest class
 */
class DomPdfEngineTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Dompdf::class)) {
            $this->markTestSkipped('Dompdf is not loaded');
        }
    }

    /**
     * Tests that the engine receives the expected options.
     */
    public function testReceiveOptions()
    {
        $mock = $this->getMockBuilder(DomPdfEngine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_createInstance'])
            ->getMock();

        $Pdf = new CakePdf([
            'engine' => [
                'className' => $mock,
                'options' => [
                    'isJavascriptEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                ],
            ],
        ]);

        $mock->__construct($Pdf);

        $expected = [
            'fontCache' => TMP,
            'tempDir' => TMP,
            'isJavascriptEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ];

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_createInstance')
            ->with($expected)
            ->willReturnCallback(function ($options) use ($expected) {
                $this->assertEquals($expected, $options);

                return new Dompdf($options);
            });

        $Pdf->engine()->output();
    }

    /**
     * Tests that the engine sets the options properly.
     */
    public function testSetOptions()
    {
        $mock = $this->getMockBuilder(DomPdfEngine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_output'])
            ->getMock();

        $Pdf = new CakePdf([
            'engine' => [
                'className' => $mock,
                'options' => [
                    'isJavascriptEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                ],
            ],
        ]);

        $mock->__construct($Pdf);

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_output')
            ->willReturnCallback(function ($Dompdf) {
                $Options = $Dompdf->getOptions();
                $this->assertEquals(TMP, $Options->getFontCache());
                $this->assertEquals(TMP, $Options->getTempDir());
                $this->assertFalse($Options->getIsJavascriptEnabled());
                $this->assertTrue($Options->getIsHtml5ParserEnabled());

                return $Dompdf->output();
            });

        $Pdf->engine()->output();
    }

    /**
     * Tests generating actual output.
     */
    public function testOutput()
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.Dompdf',
        ]);
        $Pdf->html('<foo>bar</foo>');

        $output = $Pdf->engine()->output();
        $this->assertStringStartsWith('%PDF-1.7', $output);
        $this->assertStringEndsWith("%%EOF\n", $output);
    }

    /**
     * Tests that the engine runs as expected.
     */
    public function testControlFlow()
    {
        $mock = $this->getMockBuilder(DomPdfEngine::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                '_createInstance',
                '_render',
                '_output',
            ])
            ->getMock();

        $Pdf = new CakePdf([
            'engine' => $mock,
        ]);

        $mock->__construct($Pdf);

        $DomPDF = new Dompdf();

        $Engine = $Pdf->engine();
        $Engine
            ->expects($this->once())
            ->method('_createInstance')
            ->willReturn($DomPDF);
        $Engine
            ->expects($this->once())
            ->method('_render')
            ->with($Pdf, $DomPDF)
            ->willReturn($DomPDF);
        $Engine
            ->expects($this->once())
            ->method('_output')
            ->with($DomPDF);

        $this->assertSame('', $Engine->output());
    }

    /**
     * Tests that the Dompdf instance is being processed as expected.
     */
    public function testDompdfControlFlow()
    {
        $mock = $this->getMockBuilder(DomPdfEngine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_createInstance'])
            ->getMock();

        $Pdf = new CakePdf([
            'engine' => $mock,
        ]);

        $mock->__construct($Pdf);

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_createInstance')
            ->willReturnCallback(function ($options) {
                $Dompdf = $this->getMockBuilder(Dompdf::class)
                    ->onlyMethods(['setPaper', 'loadHtml', 'render', 'output'])
                    ->setConstructorArgs([$options])
                    ->getMock();
                $Dompdf
                    ->expects($this->once())
                    ->method('setPaper')
                    ->with('A4', 'portrait');
                $Dompdf
                    ->expects($this->once())
                    ->method('loadHtml')
                    ->with(null);
                $Dompdf
                    ->expects($this->once())
                    ->method('render');
                $Dompdf
                    ->expects($this->once())
                    ->method('output');

                return $Dompdf;
            });

        $Pdf->engine()->output();
    }
}
