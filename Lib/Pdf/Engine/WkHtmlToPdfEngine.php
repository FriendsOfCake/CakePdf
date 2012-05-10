<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');

class WkHtmlToPdfEngine extends AbstractPdfEngine {

/**
 * Path to the wkhtmltopdf executable binary
 *
 * @access protected
 * @var string
 */
	protected $binary = '/usr/bin/wkhtmltopdf';

/**
 * Constructor
 *
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
		$binary = $this->config('binary');

		if ($binary) {
			$this->binary = $binary;
		}

		if (!is_executable($this->binary)) {
			throw new Exception(sprintf('wkhtmltopdf binary is not found or not executable: %s', $this->binary));
		}
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		$content = $this->_exec($this->_getCommand(), $this->_Pdf->html());

		if (strpos(mb_strtolower($content['stderr']), 'error')) {
			throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");
		}

		if (mb_strlen($content['stdout'], 'utf-8') === 0) {
			throw new Exception("WKHTMLTOPDF didn't return any data");
		}

		if ((int)$content['return'] > 1) {
			throw new Exception("Shell error, return code: " . (int)$content['return']);
		}

		return $content['stdout'];
	}

/**
 * Execute the WkHtmlToPdf commands for rendering pdfs
 *
 * @param string $cmd the command to execute
 * @param string $input
 * @return string the result of running the command to generate the pdf
 */
	protected function _exec($cmd, $input) {
		$result = array('stdout' => '', 'stderr' => '', 'return' => '');

		$proc = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
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
 */
	protected function _getCommand() {
		$command = $this->binary;

		$command .= " --orientation " . $this->_pdf->orientation();
		$command .= " --page-size " . $this->_pdf->pageSize();

		$command .= " - -";

		return $command;
	}
}