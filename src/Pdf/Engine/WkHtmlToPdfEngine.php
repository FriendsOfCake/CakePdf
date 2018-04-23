<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\Core\Exception\Exception;

class WkHtmlToPdfEngine extends AbstractPdfEngine
{

    /**
     * Path to the wkhtmltopdf executable binary
     *
     * @var string
     */
    protected $_binary = '/usr/bin/wkhtmltopdf';

    /**
     * Flag to indicate if the environment is windows
     *
     * @var bool
     */
    protected $_windowsEnvironment;

    /**
     * Constructor
     *
     * @param \CakePdf\Pdf\CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        parent::__construct($Pdf);

        $this->_windowsEnvironment = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($this->_windowsEnvironment) {
            $this->_binary = 'C:/Progra~1/wkhtmltopdf/bin/wkhtmltopdf.exe';
        }
    }

    /**
     * Generates Pdf from html
     *
     * @throws \Cake\Core\Exception\Exception
     * @return string Raw PDF data
     * @throws \Exception If no output is generated to stdout by wkhtmltopdf.
     */
    public function output()
    {
        $command = $this->_getCommand();
        $content = $this->_exec($command, $this->_Pdf->html());

        if (!empty($content['stdout'])) {
            return $content['stdout'];
        }

        if (!empty($content['stderr'])) {
            throw new Exception(sprintf(
                'System error "%s" when executing command "%s". ' .
                'Try using the binary provided on http://wkhtmltopdf.org/downloads.html',
                $content['stderr'],
                $command
            ));
        }

        throw new Exception("WKHTMLTOPDF didn't return any data");
    }

    /**
     * Execute the WkHtmlToPdf commands for rendering pdfs
     *
     * @param string $cmd the command to execute
     * @param string $input Html to pass to wkhtmltopdf
     * @return array the result of running the command to generate the pdf
     */
    protected function _exec($cmd, $input)
    {
        $result = ['stdout' => '', 'stderr' => '', 'return' => ''];

        $cwd = $this->getConfig('cwd');

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
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
        $binary = $this->getConfig('binary');

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
            'title' => $this->_Pdf->title(),
            'javascript-delay' => $this->_Pdf->delay(),
            'window-status' => $this->_Pdf->windowStatus(),
        ];

        $margin = $this->_Pdf->margin();
        foreach ($margin as $key => $value) {
            if ($value !== null) {
                $options['margin-' . $key] = $value . 'mm';
            }
        }
        $options = array_merge($options, (array)$this->getConfig('options'));

        if ($this->_windowsEnvironment) {
            $command = '"' . $this->_binary . '"';
        } else {
            $command = $this->_binary;
        }

        foreach ($options as $key => $value) {
            if (empty($value)) {
                continue;
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    $command .= sprintf(' --%s %s %s', $key, escapeshellarg($k), escapeshellarg($v));
                }
            } elseif ($value === true) {
                $command .= ' --' . $key;
            } else {
                $command .= sprintf(' --%s %s', $key, escapeshellarg($value));
            }
        }
        $footer = $this->_Pdf->footer();
        foreach ($footer as $location => $text) {
            if ($text !== null) {
                $command .= " --footer-$location \"" . addslashes($text) . "\"";
            }
        }

        $header = $this->_Pdf->header();
        foreach ($header as $location => $text) {
            if ($text !== null) {
                $command .= " --header-$location \"" . addslashes($text) . "\"";
            }
        }
        $command .= " - -";

        if ($this->_windowsEnvironment) {
            $command = '"' . $command . '"';
        }

        return $command;
    }
}
