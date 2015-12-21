<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;
use Cake\TestSuite\TestCase;

/**
 * TexToPdfEngineTest class
 *
 * @package       CakePdf.Test.Case.Pdf.Engine
 */
class TexToPdfEngineTest extends TestCase
{

    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        $class = new \ReflectionClass('CakePdf\Pdf\Engine\TexToPdfEngine');
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TexToPdf',
            ]
        ]);

        $result = $method->invokeArgs($Pdf->engine(), []);
        if (DS === '\\') {
            $expected = '/usr/bin/latexpdf --output-directory "' . TMP . 'pdf"';
        } else {
            $expected = '/usr/bin/latexpdf --output-directory \'' . TMP . 'pdf\'';
        }
        $this->assertEquals($expected, $result);
    }
}
