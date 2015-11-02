<?php
namespace CakePdf\Pdf\Engine;

class TcpdfEngine extends AbstractPdfEngine
{

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output()
    {
        //TCPDF often produces a whole bunch of errors, although there is a pdf created when debug = 0
        //Configure::write('debug', 0);
        $TCPDF = new \TCPDF($this->_Pdf->orientation(), 'mm', $this->_Pdf->pageSize());
        $TCPDF->AddPage();
        $TCPDF->writeHTML($this->_Pdf->html());
        return $TCPDF->Output('', 'S');
    }
}
