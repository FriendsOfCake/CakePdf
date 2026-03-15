<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WeasyprintEngine;
use ReflectionClass;

/**
 * WeasyprintEngineTest class
 */
class WeasyprintEngineTest extends TestCase
{
    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        if (!shell_exec('which weasyprint')) {
            $this->markTestSkipped('weasyprint not found');
        }

        $class = new ReflectionClass(WeasyprintEngine::class);
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        // Default options only
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' - -";
        $this->assertEquals($expected, $result);

        // A falsy option (false) must be skipped; a string option must be included
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'options' => [
                    'quiet' => false,
                    'encoding' => 'UTF-8',
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' --encoding 'UTF-8' - -";
        $this->assertEquals($expected, $result);

        // With all margins set to 0
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
            ],
            'margin' => [
                'bottom' => 0,
                'left' => 0,
                'right' => 0,
                'top' => 0,
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' - -";
        $this->assertEquals($expected, $result);

        // Various option types: boolean, string, integer, associative array
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
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
        $expected = "weasyprint --dpi '96' --boolean --string 'value' --integer '42' --array 'first' 'firstValue' --array 'second' 'secondValue' - -";
        $this->assertEquals($expected, $result);

        // With footer and header
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
            ],
        ]);
        $Pdf->footer('Footer left');
        $Pdf->header(null, 'Page {page}');
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' --footer-left \"Footer left\" --header-center \"Page {page}\" - -";
        $this->assertEquals($expected, $result);

        // With cover (string) and toc (boolean true)
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'options' => [
                    'cover' => 'cover.html',
                    'toc' => true,
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' cover 'cover.html' toc - -";
        $this->assertEquals($expected, $result);

        // With cover (array) and toc (array of options)
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'options' => [
                    'cover' => [
                        'url' => 'cover.html',
                        'enable-smart-shrinking' => true,
                        'zoom' => 10,
                    ],
                    'toc' => [
                        'zoom' => 5,
                        'encoding' => 'ISO-8859-1',
                    ],
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' cover 'cover.html' --enable-smart-shrinking --zoom '10' toc --zoom '5' --encoding 'ISO-8859-1' - -";
        $this->assertEquals($expected, $result);

        // With global zoom override alongside cover (array) and toc (array)
        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'options' => [
                    'zoom' => 4,
                    'cover' => [
                        'url' => 'cover.html',
                        'enable-smart-shrinking' => true,
                        'zoom' => 10,
                    ],
                    'toc' => [
                        'disable-dotted-lines' => true,
                        'xsl-style-sheet' => 'style.xsl',
                        'zoom' => 5,
                        'encoding' => 'ISO-8859-1',
                    ],
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "weasyprint --dpi '96' --zoom '4' cover 'cover.html' --enable-smart-shrinking --zoom '10' toc --disable-dotted-lines --xsl-style-sheet 'style.xsl' --zoom '5' --encoding 'ISO-8859-1' - -";
        $this->assertEquals($expected, $result);
    }

    public function testCoverUrlMissing()
    {
        if (!shell_exec('which weasyprint')) {
            $this->markTestSkipped('weasyprint not found');
        }

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The url for the cover is missing. Use the "url" index.');

        $class = new ReflectionClass(WeasyprintEngine::class);
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'options' => [
                    'cover' => [
                        'enable-smart-shrinking' => true,
                        'zoom' => 10,
                    ],
                ],
            ],
        ]);
        $method->invokeArgs($Pdf->engine(), []);
    }

    public function testGetBinaryPath()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('weasyprint binary is not found or not executable: /foo/bar');

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.Weasyprint',
                'binary' => '/foo/bar',
            ],
        ]);

        /** @var \CakePdf\Pdf\Engine\WeasyprintEngine $engine */
        $engine = $Pdf->engine();
        $engine->getBinaryPath();
    }
}
