<?php

namespace Encrypt\Infrastructure\Persistence\Repository;

class FilePubKeysRepository
{
    public function all(): array
    {
        return $this->getReadableKeys();
    }

    private function getReadableKeys(): array
    {
        $pubKeys = glob('../keys/*.pub');

        $pubKeysReadable = array_filter(
            $pubKeys,
            static function (string $fileKey) {
                return is_readable($fileKey);
            }
        );

        return array_map($this->formatter(), $pubKeysReadable);
    }

    /**
     * @param string $keyId
     * @return array|null
     * @throws \Exception
     */
    public function getId(string $keyId): ?array
    {
        $pubKeysReadable = $this->getReadableKeys();

        $pubKeysFilter = array_filter(
            $pubKeysReadable,
            static function (array $keys) use ($keyId) {
                return strpos($keys['file'], $keyId) !== false;
            }
        );

        if (empty($pubKeysFilter)) {
            throw new \Exception('Pub key invalid');
        }

        return current($pubKeysFilter);
    }

    private function formatter(): callable
    {
        return static function (string $fileKey) {
            $file = str_replace(['../keys/', '.pub'], ['', ''], $fileKey);
            [$name, $id] = explode('-', $file);

            return [
                'id' => $id,
                'name' => $name,
                'file' => $name . '-' . $id,
                'fullPath' => $fileKey,
                'content' => file_get_contents($fileKey),
            ];
        };
    }
}