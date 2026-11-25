<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Fagus\Document;
use Fagus\Engine as FagusWorker;
use Fagus\Fagus;
use Fagus\Pdf as FagusPdf;

/**
 * Engine for the `nforst/fagus` package.
 *
 * Fagus renders HTML and CSS like Chrome prints them and does not need a
 * browser, Node or any system library. Its native renderer is installed by the
 * package's Composer plugin.
 *
 * The `options` config key is passed to the `Fagus\Engine` constructor:
 * `binary`, `fontDirectories`, `sansSerif`, `serif`, `monospace`, `timeout`,
 * `systemFonts`, `locale`, `maxDocuments`, `maxMemory`, `onWarning`, `sandbox`,
 * `memoryBudget` and `timeLimit`.
 *
 * The `document` config key configures the document:
 * `baseDirectory`, `root`, `stylesheet`, `resources`, `pdfA`, `accessible`,
 * `tagged`, `forms`, `strict`, `reportLayout` and `created`.
 *
 * Page size, orientation, margins, headers, footers, the title and the
 * encoding come from the document's CSS and HTML (`@page`, `<title>`,
 * `<html lang>`), not from the CakePdf config, just as in a browser.
 */
class FagusEngine extends AbstractPdfEngine
{
    /**
     * The configured Fagus worker, reused for every document rendered by this engine.
     *
     * @var \Fagus\Engine|null
     */
    protected ?FagusWorker $instance = null;

    /**
     * Generates Pdf from html
     *
     * @return string raw pdf data
     * @throws \Cake\Core\Exception\CakeException If the Fagus package is not installed.
     */
    public function output(): string
    {
        if (!class_exists(Fagus::class)) {
            throw new CakeException(
                'The Fagus engine requires the `nforst/fagus` package. '
                . 'Install it with `composer require nforst/fagus`.',
            );
        }

        $document = $this->createDocument($this->_Pdf->html());
        $pdf = $this->render($document);

        return $this->outputPdf($pdf);
    }

    /**
     * Creates the Fagus document from the CakePdf instance and engine config.
     *
     * @param string $html The HTML to render.
     * @return \Fagus\Document
     */
    protected function createDocument(string $html): Document
    {
        $document = Fagus::html($html);
        $config = (array)$this->getConfig('document');

        if (isset($config['baseDirectory'])) {
            $document = $document->baseDirectory((string)$config['baseDirectory']);
        }

        if (isset($config['root'])) {
            $document = $document->root((string)$config['root']);
        }

        if (isset($config['stylesheet'])) {
            $document = $document->stylesheet((string)$config['stylesheet']);
        }

        foreach ((array)($config['resources'] ?? []) as $url => $data) {
            $document = $document->resource((string)$url, (string)$data);
        }

        if (isset($config['pdfA'])) {
            $level = $config['pdfA'] === true ? '3b' : (string)$config['pdfA'];
            $document = $document->pdfA($level);
        }

        if (isset($config['accessible'])) {
            $document = $document->accessible((bool)$config['accessible']);
        }

        if (isset($config['tagged'])) {
            $document = $document->tagged((bool)$config['tagged']);
        }

        if (isset($config['forms'])) {
            $document = $document->forms((bool)$config['forms']);
        }

        if (isset($config['strict'])) {
            $document = $document->strict((bool)$config['strict']);
        }

        if (isset($config['reportLayout'])) {
            $document = $document->reportLayout((bool)$config['reportLayout']);
        }

        if (isset($config['created'])) {
            $created = $config['created'];
            if (is_string($created)) {
                $created = new DateTimeImmutable($created);
            }
            if (!$created instanceof DateTimeInterface) {
                throw new CakeException('The `document.created` option must be a DateTimeInterface or a date string.');
            }
            $document = $document->created($created);
        }

        return $document;
    }

    /**
     * Creates the Fagus worker from the engine config.
     *
     * Returns `null` when no options are configured, so that the shared engine
     * from `Fagus::engine()` is used and its worker process is reused.
     *
     * @return \Fagus\Engine|null
     */
    protected function createInstance(): ?FagusWorker
    {
        if ($this->instance !== null) {
            return $this->instance;
        }

        $options = (array)$this->getConfig('options');
        if ($options === []) {
            return null;
        }

        $onWarning = $options['onWarning'] ?? null;
        if ($onWarning !== null && !$onWarning instanceof Closure) {
            throw new CakeException('The `options.onWarning` option must be a closure.');
        }

        $fontDirectories = array_values(array_map(
            static fn(mixed $directory): string => (string)$directory,
            (array)($options['fontDirectories'] ?? []),
        ));

        $this->instance = new FagusWorker(
            binary: isset($options['binary']) ? (string)$options['binary'] : null,
            fontDirectories: $fontDirectories,
            sansSerif: isset($options['sansSerif']) ? (string)$options['sansSerif'] : null,
            serif: isset($options['serif']) ? (string)$options['serif'] : null,
            monospace: isset($options['monospace']) ? (string)$options['monospace'] : null,
            timeout: isset($options['timeout']) ? (float)$options['timeout'] : 60.0,
            systemFonts: (bool)($options['systemFonts'] ?? false),
            locale: isset($options['locale']) ? (string)$options['locale'] : null,
            maxDocuments: isset($options['maxDocuments']) ? (int)$options['maxDocuments'] : null,
            maxMemory: isset($options['maxMemory']) ? (int)$options['maxMemory'] : null,
            onWarning: $onWarning,
            sandbox: (bool)($options['sandbox'] ?? true),
            memoryBudget: isset($options['memoryBudget']) ? (int)$options['memoryBudget'] : null,
            timeLimit: isset($options['timeLimit']) ? (float)$options['timeLimit'] : null,
        );

        return $this->instance;
    }

    /**
     * Renders the Fagus document.
     *
     * @param \Fagus\Document $document The document to render.
     * @return \Fagus\Pdf
     */
    protected function render(Document $document): FagusPdf
    {
        return $document->render($this->createInstance());
    }

    /**
     * Generates the PDF output.
     *
     * @param \Fagus\Pdf $pdf The rendered PDF.
     * @return string
     */
    protected function outputPdf(FagusPdf $pdf): string
    {
        return $pdf->bytes();
    }
}
