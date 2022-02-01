<?php

namespace Encrypt\Infrastructure\Persistence\Repository;

class FilePubKeysRepository
{
    public function all(): array
    {
        return $pubKeys = glob('../keys/*.pub');
    }
}