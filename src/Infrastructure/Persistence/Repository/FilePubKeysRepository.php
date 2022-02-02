<?php

namespace Encrypt\Infrastructure\Persistence\Repository;

class FilePubKeysRepository
{
    public function all(): array
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
            ];
        };
    }
}