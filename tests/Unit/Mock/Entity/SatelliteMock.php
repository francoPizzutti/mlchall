<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Entity;

use App\Entity\Satellite;

class SatelliteMock 
{
    /**
     * @param mixed[] $satelliteData
     */
    public static function getStub(array $satelliteData): Satellite
    {
        return new Satellite(
            $satelliteData['name'],
            $satelliteData['distance'],
            $satelliteData['message']
        );
    }
}