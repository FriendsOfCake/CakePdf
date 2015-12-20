<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;
use Cake\TestSuite\TestCase;

/**
 * WkHtmlToPdfEngineTest class
 *
 * @package       CakePdf.Test.Case.Pdf.Engine
 */
class WkHtmlToPdfEngineTest extends TestCase
{

    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        $class = new \ReflectionClass('CakePdf\Pdf\Engine\WkHtmlToPdfEngine');
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'quiet' => false,
                    'encoding' => 'ISO-8859-1'
                ]
            ],
            'title' => 'CakePdf rules',
        ]);

        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "/usr/bin/wkhtmltopdf --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'ISO-8859-1' --title 'CakePdf rules' - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'boolean' => true,
                    'string' => 'value',
                    'integer' => 42
                ]
            ]
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "/usr/bin/wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --boolean --string 'value' --integer '42' - -";
        $this->assertEquals($expected, $result);
    }
}
