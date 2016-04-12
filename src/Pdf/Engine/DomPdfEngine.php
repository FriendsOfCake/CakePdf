<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Dompdf\Dompdf;

class DomPdfEngine extends AbstractPdfEngine
{

    /**
     * Constructor
     *
     * @param CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        parent::__construct($Pdf);
    }

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output()
    {
        $defaults = [
            'fontCache' => TMP,
            'tempDir' => TMP
        ];
        $options = (array)$this->config('options') + $defaults;

        $DomPDF = new Dompdf($options);
        $DomPDF->setPaper($this->_Pdf->pageSize(), $this->_Pdf->orientation());
        $DomPDF->loadHtml($this->_Pdf->html());
        $DomPDF->render();
        return $DomPDF->output();
    }
}
