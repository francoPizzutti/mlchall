<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Entity;

use App\Entity\Satellite;
use App\Entity\Satellites;

class SatellitesMock 
{
    /**
     * @param Satellite[] $satellites
     */
    public static function getStub(array $satellites): Satellites
    {
        return new Satellites($satellites);
    }
}