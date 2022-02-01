<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\Persistence\Repository\FileGPGSearchRepository;
use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;
use Encrypt\Infrastructure\Ui\Html\Template\HomeScreenTemplate;

class HomeScreenUseCase
{
    private const UPLOAD_PATH = '/tmp/upload';
    private const ADMIN_TEXT_TO_SIGN = 'Get admin access';

    /** @var FileGPGSearchRepository */
    private $fileRepository;
    /** @var FilePubKeysRepository */
    private $filePubKeysRepository;
    /** @var string */
    private $domain;
    /** @var HomeScreenTemplate */
    private $homeScreenTemplate;

    public function __construct(string $domain, bool $isAdmin)
    {
        $this->fileRepository = new FileGPGSearchRepository(self::UPLOAD_PATH);
        $this->filePubKeysRepository = new FilePubKeysRepository();
        $this->homeScreenTemplate = new HomeScreenTemplate($domain, $isAdmin);
        $this->domain = $domain;
    }

    public function __invoke(): string
    {
        $filesEncrypt = $this->fileRepository->getAll();

        return $this->homeScreenTemplate->html(
            $filesEncrypt,
            $this->filePubKeysRepository->all(),
            self::ADMIN_TEXT_TO_SIGN . ' ' . substr(hash('sha256', time()), 0 ,6)
        );
    }
}