<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\DomPdfEngine;
use Cake\TestSuite\TestCase;
use Dompdf\Dompdf;

/**
 * DomPdfEngineTest class
 */
class DomPdfEngineTest extends TestCase
{

    /**
     * Tests that the engine receives the expected options.
     */
    public function testReceiveOptions()
    {
        $engineClass = $this->getMockClass(DomPdfEngine::class, ['_createInstance']);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => '\\' . $engineClass,
                'options' => [
                    'isJavascriptEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                ],
            ],
        ]);

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
            ->will($this->returnCallback(function ($options) use ($expected) {
                $this->assertEquals($expected, $options);

                return new Dompdf($options);
            }));

        $Pdf->engine()->output();
    }

    /**
     * Tests that the engine sets the options properly.
     */
    public function testSetOptions()
    {
        $engineClass = $this->getMockClass(DomPdfEngine::class, ['_output']);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => '\\' . $engineClass,
                'options' => [
                    'isJavascriptEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                ],
            ],
        ]);

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_output')
            ->will($this->returnCallback(function ($Dompdf) {
                $Options = $Dompdf->getOptions();
                $this->assertEquals(TMP, $Options->getFontCache());
                $this->assertEquals(TMP, $Options->getTempDir());
                $this->assertFalse($Options->getIsJavascriptEnabled());
                $this->assertTrue($Options->getIsHtml5ParserEnabled());

                return $Dompdf->output();
            }));

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
        $this->assertStringStartsWith('%PDF-1.3', $output);
        $this->assertStringEndsWith("%%EOF\n", $output);
    }

    /**
     * Tests that the engine runs as expected.
     */
    public function testControlFlow()
    {
        $engineClass = $this->getMockClass(DomPdfEngine::class, [
            '_createInstance',
            '_render',
            '_output',
        ]);

        $Pdf = new CakePdf([
            'engine' => '\\' . $engineClass,
        ]);

        $DomPDF = new Dompdf();

        $Engine = $Pdf->engine();
        $Engine
            ->expects($this->at(0))
            ->method('_createInstance')
            ->willReturn($DomPDF);
        $Engine
            ->expects($this->at(1))
            ->method('_render')
            ->with($Pdf, $DomPDF)
            ->willReturn($DomPDF);
        $Engine
            ->expects($this->at(2))
            ->method('_output')
            ->with($DomPDF);

        $this->assertNull($Engine->output());
    }

    /**
     * Tests that the Dompdf instance is being processed as expected.
     */
    public function testDompdfControlFlow()
    {
        $engineClass = $this->getMockClass(DomPdfEngine::class, ['_createInstance']);

        $Pdf = new CakePdf([
            'engine' => '\\' . $engineClass,
        ]);

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_createInstance')
            ->will($this->returnCallback(function ($options) {
                $Dompdf = $this->getMockBuilder('\Dompdf\Dompdf')
                    ->setMethods(['setPaper', 'loadHtml', 'render', 'output'])
                    ->setConstructorArgs([$options])
                    ->getMock();
                $Dompdf
                    ->expects($this->at(0))
                    ->method('setPaper')
                    ->with('A4', 'portrait');
                $Dompdf
                    ->expects($this->at(1))
                    ->method('loadHtml')
                    ->with(null);
                $Dompdf
                    ->expects($this->at(2))
                    ->method('render');
                $Dompdf
                    ->expects($this->at(3))
                    ->method('output');

                return $Dompdf;
            }));

        $Pdf->engine()->output();
    }
}
