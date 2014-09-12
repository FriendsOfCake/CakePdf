<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use CakePdf\Pdf\Engine\AbstractPdfEngine;
use Cake\Core\Exception\Exception;

class WkHtmlToPdfEngine extends AbstractPdfEngine {

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
 * @param $Pdf CakePdf instance
 */
	public function __construct(CakePdf $Pdf) {
		parent::__construct($Pdf);
	}

/**
 * Generates Pdf from html
 *
 * @return string raw pdf data
 * @throws Exception
 */
	public function output() {
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
 * @throws Exception
 */
	protected function _getCommand() {
		$binary = $this->config('binary');

		if ($binary) {
			$this->_binary = $binary;
		}
		if (!is_executable($this->_binary)) {
			throw new Exception(sprintf('wkhtmltopdf binary is not found or not executable: %s', $this->_binary));
		}

		$options = array(
			'quiet' => true,
			'print-media-type' => true,
			'orientation' => $this->_Pdf->orientation(),
			'page-size' => $this->_Pdf->pageSize(),
			'encoding' => $this->_Pdf->encoding(),
			'title' => $this->_Pdf->title()
		);

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

		return $command;
	}
}
