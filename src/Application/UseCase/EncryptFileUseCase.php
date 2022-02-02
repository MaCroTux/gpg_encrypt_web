<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Encrypt\FileEncryptService;
use Encrypt\Infrastructure\Persistence\Repository\FileGpgRepository;

class EncryptFileUseCase
{
    /** @var FileEncryptService */
    private $fileEncryptService;
    /** @var FileGpgRepository */
    private $fileGPGSearchRepository;

    public function __construct(
        FileEncryptService $fileEncryptService,
        FileGpgRepository $fileGPGSearchRepository
    ) {
        $this->fileEncryptService = $fileEncryptService;
        $this->fileGPGSearchRepository = $fileGPGSearchRepository;
    }

    /**
     * @throws \Exception
     */
    public function __invoke(string $idPubKey, string $fileTmpName, string $fileName, string $fileType): void
    {
        $encryptFileContent = $this->fileEncryptService->__invoke($fileTmpName, $idPubKey);

        if (null === $encryptFileContent) {
            throw new \Exception('Decrypt: ERROR');
        }

        $this->fileGPGSearchRepository->saveGpgFile($fileName, $fileType, $encryptFileContent);
    }
}