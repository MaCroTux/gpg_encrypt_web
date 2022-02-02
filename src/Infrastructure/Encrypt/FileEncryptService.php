<?php

namespace Encrypt\Infrastructure\Encrypt;

use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;

class FileEncryptService
{
    /** @var FilePubKeysRepository */
    private $filePubKeysRepository;
    /** @var string */
    private $domain;

    public function __construct(
        FilePubKeysRepository $filePubKeysRepository,
        string $domain
    ) {
        $this->filePubKeysRepository = $filePubKeysRepository;
        $this->domain = $domain;
    }

    /**
     * @param string $fileName
     * @param string $keyId
     * @return string|null
     * @throws \Exception
     */
    public function __invoke(string $fileName, string $keyId): ?string
    {
        $pubKeyId = $this->filePubKeysRepository->getId($keyId);
        $rawFile = file_get_contents($fileName);


        if (null === $pubKeyId) {
            return null;
        }

        return $this->encrypt($pubKeyId['id'], $rawFile, $pubKeyId['content']);
    }

    private function encrypt(string $pubId, string $dataToEncrypt, string $pubkey = null): ?string
    {
        $res = gnupg_init();

        if (null !== $pubkey) {
            $rtv = gnupg_import($res, $pubkey);
            if (false === $rtv) {
                return null;
            }
        }

        $rtv = gnupg_addencryptkey($res, $pubId);
        if (false === $rtv) {
            return null;
        }

        return gnupg_encrypt($res, $dataToEncrypt);
    }
}