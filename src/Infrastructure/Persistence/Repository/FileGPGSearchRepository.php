<?php

namespace Encrypt\Infrastructure\Persistence\Repository;

class FileGPGSearchRepository
{
    /** @var string */
    private $pathToFind;

    public function __construct(string $pathToFind)
    {
        $this->pathToFind = $pathToFind;
    }

    public function getAll(): array
    {
        $filesFinder = shell_exec('find ' . $this->pathToFind . '/ | grep ".gpg"');
        $filesList = explode(PHP_EOL, $filesFinder);

        return  array_filter(
            $filesList,
            function (string $file) {
                return is_file($file);
            }
        );
    }
}