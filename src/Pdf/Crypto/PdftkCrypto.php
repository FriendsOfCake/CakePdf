<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Crypto;

use Cake\Core\Exception\CakeException;

class PdftkCrypto extends AbstractPdfCrypto
{
    /**
     * Path to the pdftk executable binary
     *
     * @var string
     */
    protected string $_binary = '/usr/local/bin/pdftk';

    /**
     * Mapping of the CakePdf permissions to the Pdftk arguments
     *
     * @var array
     */
    protected array $_permissionsMap = [
        'print' => 'Printing',
        'degraded_print' => 'DegradedPrinting',
        'modify' => 'ModifyContents',
        'assembly' => 'Assembly',
        'copy_contents' => 'CopyContents',
        'screen_readers' => 'ScreenReaders',
        'annotate' => 'ModifyAnnotations',
        'fill_in' => 'FillIn',
    ];

    /**
     * Encrypt a pdf file
     *
     * @param string $data raw pdf data
     * @throws \Cake\Core\Exception\CakeException
     * @return string raw pdf data
     */
    public function encrypt(string $data): string
    {
        /** @var string $binary */
        $binary = $this->config('binary');

        if ($binary) {
            $this->_binary = $binary;
        }

        if (!is_executable($this->_binary)) {
            throw new CakeException(sprintf('pdftk binary is not found or not executable: %s', $this->_binary));
        }

        $arguments = [];

        $ownerPassword = $this->_Pdf->ownerPassword();
        if ($ownerPassword !== null) {
            $arguments['owner_pw'] = escapeshellarg($ownerPassword);
        }

        $userPassword = $this->_Pdf->userPassword();
        if ($userPassword !== null) {
            $arguments['user_pw'] = escapeshellarg($userPassword);
        }

        $allowed = $this->_buildPermissionsArgument();
        if ($allowed) {
            $arguments['allow'] = $allowed;
        }

        if (!$ownerPassword && !$userPassword) {
            throw new CakeException('Crypto: Required to configure atleast an ownerPassword or userPassword');
        }

        if ($ownerPassword == $userPassword) {
            throw new CakeException('Crypto: ownerPassword and userPassword cannot be the same');
        }

        $command = sprintf('%s - output - %s', $this->_binary, $this->_buildArguments($arguments));

        $descriptorspec = [
            0 => ['pipe', 'r'], // feed stdin of process from this file descriptor
            1 => ['pipe', 'w'], // Note you can also grab stdout from a pipe, no need for temp file
            2 => ['pipe', 'w'], // stderr
        ];

        $prochandle = proc_open($command, $descriptorspec, $pipes);
        if ($prochandle === false) {
            throw new CakeException('Unable to execute pdftk, proc_open() failed');
        }

        fwrite($pipes[0], $data);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitcode = proc_close($prochandle);

        if ($exitcode !== 0) {
            throw new CakeException(sprintf("Crypto: (exit code %d)\n%s", $exitcode, (string)$stderr));
        }

        return (string)$stdout;
    }

    /**
     * Checks if a CakePdf permission is implemented
     *
     * @param string $permission permission name
     * @return bool
     */
    public function permissionImplemented(string $permission): bool
    {
        return array_key_exists($permission, $this->_permissionsMap);
    }

    /**
     * Builds a shell safe argument list
     *
     * @param array $arguments arguments to pass to pdftk
     * @return string list of arguments
     */
    protected function _buildArguments(array $arguments): string
    {
        $output = [];

        foreach ($arguments as $argument => $value) {
            $output[] = $argument . ' ' . $value;
        }

        return implode(' ', $output);
    }

    /**
     * Generate the permissions argument
     *
     * @return string|false list of arguments or false if no permission set
     */
    protected function _buildPermissionsArgument(): string|false
    {
        $permissions = $this->_Pdf->permissions();

        if ($permissions === false) {
            return false;
        }

        $allowed = [];

        if ($permissions === true) {
            $allowed[] = 'AllFeatures';
        }

        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                $allowed[] = $this->_permissionsMap[$permission];
            }
        }

        return implode(' ', $allowed);
    }
}
