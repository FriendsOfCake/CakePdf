<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;
use Cake\TestSuite\TestCase;

/**
 * WkHtmlToPdfEngineTest class
 */
class WkHtmlToPdfEngineTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        if (!is_executable('/usr/bin/wkhtmltopdf')) {
            $this->markTestSkipped('/usr/bin/wkhtmltopdf not found');
        }
    }

    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
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
        $expected = "/usr/bin/wkhtmltopdf --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'ISO-8859-1' --title 'CakePdf rules' - -";
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
        $expected = "/usr/bin/wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --title 'CakePdf rules' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' - -";
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
        $expected = "/usr/bin/wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --boolean --string 'value' --integer '42' --array 'first' 'firstValue' --array 'second' 'secondValue' - -";
        $this->assertEquals($expected, $result);
    }
}
