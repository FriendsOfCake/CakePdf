<?php
declare(strict_types=1);

namespace TestApp\Pdf\Engine;

use CakePdf\Pdf\Engine\AbstractPdfEngine;

/**
 * Dummy engine
 */
class PdfTestEngine extends AbstractPdfEngine
{
    public function output(): string
    {
        return $this->_Pdf->html();
    }
}
