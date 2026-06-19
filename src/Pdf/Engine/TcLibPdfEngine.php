<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Com\Tecnick\Pdf\Tcpdf;

/**
 * Engine for the actively maintained `tecnickcom/tc-lib-pdf` library, the
 * successor of the now deprecated `tecnickcom/tcpdf` package used by
 * {@see \CakePdf\Pdf\Engine\TcpdfEngine}.
 *
 * Unlike the old TCPDF engine, tc-lib-pdf ships no fonts. The standard PDF
 * core fonts (Helvetica, Times, Courier, Symbol, ZapfDingbats) and any custom
 * font must be available as generated `*.json` font files in a directory
 * pointed to by the `K_PATH_FONTS` constant. Define it once during bootstrap:
 *
 * ```php
 * define('K_PATH_FONTS', '/path/to/generated/fonts');
 * ```
 *
 * See the tc-lib-pdf-font documentation for how to generate font files.
 *
 * Engine specific options are read from the `options` config key:
 *
 * - `unit`: Document unit of measure. Defaults to `mm`.
 * - `unicode`: Whether the document is in Unicode mode. Defaults to `true`.
 * - `subsetFont`: Whether to subset embedded fonts. Defaults to `false`.
 * - `compress`: Whether to compress the PDF output. Defaults to `true`.
 * - `mode`: PDF conformance mode (e.g. `pdfa2b`, `pdfx4`). Defaults to `''`.
 * - `font`: Default font as `['family' => ..., 'style' => ..., 'size' => ...]`.
 * - `margins`: Fallback page margins in document units as
 *   `['left' => ..., 'top' => ..., 'right' => ..., 'bottom' => ...]`. The
 *   standard CakePdf `margin` setting takes precedence when set.
 * - `metadata`: Document metadata as
 *   `['author' => ..., 'creator' => ..., 'subject' => ..., 'keywords' => ...]`.
 */
class TcLibPdfEngine extends AbstractPdfEngine
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'options' => [
            'unit' => 'mm',
            'unicode' => true,
            'subsetFont' => false,
            'compress' => true,
            'mode' => '',
            'font' => [
                'family' => 'helvetica',
                'style' => '',
                'size' => 10,
            ],
            'margins' => [
                'left' => 15,
                'top' => 15,
                'right' => 15,
                'bottom' => 15,
            ],
            'metadata' => [],
        ],
    ];

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     */
    public function output(): string
    {
        $tcpdf = $this->_createInstance();

        $this->_applyMetadata($tcpdf);
        $tcpdf->enableDefaultPageContent();

        $font = (array)$this->getConfig('options.font');
        $defaultFont = $tcpdf->font->insert(
            $tcpdf->pon,
            (string)($font['family'] ?? 'helvetica'),
            (string)($font['style'] ?? ''),
            (float)($font['size'] ?? 10),
        );

        $tcpdf->addPage($this->_pageOptions());
        $tcpdf->page->addContent($defaultFont['out']);

        $region = $tcpdf->page->getRegion();
        $tcpdf->addHTMLCell(
            html: $this->_Pdf->html(),
            posx: (float)$region['RX'],
            posy: (float)$region['RY'],
            width: (float)$region['RW'],
        );

        return $tcpdf->getOutPDFString();
    }

    /**
     * Creates the tc-lib-pdf instance.
     *
     * @return \Com\Tecnick\Pdf\Tcpdf
     */
    protected function _createInstance(): Tcpdf
    {
        return new Tcpdf(
            unit: (string)$this->getConfig('options.unit'),
            isunicode: (bool)$this->getConfig('options.unicode'),
            subsetfont: (bool)$this->getConfig('options.subsetFont'),
            compress: (bool)$this->getConfig('options.compress'),
            mode: (string)$this->getConfig('options.mode'),
        );
    }

    /**
     * Builds the page options array from the CakePdf instance and engine config.
     *
     * The standard CakePdf `margin` setting takes precedence; the engine
     * `options.margins` config is used as a fallback for any unset side.
     *
     * @return array<string, mixed>
     */
    protected function _pageOptions(): array
    {
        $orientation = $this->_Pdf->orientation() === 'landscape' ? 'L' : 'P';

        $fallback = (array)$this->getConfig('options.margins');
        $margin = $this->_Pdf->margin();
        $left = (float)($margin['left'] ?? $fallback['left'] ?? 0);
        $top = (float)($margin['top'] ?? $fallback['top'] ?? 0);
        $right = (float)($margin['right'] ?? $fallback['right'] ?? 0);
        $bottom = (float)($margin['bottom'] ?? $fallback['bottom'] ?? 0);

        return [
            'format' => $this->_Pdf->pageSize(),
            'orientation' => $orientation,
            'margin' => [
                'PL' => $left,
                'PR' => $right,
                'PT' => $top,
                'PB' => $bottom,
                'CT' => $top,
                'CB' => $bottom,
            ],
        ];
    }

    /**
     * Applies document metadata to the tc-lib-pdf instance.
     *
     * @param \Com\Tecnick\Pdf\Tcpdf $tcpdf The tc-lib-pdf instance.
     * @return void
     */
    protected function _applyMetadata(Tcpdf $tcpdf): void
    {
        $title = $this->_Pdf->title();
        if ($title !== null && $title !== '') {
            $tcpdf->setTitle($title);
        }

        $metadata = (array)$this->getConfig('options.metadata');
        if (isset($metadata['author'])) {
            $tcpdf->setAuthor((string)$metadata['author']);
        }
        if (isset($metadata['creator'])) {
            $tcpdf->setCreator((string)$metadata['creator']);
        }
        if (isset($metadata['subject'])) {
            $tcpdf->setSubject((string)$metadata['subject']);
        }
        if (isset($metadata['keywords'])) {
            $tcpdf->setKeywords((string)$metadata['keywords']);
        }
    }
}
