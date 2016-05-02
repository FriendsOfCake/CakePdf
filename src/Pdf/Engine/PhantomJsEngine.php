<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\Core\Exception\Exception;

class PhantomJsEngine extends AbstractPdfEngine
{

    /**
     * Path to the phantomjs executable binary
     *
     * @access protected
     * @var string
     */
    protected $_binary = '/usr/bin/phantomjs';
    
    /**
     * Script used by PhantomJs for rendering the PDF
     *
     * @access protected
     * @var string
     */
     protected $_phantomJsScript = <<<'EOT'
     "use strict";
"use strict";
var page = require('webpage').create(),
    system = require('system'),
    fs = require('fs'),
    size;

page.paperSize = { format: 'A4', orientation: 'portrait', margin: '1cm' };
page.content = fs.read('/proc/self/fd/3');
page.render('/dev/stdout', { format: 'pdf' });
phantom.exit();
EOT;

    /**
     * Constructor
     *
     * @param CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        parent::__construct($Pdf);
    }

    /**
     * Generates Pdf from html
     *
     * @throws \Cake\Core\Exception\Exception
     * @return string raw pdf data
     */
    public function output()
    {
        $content = $this->_exec($this->_getCommand(), $this->_Pdf->html());

        if (strpos(mb_strtolower($content['stderr']), 'error')) {
            throw new Exception("System error <pre>" . $content['stderr'] . "</pre>");
        }

        if (mb_strlen($content['stdout'], $this->_Pdf->encoding()) === 0) {
            throw new Exception("PhantomJs didn't return any data");
        }

        if ((int)$content['return'] !== 0 && !empty($content['stderr'])) {
            throw new Exception("Shell error, return code: " . (int)$content['return']);
        }

        return $content['stdout'];
    }

    /**
     * Execute the PhantomJs commands for rendering pdfs
     *
     * @param string $cmd the command to execute
     * @param string $input Html to pass to wkhtmltopdf
     * @return string the result of running the command to generate the pdf
     */
    protected function _exec($cmd, $input)
    {
        $result = ['stdout' => '', 'stderr' => '', 'return' => ''];

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'], 3 => ['pipe', 'r']], $pipes);
        
        fwrite($pipes[0], $this->_phantomJsScript);
        fclose($pipes[0]);
        
        fwrite($pipes[3], $input);
        fclose($pipes[3]);

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
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _getCommand()
    {
        $binary = $this->config('binary');

        if ($binary) {
            $this->_binary = $binary;
        }
        if (!is_executable($this->_binary)) {
            throw new Exception(sprintf('phantomjs binary is not found or not executable: %s', $this->_binary));
        }

        $command = $this->_binary . ' /dev/stdin';
        //TODO: Add support for options like paper size etc.

        return $command;
    }
}
