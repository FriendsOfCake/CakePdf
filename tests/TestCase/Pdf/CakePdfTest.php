<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use TestApp\Pdf\Engine\PdfTestEngine;

class CakePdfTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Configure::delete('Pdf');
    }

    public static function provider()
    {
        return [
            [
                [
                    'engine' => PdfTestEngine::class,
                    'margin' => [
                        'bottom' => 15,
                        'left' => 50,
                        'right' => 30,
                        'top' => 45,
                    ],
                    'orientation' => 'landscape',
                ],
            ],
        ];
    }

    /**
     * Tests exception to be thrown for a non existing engine
     */
    public function testNonExistingEngineException()
    {
        $this->expectException(CakeException::class);

        $config = ['engine' => 'NonExistingEngine'];
        new CakePdf($config);
    }

    /**
     * testOutput
     *
     * @dataProvider provider
     */
    public function testOutput($config)
    {
        $pdf = new CakePdf($config);
        $pdf->viewVars(['data' => 'testing']);
        $pdf->template('testing', 'pdf');
        $result = $pdf->output();
        $expected = 'Data: testing';
        $this->assertEquals($expected, $result);

        $html = '<h2>Title</h2>';
        $result = $pdf->output($html);
        $this->assertEquals($html, $result);

        $html = '';
        $result = $pdf->output($html);
        $this->assertEquals($html, $result);
    }

    /**
     * Test the custom paths for Layouts
     *
     * @dataProvider provider
     */
    public function testCustomLayoutPaths($config)
    {
        $pdf = new CakePdf($config);
        $pdf->viewVars(['data' => 'testing']);
        $pdf->template('testing', 'pdf');
        $pdf->layoutPath('customPath');
        $result = $pdf->output();
        $expected = 'CustomLayoutData: testing';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the custom paths for Templates
     *
     * @dataProvider provider
     */
    public function testCustomTemplatePaths($config)
    {
        $pdf = new CakePdf($config);
        $pdf->viewVars(['post' => 'testing']);
        $pdf->template('view', 'default');
        $pdf->templatePath('Posts/pdf');
        $result = $pdf->output();
        $expected = '<h2>Rendered with default layout</h2>' . "\n" . 'Post data: testing';
        $this->assertTextEquals($expected, $result);
    }

    /**
     * testPluginOutput
     *
     * @dataProvider provider
     */
    public function testPluginOutput($config)
    {
        $pdf = new CakePdf($config);
        $this->loadPlugins(['MyPlugin']);
        $pdf->viewVars(['data' => 'testing']);
        $pdf->template('MyPlugin.testing', 'MyPlugin.pdf');
        $pdf->helpers(['MyPlugin.MyTest']);
        $result = $pdf->output();
        $expected = 'MyPlugin Layout Data: testing';
        $this->assertEquals($expected, $result);

        $pdf->template('MyPlugin.testing', 'MyPlugin.default');
        $result = $pdf->output();
        $lines = [
            '<h2>Rendered with default layout from MyPlugin</h2>',
            'MyPlugin view Data: testing',
            'MyPlugin Helper Test: successful',
        ];
        foreach ($lines as $line) {
            $this->assertStringContainsString($line, $result);
        }
    }

    /**
     * Tests that engine returns the proper object
     *
     * @dataProvider provider
     */
    public function testEngine($config)
    {
        $pdf = new CakePdf($config);
        $engine = $pdf->engine();
        $this->assertEquals(PdfTestEngine::class, get_class($engine));
    }

    /**
     * @dataProvider provider
     */
    public function testMargin($config)
    {
        $pdf = new CakePdf($config);
        $pdf->margin(15, 20, 25, 30);
        $expected = [
            'bottom' => 15,
            'left' => 20,
            'right' => 25,
            'top' => 30,
        ];
        $this->assertEquals($expected, $pdf->margin());

        $pdf = new CakePdf($config);
        $pdf->margin(75);
        $expected = [
            'bottom' => 75,
            'left' => 75,
            'right' => 75,
            'top' => 75,
        ];
        $this->assertEquals($expected, $pdf->margin());

        $pdf = new CakePdf($config);
        $pdf->margin(20, 50);
        $expected = [
            'bottom' => 20,
            'left' => 50,
            'right' => 50,
            'top' => 20,
        ];
        $this->assertEquals($expected, $pdf->margin());

        $pdf = new CakePdf($config);
        $pdf->margin(['left' => 120, 'right' => 30, 'top' => 34, 'bottom' => 15]);
        $expected = [
            'bottom' => 15,
            'left' => 120,
            'right' => 30,
            'top' => 34,
        ];
        $this->assertEquals($expected, $pdf->margin());

        $pdf = new CakePdf($config);
        $expected = [
            'bottom' => 15,
            'left' => 50,
            'right' => 30,
            'top' => 45,
        ];
        $this->assertEquals($expected, $pdf->margin());
    }

    /**
     * @dataProvider provider
     */
    public function testConfigRead($config)
    {
        Configure::write('CakePdf', $config);
        $pdf = new CakePdf();

        $this->assertEquals($config['margin'], $pdf->margin());
        $this->assertEquals($config['orientation'], $pdf->orientation());
    }
}
