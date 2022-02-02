<?php

namespace Encrypt\Domain\Admin;

class GenerateAdminAccessService
{
    private const ADMIN_TEXT_TO_SIGN = 'Get admin access';

    public function __invoke()
    {
        return self::ADMIN_TEXT_TO_SIGN . ' ' . substr(hash('sha256', time()), 0 ,6);
    }
}