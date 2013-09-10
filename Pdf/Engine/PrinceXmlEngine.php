<?php
App::uses('AbstractPdfEngine', 'CakePdf.Pdf/Engine');
App::uses('Hash', 'Utility');
/**
 *  Configure::write ('CakePdf', array (
 *  	'engine'	=> 'CakePdf.PrinceXml',
 *  	'options'	=> array (
 *  		'binary'	=> '/usr/local/bin/prince',
 *  		'pdf'		=> array (
 *  			'subject'	=> 'subject',
 *  			'author'	=> 'author',
 *  			'keywords'	=> 'keywords',
 *  			'creator'	=> 'creator',
 *  			'key_bits'	=> 128
 *  		)
 *  	)
 *  ));
 */
class PrinceXmlEngine extends AbstractPdfEngine
{
	protected function getBinary ()
	{
		return Hash::get ($this->config(), 'options.binary') ?: '/usr/bin/prince';
	}
	
	protected function parseCommand ()
	{
		$arguments = array (
			'input'			=> 'auto',
			'input-list'	=> '-',
			'baseurl'		=> Router::fullBaseUrl(),
			'javascript'	=> '',
			'output'		=> '-'
		);
		
		$title = $this->_Pdf->title();
		if (! empty ($title)) {
			$arguments['pdf-title'] = $title;
		}
		
		$options = $this->config ('options');
		
		if ( $options && ($opts = Hash::get ($options, 'pdf')) ) {
			foreach (array (
				'subject'	=> 'pdf-subject',
				'author'	=> 'pdf-author',
				'keywords'	=> 'pdf-keywords',
				'creator'	=> 'pdf-creator'
			) as $k => $v) {
				if ( ($k = Hash::get ($opts, $k)) ) {
					$arguments[$v] = $k;
				}
			}
		}
		
		$userPw = $this->_Pdf->userPassword();
		$ownerPw = $this->_Pdf->ownerPassword();
		
		if (! empty ($userPw) || ! empty ($ownerPw)) {
			if ( ($k = Hash::get ($options, 'pdf.key_bits')) ) {
				$arguments['key-bits'] = $k;
			}
			
			foreach (array (
				'userPassword'		=> 'user-password',
				'ownerPassword'		=> 'owner-password'
			) as $k => $v) {
				if ( ($k = $this->config ($k)) ) {
					$arguments[$v] = $k;
				}
			}
		}
		
		$argsList = array();
		foreach ($arguments as $longName => $value) {
			if (is_string ($longName)) {
				$i = array_push ($argsList, '--' . $longName);
				
				if (! empty ($value)) {
					if (preg_match ('#\s+#', $value)) {
						$value = sprintf ('"%s"', $value);
					}
					
					$argsList[$i - 1] .= sprintf ('=%s', $value);
				}
			} else {
				$argsList[] = '-' . $value;
			}
		}
		
		return $this->getBinary() . ' ' . implode (' ', $argsList);
	}
	
	/**
	 *  Generates PDF from HTML
	 */
	public function output ()
	{
		$cmd = $this->parseCommand();
		$proc = proc_open ($cmd, array (
			0	=> array (
				'pipe', 'r'
			),
			1	=> array (
				'pipe', 'w'
			),
			2	=> array (
				'pipe', 'w'
			)
		), $pipes);
		
		$html = $this->_Pdf->html();
		fwrite ($pipes[0], $html);
		fclose ($pipes[0]);
		
		$result = array();
		for ($i = 1; $i < 3; $i++) {
			$result[$i] = stream_get_contents ($pipes[$i]);
			fclose ($pipes[$i]);
		}
		
		$retval = proc_close ($proc);
		
		if (! empty ($result[2])) {
			throw new CakeException ("PrinceXml: " . $result[2] . " (exit code: " . $retval . ")");
		}
		
		return $result[1];
	}
}