<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use TCPDF;

class TcpdfEngine extends AbstractPdfEngine
{
    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output(): string
    {
        $TCPDF = new TCPDF($this->_Pdf->orientation(), 'mm', $this->_Pdf->pageSize());
        $TCPDF->AddPage();
        $TCPDF->writeHTML($this->_Pdf->html());

        return $TCPDF->Output('', 'S');
    }
}
