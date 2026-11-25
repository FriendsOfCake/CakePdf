<?php
declare(strict_types=1);

namespace CakePdf\Test\TestCase\Pdf\Engine;

use Cake\TestSuite\TestCase;
use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\AbstractPdfEngine;
use CakePdf\Pdf\Engine\TexToPdfEngine;
use CakePdf\Pdf\Engine\WeasyPrintEngine;
use CakePdf\Pdf\Engine\WkHtmlToPdfEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * The engines that run an external binary must read its stdout and stderr concurrently. Reading stdout to EOF
 * first deadlocks as soon as the binary writes more than a pipe buffer (64 KiB on Linux) to stderr: the binary
 * blocks writing stderr while PHP blocks reading stdout.
 */
class ProcessPipesTest extends TestCase
{
    /**
     * @return array<string, array{class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine>}>
     */
    public static function engineProvider(): array
    {
        return [
            'WeasyPrint' => [WeasyPrintEngine::class],
            'WkHtmlToPdf' => [WkHtmlToPdfEngine::class],
            'TexToPdf' => [TexToPdfEngine::class],
        ];
    }

    /**
     * A binary writing 240 KB to stderr completes, and both outputs and the stdin input are returned.
     *
     * @param class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine> $class Engine class
     */
    #[DataProvider('engineProvider')]
    public function testLargeStderrDoesNotDeadlock(string $class): void
    {
        $engine = new $class(new CakePdf([]));
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
            'fwrite(STDERR, str_repeat("warning\n", 30000)); echo "out:" . stream_get_contents(STDIN);',
        );

        $result = $this->exec($engine, $command, 'input');

        $this->assertSame('out:input', $result['stdout']);
        $this->assertSame(240000, strlen($result['stderr']));
        $this->assertSame(0, $result['return']);
    }

    /**
     * @return array<string, array{class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine>}>
     */
    public static function stdinEngineProvider(): array
    {
        // TexToPdf passes a .tex file path, not the document, and appends it to the command line.
        return [
            'WeasyPrint' => [WeasyPrintEngine::class],
            'WkHtmlToPdf' => [WkHtmlToPdfEngine::class],
        ];
    }

    /**
     * Large stdout (a big PDF) and a large stdin (a big HTML document) are passed through untouched.
     *
     * @param class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine> $class Engine class
     */
    #[DataProvider('stdinEngineProvider')]
    public function testLargeInputAndOutput(string $class): void
    {
        $engine = new $class(new CakePdf([]));
        $input = str_repeat('<p>html</p>', 50000);
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
            '$in = stream_get_contents(STDIN); fwrite(STDERR, "done"); echo strlen($in), ":", str_repeat("x", 300000);',
        );

        $result = $this->exec($engine, $command, $input);

        $this->assertSame(strlen($input) . ':' . str_repeat('x', 300000), $result['stdout']);
        $this->assertSame('done', $result['stderr']);
        $this->assertSame(0, $result['return']);
    }

    /**
     * A binary that fills its stdout and stderr pipes before it starts reading a stdin larger than a pipe buffer
     * completes. Writing all of stdin before reading the outputs deadlocks here: PHP blocks writing stdin while
     * the binary blocks writing stdout.
     *
     * @param class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine> $class Engine class
     */
    #[DataProvider('stdinEngineProvider')]
    public function testOutputBeforeLargeInputDoesNotDeadlock(string $class): void
    {
        $engine = new $class(new CakePdf([]));
        $input = str_repeat('<p>html</p>', 50000);
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
            'echo str_repeat("x", 300000); fwrite(STDERR, str_repeat("e", 300000));'
            . ' $in = stream_get_contents(STDIN); echo ":", strlen($in);',
        );

        $result = $this->exec($engine, $command, $input);

        $this->assertSame(str_repeat('x', 300000) . ':' . strlen($input), $result['stdout']);
        $this->assertSame(300000, strlen($result['stderr']));
        $this->assertSame(0, $result['return']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function runnerProvider(): array
    {
        return [
            'pipes' => ['_runProcessWithPipes'],
            'files (Windows)' => ['_runProcessWithFiles'],
        ];
    }

    /**
     * Both process runners pass large input, large stdout, large stderr and the exit code through untouched.
     */
    #[DataProvider('runnerProvider')]
    public function testRunner(string $runner): void
    {
        $engine = new WeasyPrintEngine(new CakePdf([]));
        $input = str_repeat('<p>html</p>', 50000);
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
            'echo str_repeat("x", 300000); fwrite(STDERR, str_repeat("e", 300000));'
            . ' $in = stream_get_contents(STDIN); echo ":", strlen($in); exit(3);',
        );

        $method = new ReflectionMethod($engine, $runner);
        $result = $method->invoke($engine, $command, $input, null, 'php');

        $this->assertSame(str_repeat('x', 300000) . ':' . strlen($input), $result['stdout']);
        $this->assertSame(str_repeat('e', 300000), $result['stderr']);
        $this->assertSame(3, $result['return']);

        $result = $method->invoke($engine, $command, '', null, 'php');
        $this->assertSame(str_repeat('x', 300000) . ':0', $result['stdout']);
    }

    /**
     * The exit code of the binary is returned.
     *
     * @param class-string<\CakePdf\Pdf\Engine\AbstractPdfEngine> $class Engine class
     */
    #[DataProvider('engineProvider')]
    public function testExitCode(string $class): void
    {
        $engine = new $class(new CakePdf([]));
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('fwrite(STDERR, "failed"); exit(3);');

        $result = $this->exec($engine, $command, 'input');

        $this->assertSame('', $result['stdout']);
        $this->assertSame('failed', $result['stderr']);
        $this->assertSame(3, $result['return']);
    }

    /**
     * @return array{stdout: string, stderr: string, return: int}
     */
    protected function exec(AbstractPdfEngine $engine, string $command, string $input): array
    {
        $method = new ReflectionMethod($engine, '_exec');

        return $method->invoke($engine, $command, $input);
    }
}
