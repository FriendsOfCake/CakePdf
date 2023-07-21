<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;
use ReflectionClass;

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

        $class = new ReflectionClass(WkHtmlToPdfEngine::class);
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

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'cover' => 'cover.html',
                    'toc' => true,
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' cover 'cover.html' toc - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'encoding' => 'UTF-16',
                    'title' => 'Test',
                    'cover' => 'cover.html',
                    'toc' => [
                        'zoom' => 5,
                        'encoding' => 'ISO-8859-1',
                    ],
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-16' --title 'Test' cover 'cover.html' toc --zoom '5' --encoding 'ISO-8859-1' - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.WkHtmlToPdf',
                'options' => [
                    'cover' => [
                        'url' => 'cover.html',
                        'enable-smart-shrinking' => true,
                        'zoom' => 10,
                    ],
                    'toc' => true,
                ],
            ],
        ]);
        $result = $method->invokeArgs($Pdf->engine(), []);
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' cover 'cover.html' --enable-smart-shrinking --zoom '10' toc - -";
        $this->assertEquals($expected, $result);

        $Pdf = new CakePdf([
           'engine' => [
               'className' => 'CakePdf.WkHtmlToPdf',
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
        $expected = "wkhtmltopdf --quiet --print-media-type --orientation 'portrait' --page-size 'A4' --encoding 'UTF-8' --zoom '4' cover 'cover.html' --enable-smart-shrinking --zoom '10' toc --disable-dotted-lines --xsl-style-sheet 'style.xsl' --zoom '5' --encoding 'ISO-8859-1' - -";
        $this->assertEquals($expected, $result);
    }

    public function testCoverUrlMissing()
    {
        if (!shell_exec('which wkhtmltopdf')) {
            $this->markTestSkipped('wkhtmltopdf not found');
        }

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The url for the cover is missing. Use the "url" index.');

        $class = new ReflectionClass(WkHtmlToPdfEngine::class);
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);
        $Pdf = new CakePdf([
           'engine' => [
               'className' => 'CakePdf.WkHtmlToPdf',
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
