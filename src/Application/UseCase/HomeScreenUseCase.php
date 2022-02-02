<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Persistence\Repository\FileGPGSearchRepository;
use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;
use Encrypt\Infrastructure\Ui\Html\Template\HomeScreenTemplate;

class HomeScreenUseCase
{
    /** @var FileGPGSearchRepository */
    private $fileGpgRepository;
    /** @var FilePubKeysRepository */
    private $filePubKeysRepository;
    /** @var HomeScreenTemplate */
    private $homeScreenTemplate;
    /** @var string */
    private $domain;

    public function __construct(
        FileGPGSearchRepository $fileGpgRepository,
        FilePubKeysRepository $filePubKeysRepository,
        HomeScreenTemplate $homeScreenTemplate,
        string $domain
    ) {
        $this->fileGpgRepository = $fileGpgRepository;
        $this->filePubKeysRepository = $filePubKeysRepository;
        $this->homeScreenTemplate = $homeScreenTemplate;
        $this->domain = $domain;
    }

    public function __invoke(bool $isAdmin, string $adminAccess): string
    {
        $filesEncrypt = $this->fileGpgRepository->getAll();

        return $this->homeScreenTemplate->html(
            $filesEncrypt,
            $this->filePubKeysRepository->all(),
            $adminAccess
        );
    }
}