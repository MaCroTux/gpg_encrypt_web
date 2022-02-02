<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Persistence\Repository\FileGpgRepository;

class DeleteGpgFileUseCase
{
    /** @var FileGpgRepository */
    private $fileGpgRepository;
    /** @var bool */
    private $isAdmin;

    public function __construct(FileGpgRepository $fileGpgRepository, bool $isAdmin)
    {
        $this->fileGpgRepository = $fileGpgRepository;
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param string $fileName
     * @throws \Exception
     */
    public function __invoke(string $fileName)
    {
        if (!$this->isAdmin) {
            throw new \Exception('Not admin user present');
        }

        $this->fileGpgRepository->deleteGpgFile($fileName);
    }
}