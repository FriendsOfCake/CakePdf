<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\Core\Exception\Exception;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;

/**
 * WkHtmlToPdfEngineTest class
 */
class WkHtmlToPdfEngineTest extends TestCase
{
    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        if (!shell_exec('which wkhtmltopdf')) {
            $this->markTestSkipped('wkhtmltopdf not found');
        }

        $class = new \ReflectionClass(WkHtmlToPdfEngine::class);
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'quiet' => false,
                    'encoding' => 'ISO-8859-1',
                ],
            ],
            'title' => 'CakePdf rules',
        ]);

        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'ISO-8859-1' --title 'CakePdf rules' - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
            ],
            'margin' => [
                'bottom' => 0,
                'left' => 0,
                'right' => 0,
                'top' => 0,
            ],
            'title' => 'CakePdf rules',
        ]);

        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --title 'CakePdf rules' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'boolean' => true,
                    'string' => 'value',
                    'integer' => 42,
                    'array' => [
                        'first' => 'firstValue',
                        'second' => 'secondValue',
                    ],
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --boolean --string 'value' --integer '42' --array 'first' 'firstValue' --array 'second' 'secondValue' - -";
        $this->assertEquals($expected, $result);
    }

    public function testGetBinaryPath()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wkhtmltopdf binary is not found or not executable: /foo/bar');

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'binary' => '/foo/bar',
            ],
        ]);

        /** @var \CakePDF\Pdf\Engine\WkHtmlToPdfEngine $engine */
        $engine = $Pdf->engine();
        $engine->getBinaryPath();
    }
}
