<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\Core\Exception\Exception;
use Cake\Filesystem\File;

class WkHtmlToPdfEngine extends AbstractPdfEngine
{

    /**
     * Path to the wkhtmltopdf executable binary
     *
     * @access protected
     * @var string
     */
    protected $_binary = '/usr/bin/wkhtmltopdf';

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
     * @throws \Cake\Core\Exception\Exception
     * @return string raw pdf data
     */
    public function output()
    {
        $content = $this->_exec($this->_getCommand(), $this->_Pdf->html());

        if (strpos(mb_strtolower($content['stderr']), 'error')) {
            throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");
        }

        if (mb_strlen($content['stdout'], $this->_Pdf->encoding()) === 0) {
            throw new Exception("WKHTMLTOPDF didn't return any data");
        }

        if ((int)$content['return'] !== 0 && !empty($content['stderr'])) {
            throw new Exception("Shell error, return code: " . (int)$content['return']);
        }

        return $content['stdout'];
    }

    /**
     * Execute the WkHtmlToPdf commands for rendering pdfs
     *
     * @param string $cmd the command to execute
     * @param string $input Html to pass to wkhtmltopdf
     * @return string the result of running the command to generate the pdf
     */
    protected function _exec($cmd, $input)
    {
        $result = ['stdout' => '', 'stderr' => '', 'return' => ''];

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $result['stdout'] = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $result['stderr'] = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $result['return'] = proc_close($proc);

        return $result;
    }

    /**
     * Get the command to render a pdf
     *
     * @return string the command for generating the pdf
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _getCommand()
    {
        $binary = $this->config('binary');

        if ($binary) {
            $this->_binary = $binary;
        }
        if (!is_executable($this->_binary)) {
            throw new Exception(sprintf('wkhtmltopdf binary is not found or not executable: %s', $this->_binary));
        }

        $options = [
            'quiet' => true,
            'print-media-type' => true,
            'orientation' => $this->_Pdf->orientation(),
            'page-size' => $this->_Pdf->pageSize(),
            'encoding' => $this->_Pdf->encoding(),
            'title' => $this->_Pdf->title()
        ];

        $margin = $this->_Pdf->margin();
        foreach ($margin as $key => $value) {
            if ($value !== null) {
                $options['margin-' . $key] = $value . 'mm';
            }
        }
        $options = array_merge($options, (array)$this->config('options'));

        $command = $this->_binary;
        foreach ($options as $key => $value) {
            if (empty($value)) {
                continue;
            } elseif ($value === true) {
                $command .= ' --' . $key;
            } else {
                if ($key == 'header-html' || $key == 'footer-html') {
                    $value = $this->handleInlineHtmlBlock($key, $value);
                }
                $command .= sprintf(' --%s %s', $key, escapeshellarg($value));
            }
        }
        $footer = $this->_Pdf->footer();
        foreach ($footer as $location => $text) {
            if ($text !== null) {
                $command .= " --footer-$location \"" . addslashes($text) . "\"";
            }
        }
        $footerHtml = $this->_Pdf->footerHtml();
        if ($footerHtml !== null) {
            $command .= " --footer-html \"" . $this->handleInlineHtmlBlock("--footer-html", $footerHtml) . "\"";
        }
        $footerSpacing = $this->_Pdf->footerSpacing();
        if ($footerSpacing !== null) {
            $command .= " --footer-spacing \"" . $footerSpacing . "\"";
        }
        $header = $this->_Pdf->header();
        foreach ($header as $location => $text) {
            if ($text !== null) {
                $command .= " --header-$location \"" . addslashes($text) . "\"";
            }
        }
        $headerHtml = $this->_Pdf->headerHtml();
        if ($headerHtml !== null) {
            $command .= " --header-html \"" . $this->handleInlineHtmlBlock("--header-html", $headerHtml) . "\"";
        }
        $headerSpacing = $this->_Pdf->headerSpacing();
        if ($headerSpacing !== null) {
            $command .= " --header-spacing \"" . $headerSpacing . "\"";
        }
        $command .= " - -";

        return $command;
    }

    /**
     * Convert a HTML block, passed in as text, into a temporary HTML file,
     * which can be requested and rendered via wkhtmltopdf
     *
     *   input: <p>Some HTML here</p>
     *   output: app/tmp/cache/cakepdf-header-html-52bf266917d266accbb0b794fae83062.html
     *
     * Config:
     *   'webroot-temp-disable-wrapper' (boolean) if true, we will not wrap content block
     *                                            in html/JS recommended by wkhtmltopdf
     *
     * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
     * @param string $key string to add for cache file
     * @param string $content either a HTML block or a URL to a HTML fragment document
     * @return string $url to a HTML fragment document
     * @throws Exception
     */
    public function handleInlineHtmlBlock($key, $content)
    {
        if (substr($content, 0, 4) == 'http' || substr($content, -5) == '.html') {
            return $content;
        }
        $prefix = 'cakepdf-';
        $filename = $prefix . $key . '-' . md5($content) . '.html';
        if (defined('CACHE') && is_dir(CACHE) && is_writeable(CACHE)) {
            $filepath = CACHE . $filename;
        } else {
            $filepath = TMP . $filename;
        }

        $File = new File($filepath, true, 0777);
        if (!$File->exists()) {
            throw new Exception('Unable to make temp file for PDF rendering: ' . $key);
        }
        if (!$this->config('webroot-temp-disable-wrapper')) {
            $content = sprintf('<!DOCTYPE html><html><head><script>' .
                'function subst() { var vars={}; var x=window.location.search.substring(1).split("&"); for (var i in x) {var z=x[i].split("=",2);vars[z[0]] = unescape(z[1]);} var x=["frompage","topage","page","webpage","section","subsection","subsubsection"]; for (var i in x) { var y = document.getElementsByClassName(x[i]); for (var j=0; j<y.length; ++j) y[j].textContent = vars[x[i]]; } }' .
                '</script></head><body style="border:0; margin: 0;padding: 0;line-height: 1;vertical-align: baseline;" onload="subst()">%s</body></html>',
                $content
            );
        }
        $File->write($content);

        return $filepath;
    }
}
