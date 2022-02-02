<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Encrypt\FileEncryptService;
use Encrypt\Infrastructure\Persistence\Repository\FileGPGSearchRepository;

class EncryptFileUseCase
{
    /** @var FileEncryptService */
    private $fileEncryptService;
    /** @var FileGPGSearchRepository */
    private $fileGPGSearchRepository;

    public function __construct(
        FileEncryptService $fileEncryptService,
        FileGPGSearchRepository $fileGPGSearchRepository
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