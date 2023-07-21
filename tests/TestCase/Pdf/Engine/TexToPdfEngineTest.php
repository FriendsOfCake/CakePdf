<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\TexToPdfEngine;
use ReflectionClass;

/**
 * TexToPdfEngineTest class
 */
class TexToPdfEngineTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!is_executable('/usr/bin/latexpdf')) {
            $this->markTestSkipped('/usr/bin/latexpdf not found');
        }
    }

    /**
     * Tests that the engine generates the right command
     */
    public function testGetCommand()
    {
        $class = new ReflectionClass(TexToPdfEngine::class);
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => 'CakePdf.TexToPdf',
            ],
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
