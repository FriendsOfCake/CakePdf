<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WeasyPrintEngine;
use ReflectionClass;

/**
 * WeasyPrintEngineTest class
 */
class WeasyPrintEngineTest extends TestCase
{
    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        if (!shell_exec('which weasyprint')) {
            $this->markTestSkipped('weasyprint not found');
        }

        $class = new ReflectionClass(WeasyPrintEngine::class);
        $method = $class->getMethod('_getCommand');

        // Default options only
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WeasyPrint',
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --encoding 'UTF-8' - -";
        $this->assertEquals($expected, $result);

        // A falsy option (false) must be skipped; a string option must be included
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WeasyPrint',
                'options' => [
                    'quiet' => false,
                    'encoding' => 'UTF-8',
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --encoding 'UTF-8' - -";
        $this->assertEquals($expected, $result);

        // Various option types: boolean, string, integer, array
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WeasyPrint',
                'options' => [
                    'boolean' => true,
                    'string' => 'value',
                    'integer' => 42,
                    'array' => [
                        'firstValue',
                        'secondValue',
                    ],
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --encoding 'UTF-8' --boolean --string 'value' --integer '42' --array 'firstValue' --array 'secondValue' - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WeasyPrint',
                'options' => [
                    'presentational-hints' => true,
                    'pdf-variant' => 'pdf/ua-1',
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --encoding 'UTF-8' --presentational-hints --pdf-variant 'pdf/ua-1' - -";
        $this->assertEquals($expected, $result);
    }

    public function testGetBinaryPath()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('weasyprint binary is not found or not executable: /foo/bar');

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WeasyPrint',
                'binary' => '/foo/bar',
            ],
        ]);

        /** @var \CakePdf\Pdf\Engine\WeasyPrintEngine $engine */
        $engine = $Pdf->engine();
        $engine->getBinaryPath();
    }
}
