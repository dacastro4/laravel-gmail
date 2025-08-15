<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Repositories;

use Dacastro4\LaravelGmail\Contracts\TokenRepository;
use Illuminate\Support\Facades\Storage;

class StorageTokenRepository implements TokenRepository
{
    public function tokenExists(string $fileName): bool
    {
        $file = "gmail/tokens/$fileName.json";

        return Storage::disk('local')->exists($file);
    }

    public function getToken(string $fileName, bool $allowJsonEncrypt): array
    {
        $file = "gmail/tokens/$fileName.json";
        $disk = Storage::disk('local');

        if (! $disk->exists($file)) {
            return [];
        }

        $contents = $disk->get($file);

        return json_decode($allowJsonEncrypt ? decrypt($contents) : $contents, true) ?? [];
    }

    public function storeToken(string $fileName, array $config, bool $allowJsonEncrypt): void
    {
        $disk = Storage::disk('local');
        $file = "gmail/tokens/$fileName.json";

        if ($disk->exists($file)) {
            $savedConfigToken = $this->getToken($fileName, $allowJsonEncrypt);
            if (empty($config['email']) && isset($savedConfigToken['email'])) {
                $config['email'] = $savedConfigToken['email'];
            }
            $disk->delete($file);
        }

        if ($allowJsonEncrypt) {
            $disk->put($file, encrypt(json_encode($config)));
        } else {
            $disk->put($file, json_encode($config));
        }
    }

    public function deleteToken(string $fileName, bool $allowJsonEncrypt): void
    {
        $disk = Storage::disk('local');
        $file = "gmail/tokens/$fileName.json";

        if ($disk->exists($file)) {
            $disk->delete($file);
        }

        if ($allowJsonEncrypt) {
            $disk->put($file, encrypt(json_encode([])));
        } else {
            $disk->put($file, json_encode([]));
        }
    }
}
