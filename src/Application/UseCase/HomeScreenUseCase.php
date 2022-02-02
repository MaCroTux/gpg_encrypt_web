<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Persistence\Repository\FileGPGSearchRepository;
use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;
use Encrypt\Infrastructure\Ui\Html\Template\HomeScreenTemplate;

class HomeScreenUseCase
{
    private const UPLOAD_PATH = '/tmp/upload';

    /** @var FileGPGSearchRepository */
    private $fileRepository;
    /** @var FilePubKeysRepository */
    private $filePubKeysRepository;
    /** @var string */
    private $domain;
    /** @var HomeScreenTemplate */
    private $homeScreenTemplate;
    /** @var string */
    private $adminAccess;

    public function __construct(string $domain, bool $isAdmin, string $adminAccess)
    {
        $this->fileRepository = new FileGPGSearchRepository(self::UPLOAD_PATH);
        $this->filePubKeysRepository = new FilePubKeysRepository();
        $this->homeScreenTemplate = new HomeScreenTemplate($domain, $isAdmin);
        $this->domain = $domain;
        $this->adminAccess = $adminAccess;
    }

    public function __invoke(): string
    {
        $filesEncrypt = $this->fileRepository->getAll();

        return $this->homeScreenTemplate->html(
            $filesEncrypt,
            $this->filePubKeysRepository->all(),
            $this->adminAccess
        );
    }
}