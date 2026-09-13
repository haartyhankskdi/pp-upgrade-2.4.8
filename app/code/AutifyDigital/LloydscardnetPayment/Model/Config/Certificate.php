<?php
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Config;

class Certificate extends \Magento\Config\Model\Config\Backend\File
{
    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['pem'];
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {
        parent::afterSave();
        $this->denyDirectWebAccess();

        return $this;
    }

    /**
     * Drop deny rules into the certificate upload directory so the private key / cert PEM files
     * cannot be downloaded directly over HTTP (they live under pub/media, inside the web root).
     *
     * Covers Apache and IIS. Nginx ignores these, so a server-level deny rule is still required.
     *
     * @return void
     */
    private function denyDirectWebAccess(): void
    {
        try {
            $dir = rtrim($this->_mediaDirectory->getRelativePath($this->_getUploadDir()), '/');

            if (!$this->_mediaDirectory->isDirectory($dir)) {
                return;
            }

            $denyRules = [
                $dir . '/.htaccess' => "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
                    . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n",
                $dir . '/web.config' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                    . '<configuration><system.webServer><security><authorization>' . "\n"
                    . '    <deny users="*" />' . "\n"
                    . '</authorization></security></system.webServer></configuration>' . "\n",
            ];

            foreach ($denyRules as $path => $contents) {
                if (!$this->_mediaDirectory->isExist($path)) {
                    $this->_mediaDirectory->writeFile($path, $contents);
                }
            }
        } catch (\Throwable $e) {
            $this->_logger->warning(
                'Lloyds Cardnet: unable to write web access deny rules to the certificate upload directory: '
                . $e->getMessage()
            );
        }
    }
}
