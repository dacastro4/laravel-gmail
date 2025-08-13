<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

trait HasHeaders
{
    /**
     * Gets a single header from an existing email by name.
     */
    public function getHeader(string $headerName, ?string $regex = null): ?string
    {
        $headers = $this->getHeaders();

        $value = null;

        foreach ($headers as $header) {
            if ($header->key === $headerName) {
                $value = $header->value;
                if (! is_null($regex)) {
                    preg_match_all($regex, $header->value, $value);
                }
                break;
            }
        }

        if (is_array($value)) {
            return $value[1] ?? null;
        }

        return $value;
    }

    abstract public function getHeaders(): \Illuminate\Support\Collection;
}
