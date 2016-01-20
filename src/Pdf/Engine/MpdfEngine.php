<?php
namespace CakePdf\Pdf\Engine;

class MpdfEngine extends AbstractPdfEngine
{

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output()
    {
        //mPDF often produces a whole bunch of errors, although there is a pdf created when debug = 0
        //Configure::write('debug', 0);
        $orientation = $this->_Pdf->orientation() == 'landscape' ? 'L' : 'P';
        $MPDF = new \mPDF($this->_Pdf->encoding(), $this->_Pdf->pageSize() . '-' . $orientation);
        $MPDF->writeHTML($this->_Pdf->html());
        return $MPDF->Output('', 'S');
    }
}
