<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Dto;

use App\Service\Dto\SatelliteDataDTO;

class SatelliteDataDTOMock 
{
    /**
     * @param mixed[] $satelliteData
     */
    public static function getStub(array $satelliteData): SatelliteDataDTO
    {
        return new SatelliteDataDTO(
            $satelliteData['distance'],
            $satelliteData['message']
        );
    }
}