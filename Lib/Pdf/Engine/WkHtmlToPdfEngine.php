<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');

class WkHtmlToPdfEngine extends AbstractPdfEngine {

	protected $output = null;

	/**
	 * @brief the default options for WkHtmlToPdf View class
	 * 
	 * @access protected
	 * @var array
	 */
	protected $options = array(
		'orientation' => 'Portrait',
		'pageSize' => 'A4',
		'binary' => '/usr/bin/wkhtmltopdf',
	);

	public function output($html) {

		return $this->_renderPdf($html);
	}

	/**
	 * @brief render a pdf document from some html
	 * 
	 * @access protected
	 * 
	 * @return the data from the rendering
	 */
	protected function _renderPdf($html) {
		$content = $this->__exec($this->__getCommand(), $html);

		if(strpos(mb_strtolower($content['stderr']), 'error')) {
			throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");
		}

		if(mb_strlen($content['stdout'], 'utf-8') === 0) {
			throw new Exception("WKHTMLTOPDF didn't return any data");
		}

		if((int)$content['return'] > 1) {
			throw new Exception("Shell error, return code: " . (int)$content['return']);
		}

		return $content['stdout'];
	}

	/**
	 * @brief execute the WkHtmlToPdf commands for rendering pdfs
	 * 
	 * @access private
	 * 
	 * @param string $cmd the command to execute
	 * 
	 * @return string the result of running the command to generate the pdf 
	 */
	private function __exec($cmd, $input) {
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
	 * @brief get the command to render a pdf 
	 * 
	 * @access private
	 * 
	 * @return string the command for generating the pdf
	 */
	private function __getCommand() {
		$command = $this->options['binary'];

		$command .= " --orientation " . $this->options['orientation'];
		$command .= " --page-size " . $this->options['pageSize'];

		$command .= " - -";

		return $command;
	}
}