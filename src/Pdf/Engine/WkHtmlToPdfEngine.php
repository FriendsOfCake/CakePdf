<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Cake\Core\Exception\Exception;
use CakePdf\Pdf\CakePdf;

class WkHtmlToPdfEngine extends AbstractPdfEngine
{
    /**
     * Path to the wkhtmltopdf executable binary
     *
     * @var string
     */
    protected $_binary = 'wkhtmltopdf';

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
    public function output(): string
    {
        $command = $this->_getCommand();
        $content = $this->_exec($command, $this->_Pdf->html());

        if (!empty($content['stdout'])) {
            return $content['stdout'];
        }

        if (!empty($content['stderr'])) {
            throw new Exception(sprintf(
                'System error "%s" when executing command "%s". ' .
                'Try using the binary/package provided on http://wkhtmltopdf.org/downloads.html',
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
    protected function _exec(string $cmd, string $input): array
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
    protected function _getCommand(): string
    {
        $binary = $this->getBinaryPath();

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
            $command = '"' . $binary . '"';
        } else {
            $command = $binary;
        }

        foreach ($options as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $command .= $this->parseOptions($key, $value);
        }
        $footer = $this->_Pdf->footer();
        foreach ($footer as $location => $text) {
            if ($text !== null) {
                $command .= " --footer-$location \"" . addslashes($text) . '"';
            }
        }

        $header = $this->_Pdf->header();
        foreach ($header as $location => $text) {
            if ($text !== null) {
                $command .= " --header-$location \"" . addslashes($text) . '"';
            }
        }
        $command .= ' - -';

        if ($this->_windowsEnvironment && PHP_MAJOR_VERSION < 8) {
            $command = '"' . $command . '"';
        }

        return $command;
    }

    /**
     * Parses a value of options to create a part of the command.
     * Created to reuse logic to parse the cover and toc options.
     *
     * @param string $key the option key name
     * @param string|true|array|float $value the option value
     * @return string part of the command
     */
    protected function parseOptions(string $key, $value): string
    {
        $command = '';
        if (is_array($value)) {
            if ($key === 'toc') {
                $command .= ' toc';
                foreach ($value as $k => $v) {
                    $command .= $this->parseOptions($k, $v);
                }
            } elseif ($key === 'cover') {
                if (!isset($value['url'])) {
                    throw new Exception('The url for the cover is missing. Use the "url" index.');
                }
                $command .= ' cover ' . escapeshellarg((string)$value['url']);
                unset($value['url']);
                foreach ($value as $k => $v) {
                    $command .= $this->parseOptions($k, $v);
                }
            } else {
                foreach ($value as $k => $v) {
                    $command .= sprintf(' --%s %s %s', $key, escapeshellarg($k), escapeshellarg((string)$v));
                }
            }
        } elseif ($value === true) {
            if ($key === 'toc') {
                $command .= ' toc';
            } else {
                $command .= ' --' . $key;
            }
        } else {
            if ($key === 'cover') {
                $command .= ' cover ' . escapeshellarg((string)$value);
            } else {
                $command .= sprintf(' --%s %s', $key, escapeshellarg((string)$value));
            }
        }

        return $command;
    }

    /**
     * Get path to wkhtmltopdf binary.
     *
     * @return string
     */
    public function getBinaryPath(): string
    {
        $binary = $this->getConfig('binary', $this->_binary);

        /** @psalm-suppress ForbiddenCode */
        if (
            is_executable($binary) ||
            (!$this->_windowsEnvironment && shell_exec('which ' . escapeshellarg($binary)))
        ) {
            return $binary;
        }

        throw new Exception(sprintf('wkhtmltopdf binary is not found or not executable: %s', $binary));
    }
}
