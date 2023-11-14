<?php

declare(strict_types=1);

namespace App\Entity; 

class KnownSatellites 
{
    public const KNOWN_SATELLITES = [
        'kenobi' => self::KENOBI,
        'skywalker' => self::SKYWALKER,
        'sato' => self::SATO
    ];

    public const KENOBI = [
        -500, -200
    ];

    public const SKYWALKER = [
        100,
        -100
    ];

    public const SATO = [
        500, 
        100
    ];

    public static function isUnknown(string $satelliteName): bool
    {
        return !in_array($satelliteName, array_keys(self::KNOWN_SATELLITES));
    }
}