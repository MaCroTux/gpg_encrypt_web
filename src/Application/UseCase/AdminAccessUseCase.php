<?php

namespace Encrypt\Application\UseCase;

class AdminAccessUseCase
{
    /** @var string */
    private $domain;
    /** @var string */
    private $adminPubKey;

    public function __construct(string $domain, string $adminPubKey)
    {
        $this->domain = $domain;
        $this->adminPubKey = $adminPubKey;
    }

    public function __invoke(string $adminAccess, ?string $passwordInput = null): bool
    {
        if (
            !empty($passwordInput) &&
            verify($passwordInput, $this->adminPubKey, $adminAccess)
        ) {
            return true;
        }

        return false;
    }
}