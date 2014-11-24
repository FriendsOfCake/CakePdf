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

		$command = $this->binary;
		foreach ($options as $key => $value) {
			if (empty($value)) {
				continue;
			} elseif ($value === true) {
				$command .= ' --' . $key;
			} else {
				if ($key == 'header-html' || $key == 'footer-html') {
					$value = $this->handleInlineHtmlBlock($key, $value);
				}
				$command .= sprintf(' --%s %s', $key, escapeshellarg($value));
			}
		}
		$command .= " - -";

		return $command;
	}

/**
 * Convert a HTML block, passed in as text, into a temporary HTML file,
 * which can be requested and rendered via wkhtmltopdf
 *
 *   input: <p>Some HTML here</p>
 *   output: app/tmp/cache/cakepdf-header-html-52bf266917d266accbb0b794fae83062.html
 *
 * Config:
 *   'webroot-temp-disable-wrapper' (boolean) if true, we will not wrap content block
 *                                            in html/JS recommended by wkhtmltopdf
 *
 * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
 * @param string $content either a HTML block or a URL to a HTML fragment document
 * @return string $url to a HTML fragment document
 * @throws CakeException if temp file can not be created
 */
	public function handleInlineHtmlBlock($key, $content) {
		if (substr($content, 0, 4) == 'http' || substr($content, -5) == '.html') {
			return $content;
		}

		$prefix = 'cakepdf-';
		$filename = $prefix . $key . '-' . md5($content) . '.html';
		if (defined('CACHE') && is_dir(CACHE) && is_writeable(CACHE)) {
			$filepath = CACHE . $filename;
		} else {
			$filepath = TMP . $filename;
		}

		App::uses('File', 'Utility');
		$File = new File($filepath, true, 0777);
		if (!$File->exists()) {
			throw new CakeException('Unable to make temp file for PDF rendering: ' . $key);
		}

		if (!($this->config('webroot-temp-disable-wrapper'))) {
			$content = sprintf('<!DOCTYPE html><html><head><script>' .
				'function subst() { var vars={}; var x=window.location.search.substring(1).split("&"); for (var i in x) {var z=x[i].split("=",2);vars[z[0]] = unescape(z[1]);} var x=["frompage","topage","page","webpage","section","subsection","subsubsection"]; for (var i in x) { var y = document.getElementsByClassName(x[i]); for (var j=0; j<y.length; ++j) y[j].textContent = vars[x[i]]; } }' .
				'</script></head><body style="border:0; margin: 0;padding: 0;line-height: 1;vertical-align: baseline;" onload="subst()">%s</body></html>',
				$content
			);
		}

		$File->write($content);
		return $filepath;
	}

}
