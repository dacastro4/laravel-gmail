<?php

declare(strict_types=1);

namespace Dacastro4\LaravelGmail\Traits;

trait HasDecodableBody
{
    public function getDecodedBody(string $content): string
    {
        $content = str_replace('_', '/', str_replace('-', '+', $content));

        return base64_decode($content);
    }
}
