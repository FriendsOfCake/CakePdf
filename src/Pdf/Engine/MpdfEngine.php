<?php
namespace CakePdf\Pdf\Engine;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class MpdfEngine extends AbstractPdfEngine
{

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output()
    {
        $mpdf = new Mpdf([
            'mode' => $this->_Pdf->encoding(),
            'format' => $this->_Pdf->pageSize(),
            'orientation' => $this->_Pdf->orientation() === 'landscape' ? 'L' : 'P',
        ]);
        $mpdf->writeHTML($this->_Pdf->html());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
