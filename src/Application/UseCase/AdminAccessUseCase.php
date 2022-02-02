<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\InSession\Repository\SessionAccessAdminRepository;
use Encrypt\Infrastructure\Verify\FileVerifySignService;

class AdminAccessUseCase
{
    /** @var string */
    private $domain;
    /** @var string */
    private $adminPubKey;
    /** @var SessionAccessAdminRepository */
    private $accessAdminRepository;
    /** @var FileVerifySignService */
    private $fileVerifySignService;

    public function __construct(
        FileVerifySignService $fileVerifySignService,
        SessionAccessAdminRepository $accessAdminRepository,
        string $domain,
        string $adminPubKey
    ) {
        $this->accessAdminRepository = $accessAdminRepository;
        $this->domain = $domain;
        $this->adminPubKey = $adminPubKey;
        $this->fileVerifySignService = $fileVerifySignService;
    }

    public function __invoke(?string $passwordInput = null): bool
    {
        if (
            !empty($passwordInput) &&
            $this->fileVerifySignService->__invoke(
                $passwordInput,
                $this->adminPubKey,
                $this->accessAdminRepository->getAdminAccessPass()
            )
        ) {
            return true;
        }

        return false;
    }
}