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
     * Reading stdout to EOF before stderr deadlocks as soon as the command writes more than a pipe buffer
     * (64 KiB on Linux) to stderr: the command blocks writing stderr while PHP blocks reading stdout. Likewise,
     * writing all of stdin before reading deadlocks when the command fills an output pipe before it has
     * consumed its input. The pipes are therefore serviced concurrently with `stream_select()`.
     *
     * `stream_select()` does not work on process pipes on Windows, so there stdout and stderr are redirected
     * to temporary files instead, which never block the command.
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
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->_runProcessWithFiles($cmd, $input, $cwd, $name);
        }

        return $this->_runProcessWithPipes($cmd, $input, $cwd, $name);
    }

    /**
     * Run a command servicing its stdin, stdout and stderr pipes concurrently with `stream_select()`.
     *
     * @param string $cmd The command to execute
     * @param string $input Data to write to the command's stdin
     * @param string|null $cwd Working directory, null for the current one
     * @param string $name Name of the binary, for the error message
     * @return array{stdout: string, stderr: string, return: int}
     * @throws \Cake\Core\Exception\CakeException When the process cannot be started
     */
    protected function _runProcessWithPipes(string $cmd, string $input, ?string $cwd, string $name): array
    {
        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if ($proc === false) {
            throw new CakeException(sprintf('Unable to execute %s, proc_open() failed', $name));
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $stdin = $pipes[0];
        $length = strlen($input);
        $offset = 0;
        if ($length === 0) {
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
                $written = fwrite($stdin, substr($input, $offset, 65536));
                $offset = $written === false ? $length : $offset + $written;
                if ($offset >= $length) {
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

    /**
     * Run a command with its stdout and stderr redirected to temporary files.
     *
     * Used where `stream_select()` is not supported on process pipes (Windows). Since the outputs go to files,
     * the command never blocks on them, and stdin can be written with blocking writes.
     *
     * @param string $cmd The command to execute
     * @param string $input Data to write to the command's stdin
     * @param string|null $cwd Working directory, null for the current one
     * @param string $name Name of the binary, for the error message
     * @return array{stdout: string, stderr: string, return: int}
     * @throws \Cake\Core\Exception\CakeException When the process cannot be started
     */
    protected function _runProcessWithFiles(string $cmd, string $input, ?string $cwd, string $name): array
    {
        $files = [1 => tempnam(sys_get_temp_dir(), 'cakepdf'), 2 => tempnam(sys_get_temp_dir(), 'cakepdf')];
        if ($files[1] === false || $files[2] === false) {
            foreach ($files as $file) {
                if ($file !== false) {
                    unlink($file);
                }
            }
            throw new CakeException(sprintf('Unable to execute %s, could not create temporary files', $name));
        }

        try {
            $descriptors = [0 => ['pipe', 'r'], 1 => ['file', $files[1], 'w'], 2 => ['file', $files[2], 'w']];
            $proc = proc_open($cmd, $descriptors, $pipes, $cwd);
            if ($proc === false) {
                throw new CakeException(sprintf('Unable to execute %s, proc_open() failed', $name));
            }

            $length = strlen($input);
            $offset = 0;
            while ($offset < $length) {
                $written = fwrite($pipes[0], substr($input, $offset, 65536));
                if ($written === false || $written === 0) {
                    break;
                }
                $offset += $written;
            }
            fclose($pipes[0]);

            $return = proc_close($proc);

            return [
                'stdout' => (string)file_get_contents($files[1]),
                'stderr' => (string)file_get_contents($files[2]),
                'return' => $return,
            ];
        } finally {
            unlink($files[1]);
            unlink($files[2]);
        }
    }
}
