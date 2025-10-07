<?php

namespace App\Service;

class GravatarService
{
    public function getGravatarUrl(string $email, int $size = 200): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
    }
}
