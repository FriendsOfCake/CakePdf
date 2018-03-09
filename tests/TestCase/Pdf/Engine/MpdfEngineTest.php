<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\MpdfEngine;
use Cake\TestSuite\TestCase;
use Mpdf\Mpdf;

/**
 * MpdfEngineTest class
 */
class MpdfEngineTest extends TestCase
{

    /**
     * Tests that the engine sets the options properly.
     */
    public function testSetOptions()
    {
        $engineClass = $this->getMockClass(MpdfEngine::class, ['_createInstance']);

        $Pdf = new CakePdf([
            'engine' => [
                'className' => '\\' . $engineClass,
            ],
            'pageSize' => 'A4',
            'orientation' => 'landscape',
            'tempDir' => TMP,
        ]);
        $Pdf->html('');

        $Pdf
            ->engine()
            ->expects($this->once())
            ->method('_createInstance')
            ->will($this->returnCallback(function ($config) {
                $Mpdf = new Mpdf($config);

                $this->assertSame(TMP, $Mpdf->tempDir);
                $this->assertSame('L', $Mpdf->CurOrientation);

                return $Mpdf;
            }));

        $Pdf->engine()->output();
    }

    /**
     * Tests generating actual output.
     */
    public function testOutput()
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.Mpdf',
        ]);
        $Pdf->html('<foo>bar</foo>');

        $output = $Pdf->engine()->output();
        $this->assertNotEmpty($output);
    }
}
