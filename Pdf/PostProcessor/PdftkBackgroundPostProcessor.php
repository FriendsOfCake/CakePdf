<?php
App::uses('AbstractPostProcessor', 'CakePdf.Pdf/PostProcessor');

class PdftkBackgroundPostProcessor extends AbstractPostProcessor {
/**
 * Path to the pdftk executable binary
 *
 * @access protected
 * @var string
 */
	protected $binary = '/usr/bin/pdftk';

	public function output($data) {
		if (!is_executable($this->binary)) {
			throw new CakeException(sprintf('pdftk binary is not found or not executable: %s', $this->binary));
		}
		$background = $this->config('background');
		if (empty($background)) {
			throw new CakeException('Background file is not present on the configuration.');
		}

		$command = sprintf('%s - background %s output -',
			$this->binary,
			$background
		);

		$descriptorspec = array(
			0 => array('pipe', 'r'), // feed stdin of process from this file descriptor
			1 => array('pipe', 'w'), // Note you can also grab stdout from a pipe, no need for temp file
			2 => array('pipe', 'w'), // stderr
		);

		$prochandle = proc_open($command, $descriptorspec, $pipes);

		fwrite($pipes[0], $data);
		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$exitcode = proc_close($prochandle);

		if ($exitcode !== 0) {
			throw new CakeException(sprintf('Pdftk: Unknown error (exit code %d)', $exitcode));
		}

		return $stdout;
	}
}
