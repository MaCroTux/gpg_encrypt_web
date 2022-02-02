<?php

namespace Encrypt\Application\UseCase;

use Encrypt\Infrastructure\InSession\Repository\SessionAccessAdminRepository;

class AdminAccessUseCase
{
    /** @var string */
    private $domain;
    /** @var string */
    private $adminPubKey;
    /**
     * @var SessionAccessAdminRepository
     */
    private $accessAdminRepository;

    public function __construct(
        SessionAccessAdminRepository $accessAdminRepository,
        string $domain,
        string $adminPubKey
    ) {
        $this->accessAdminRepository = $accessAdminRepository;
        $this->domain = $domain;
        $this->adminPubKey = $adminPubKey;
    }

    public function __invoke(?string $passwordInput = null): bool
    {
        if (
            !empty($passwordInput) &&
            verify($passwordInput, $this->adminPubKey, $this->accessAdminRepository->getAdminAccessPass())
        ) {
            return true;
        }

        return false;
    }
}