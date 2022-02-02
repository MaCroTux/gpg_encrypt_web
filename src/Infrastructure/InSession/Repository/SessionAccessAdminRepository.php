<?php

namespace Encrypt\Infrastructure\InSession\Repository;

class SessionAccessAdminRepository
{
    /** @var array */
    private $nativeSession;

    public function __construct(array $nativeSession)
    {
        $this->nativeSession = $nativeSession;
    }

    public function getAdminAccessPass(): ?string
    {
        return $_SESSION['ADMIN_ACCESS_PASS'] ?? null;
    }
}