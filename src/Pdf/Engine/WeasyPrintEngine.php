<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use CakePdf\Pdf\CakePdf;

class WeasyPrintEngine extends AbstractPdfEngine
{
    /**
     * Path to the weasyprint executable binary
     *
     * @var string
     */
    protected string $_binary = 'weasyprint';

    /**
     * Flag to indicate if the environment is windows
     *
     * @var bool
     */
    protected bool $_windowsEnvironment;

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
            $this->_binary = 'C:/Progra~1/WeasyPrint/bin/weasyprint.exe';
        }
    }

    /**
     * Generates Pdf from html
     *
     * @return string Raw PDF data
     * @throws \Cake\Core\Exception\CakeException If no output is generated to stdout by weasyprint.
     */
    public function output(): string
    {
        $command = $this->_getCommand();
        $content = $this->_exec($command, $this->_Pdf->html());

        if (!empty($content['stdout'])) {
            return $content['stdout'];
        }

        if (!empty($content['stderr'])) {
            throw new CakeException(sprintf(
                'System error "%s" when executing command "%s".',
                $content['stderr'],
                $command,
            ));
        }

        throw new CakeException("weasyprint didn't return any data");
    }

    /**
     * Execute the weasyprint commands for rendering pdfs
     *
     * @param string $cmd the command to execute
     * @param string $input Html to pass to weasyprint
     * @return array{stdout: string, stderr: string, return: int} the result of running the command to generate the pdf
     */
    protected function _exec(string $cmd, string $input): array
    {
        $result = ['stdout' => '', 'stderr' => '', 'return' => 0];

        $cwd = $this->getConfig('cwd');

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if ($proc === false) {
            throw new CakeException('Unable to execute weasyprint, proc_open() failed');
        }

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
     * @throws \Cake\Core\Exception\CakeException
     */
    protected function _getCommand(): string
    {
        $binary = $this->getBinaryPath();

        $options = [
            'encoding' => $this->_Pdf->encoding(),
        ];

        $options = array_merge($options, (array)$this->getConfig('options'));

        if ($this->_windowsEnvironment) {
            $command = '"' . $binary . '"';
        } else {
            $command = $binary;
        }

        foreach ($options as $key => $value) {
            if (!$value && $value !== 0 && $value !== '0') {
                continue;
            }
            $command .= $this->parseOptions($key, $value);
        }

        $command .= ' - -';

        return $command;
    }

    /**
     * Parses a value of options to create a part of the command.
     *
     * @param string $key the option key name
     * @param array<string, mixed>|string|float|int|true $value the option value
     * @return string part of the command
     */
    protected function parseOptions(string $key, string|bool|array|float|int $value): string
    {
        $command = '';
        if (is_array($value)) {
            foreach ($value as $v) {
                $command .= sprintf(' --%s %s', $key, escapeshellarg((string)$v));
            }
        } elseif ($value === true) {
            $command .= ' --' . $key;
        } else {
            $command .= sprintf(' --%s %s', $key, escapeshellarg((string)$value));
        }

        return $command;
    }

    /**
     * Get path to weasyprint binary.
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

        throw new CakeException(sprintf('weasyprint binary is not found or not executable: %s', $binary));
    }
}
