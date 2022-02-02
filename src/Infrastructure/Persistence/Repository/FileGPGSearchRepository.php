<?php

namespace Encrypt\Infrastructure\Persistence\Repository;

class FileGPGSearchRepository
{
    private const GPG_EXTENSION = '.gpg';
    private const UPLOAD_PATH = '/tmp/upload';

    public function getAll(): array
    {
        $filesFinder = shell_exec('find ' . self::UPLOAD_PATH . '/ | grep ".gpg"');
        $filesList = explode(PHP_EOL, $filesFinder);

        return  array_filter(
            $filesList,
            function (string $file) {
                return is_file($file);
            }
        );
    }

    public function saveGpgFile(string $fileName, string $fileType, string $encryptFileContent): void
    {
        $uploadDir = self::UPLOAD_PATH . DS . str_replace(DS, '_', $fileType);
        $uploadFile = $uploadDir . DS . $fileName . self::GPG_EXTENSION;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        file_put_contents($uploadFile, $encryptFileContent);
    }
}