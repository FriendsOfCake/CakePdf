<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use CakePdf\Pdf\CakePdf;

abstract class AbstractPdfEngine
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * Instance of CakePdf class
     *
     * @var \CakePdf\Pdf\CakePdf
     */
    protected CakePdf $_Pdf;

    /**
     * Constructor
     *
     * @param \CakePdf\Pdf\CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        $this->_Pdf = $Pdf;
    }

    /**
     * Implement in subclass to return raw pdf data.
     *
     * @return string
     */
    abstract public function output(): string;

    /**
     * Run a command, write `$input` to its stdin and collect its stdout and stderr.
     *
     * The three pipes are serviced concurrently with `stream_select()`. Reading stdout to EOF before stderr
     * deadlocks as soon as the command writes more than a pipe buffer (64 KiB on Linux) to stderr: the command
     * blocks writing stderr while PHP blocks reading stdout.
     *
     * @param string $cmd The command to execute
     * @param string $input Data to write to the command's stdin
     * @param string|null $cwd Working directory, null for the current one
     * @param string $name Name of the binary, for the error message
     * @return array{stdout: string, stderr: string, return: int}
     * @throws \Cake\Core\Exception\CakeException When the process cannot be started
     */
    protected function _runProcess(string $cmd, string $input, ?string $cwd = null, string $name = 'command'): array
    {
        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if ($proc === false) {
            throw new CakeException(sprintf('Unable to execute %s, proc_open() failed', $name));
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $stdin = $pipes[0];
        if ($input === '') {
            fclose($stdin);
            $stdin = null;
        }
        $outputs = [1 => '', 2 => ''];
        $open = [1 => $pipes[1], 2 => $pipes[2]];

        while ($open || $stdin !== null) {
            $read = array_values($open);
            $write = $stdin !== null ? [$stdin] : [];
            $except = null;
            if (stream_select($read, $write, $except, null) === false) {
                break;
            }

            if ($write) {
                $written = fwrite($stdin, $input);
                if ($written === false) {
                    $input = '';
                } else {
                    $input = substr($input, $written);
                }
                if ($input === '') {
                    fclose($stdin);
                    $stdin = null;
                }
            }

            foreach ($open as $index => $stream) {
                if (!in_array($stream, $read, true)) {
                    continue;
                }
                $chunk = fread($stream, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $outputs[$index] .= $chunk;
                } elseif (feof($stream)) {
                    fclose($stream);
                    unset($open[$index]);
                }
            }
        }

        if ($stdin !== null) {
            fclose($stdin);
        }
        foreach ($open as $stream) {
            fclose($stream);
        }

        return ['stdout' => $outputs[1], 'stderr' => $outputs[2], 'return' => proc_close($proc)];
    }
}
