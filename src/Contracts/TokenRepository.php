<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Contracts;

interface TokenRepository
{
    public function tokenExists(string $fileName): bool;

    public function getToken(string $fileName, bool $allowJsonEncrypt): array;

    public function storeToken(string $fileName, array $config, bool $allowJsonEncrypt): void;

    public function deleteToken(string $fileName, bool $allowJsonEncrypt): void;
}
