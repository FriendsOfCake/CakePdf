<?php
namespace CakePdf\Test\TestCase\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\TestSuite\TestCase;

/**
 * MpdfEngineTest class
 */
class MpdfEngineTest extends TestCase
{

    /**
     * Tests generating actual output.
     */
    public function testOutput()
    {
        $Pdf = new CakePdf([
            'engine' => 'CakePdf.Mpdf'
        ]);
        $Pdf->html('<foo>bar</foo>');

        $output = $Pdf->engine()->output();
        $this->assertNotEmpty($output);
    }
}
