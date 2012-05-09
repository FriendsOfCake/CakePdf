<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');
App::uses('String', 'Utility');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class WkHtmlToPdfEngine extends AbstractPdfEngine {

	protected $output = null;

	protected $sourceFile = null;

	/**
	 * @brief the default options for WkHtmlToPdf View class
	 * 
	 * @access protected
	 * @var array
	 */
	protected $options = array(
		'footer' => array(),
		'header' => array(),
		'orientation' => 'Portrait',
		'pageSize' => 'A4',
		'mode' => 'download',
		'filename' => 'output.pdf',
		'binary' => '/usr/bin/wkhtmltopdf',
		'copies' => 1,
		'toc' => false,
		'grayscale' => false,
		'username' => false,
		'password' => false,
		'title' => ''
	);

	public function __construct() {
		
	}

	public function output($html) {
		$this->_prepare($html);

		return $this->_renderPdf();
	}

	/**
	 * @brief Prepares the temporary file paths and source file with the html data
	 * 
	 * @access protected
	 * 
	 * @return void
	 */
	protected function _prepare($html) {
		$path = TMP . 'wk_html_to_pdf' . DS;

		//Make sure the folder exists
		new Folder($path, true);

		$this->sourceFile = new File($path . String::uuid() . '.html', true);
		$this->sourceFile->write($html);
		$this->sourceFile->close();

		return;
	}

	/**
	 * @brief render a pdf document from some html
	 * 
	 * @access protected
	 * 
	 * @return the data from the rendering
	 */
	protected function _renderPdf() {
		$content = $this->__exec(str_replace('%input%', $this->sourceFile->pwd(), $this->__getCommand()));

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
	 * @breif execute the WkHtmlToPdf commands for rendering pdfs
	 * 
	 * @access private
	 * 
	 * @param string $cmd the command to execute
	 * @param string $input
	 * 
	 * @return string the result of running the command to generate the pdf 
	 */
	private function __exec($cmd, $input = '') {
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
	 * @brief build up parts of the command that will later be executed
	 * 
	 * @access private
	 * 
	 * @param string $commandType the part of the command to build up
	 * 
	 * @return string a part of the command for rendering pdfs 
	 */
	private function __subCommand($commandType) {
		$data = $this->options[$commandType];
		$command = '';

		if(count($data) > 0) {
			$availableCommands = array(
				'left', 'right', 'center', 'font-name', 'html', 'line', 'spacing', 'font-size'
			);

			foreach($data as $key => $value) {
				if(in_array($key, $availableCommands)) {
					$command .= " --$commandType-$key \"$value\"";
				}
			}
		}

		return $command;
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

		$command .= ($this->options['copies'] > 1) ? " --copies " . $this->options['copies'] : "";
		$command .= " --orientation " . $this->options['orientation'];
		$command .= " --page-size " . $this->options['pageSize'];
		$command .= ($this->options['toc'] === true) ? " --toc" : "";
		$command .= ($this->options['grayscale'] === true) ? " --grayscale" : "";
		$command .= ($this->options['password'] !== false) ? " --password " . $this->options['password'] : "";
		$command .= ($this->options['username'] !== false) ? " --username " . $this->options['username'] : "";
		$command .= $this->__subCommand('footer') . $this->__subCommand('header');

		$command .= ' --title "' . $this->options['title'] . '"';
		$command .= ' "%input%"';
		$command .= " -";
		
		return $command;
	}
}