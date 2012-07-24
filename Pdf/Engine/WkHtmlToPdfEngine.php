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
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 */
	public function output() {
		$content = $this->_exec($this->_getCommand(), $this->_Pdf->html());

		if (strpos(mb_strtolower($content['stderr']), 'error')) {
			throw new CakeException("System error <pre>" . $content['stderr'] . "</pre>");
		}

		if (mb_strlen($content['stdout'], $this->_Pdf->encoding()) === 0) {
			throw new CakeException("WKHTMLTOPDF didn't return any data");
		}

		if ((int)$content['return'] !== 0 && !empty($content['stderr'])) {
			throw new CakeException("Shell error, return code: " . (int)$content['return']);
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
		$binary = $this->config('binary');

		if ($binary) {
			$this->binary = $binary;
		}
		if (!is_executable($this->binary)) {
			throw new CakeException(sprintf('wkhtmltopdf binary is not found or not executable: %s', $this->binary));
		}
		$command = $this->binary;

		$command .= " -q";
		$command .= " --print-media-type";
		$command .= " --orientation " . $this->_Pdf->orientation();
		$command .= " --page-size " . $this->_Pdf->pageSize();
		$command .= " --encoding " . $this->_Pdf->encoding();

		$margin = $this->_Pdf->margin();

		foreach($margin as $border => $value) {
			if ($value !== null) {
				$command .= sprintf(' --margin-%s %dmm', $border, $value);
			}
		}

		$title = $this->_Pdf->title();
		if ($title !== null) {
			$command .= sprintf(' --title %s', escapeshellarg($title));
		}
		$command .= " - -";

		return $command;
	}
}