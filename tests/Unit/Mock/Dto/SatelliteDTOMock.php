<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Dto;

use App\Service\Dto\SatelliteDTO;

class SatelliteDTOMock 
{
    /**
     * @param mixed[] $satelliteData
     */
    public static function getStub(array $satelliteData): SatelliteDTO
    {
        return new SatelliteDTO(
            $satelliteData['name'],
            $satelliteData['distance'],
            $satelliteData['message']
        );
    }
}