<?php
App::uses('AbstractPdfCrypto', 'CakePdf.Pdf/Crypto');

class PdftkCrypto extends AbstractPdfCrypto {

/**
 * Path to the pdftk executable binary
 *
 * @access protected
 * @var string
 */
	protected $binary = '/usr/local/bin/pdftk';

/**
 * Encrypt a pdf file
 *
 * @param string $data raw pdf data
 * @return string raw pdf data
 */
	public function encrypt($data) {
		if (!is_executable($this->binary)) {
			throw new CakeException(sprintf('pdftk binary is not found or not executable: %s', $this->binary));
		}

		$arguments = array();

		$ownerPassword = $this->_Pdf->ownerPassword();
		if ($ownerPassword !== null) {
			$arguments['owner_pw'] = $ownerPassword;
		}

		$userPassword = $this->_Pdf->userPassword();
		if ($userPassword !== null) {
			$arguments['user_pw'] = $userPassword;
		}

		if (!$ownerPassword && !$userPassword) {
			throw new CakeException('Crypto: Required to configure atleast an ownerPassword or userPassword');
		}

		if ($ownerPassword == $userPassword) {
			throw new CakeException('Crypto: ownerPassword and userPassword cannot be the same');
		}

		$command = sprintf('%s - output - %s', $this->binary, $this->__buildArguments($arguments)); 

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
			throw new CakeException(sprintf('Crypto: Unknown error (exit code %d)', $exitcode));
		}

		return $stdout;
	}

/**
 * Builds a shell safe argument list
 *
 * @param array $arguments
 * @return string list of arguments
 */
	private function __buildArguments($arguments) {
		$output = array();

		foreach ($arguments as $argument => $value) {
			$output[] = $argument . ' ' . escapeshellarg($value);
		}

		return implode(' ', $output);
	}

}