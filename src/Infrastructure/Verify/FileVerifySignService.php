<?php

namespace Encrypt\Infrastructure\Verify;

class FileVerifySignService
{
    public function __invoke(string $signature, string $pubKey, string $textToVerify): bool
    {
        $res = gnupg_init();

        gnupg_seterrormode($res, GNUPG_ERROR_WARNING);

        $public = gnupg_import($res, $pubKey);
        $publicFingerprint = $public['fingerprint'] ?? null;

        $publicsInfo = gnupg_keyinfo($res, $publicFingerprint);
        $publicInfo = array_shift($publicsInfo);

        $publicSigns = array_filter($publicInfo['subkeys'] ?? [], static function (array $keys) {
            return $keys['can_sign'] === true;
        });

        $publicSign = array_shift($publicSigns);
        $publicFingerprint = $publicSign['fingerprint'];

        $response = gnupg_verify($res, $signature, false);

        $verify = array_shift($response);
        $signatureFingerprint = $verify['fingerprint'];

        return $publicFingerprint === $signatureFingerprint
            && strpos($signature, $textToVerify) !== false;
    }
}