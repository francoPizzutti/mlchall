<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Dto;

use App\Service\Dto\SatellitesDTO;

class SatellitesDTOMock 
{
    public static function getStub(array $satellitesData): SatellitesDTO
    {
        $satellites = [];
        foreach($satellitesData as $satelliteData) {
            $satelliteDTO = SatelliteDTOMock::getStub(
                $satelliteData
            );

            $satellites[] = $satelliteDTO;
        }
        
        return new SatellitesDTO($satellites);
    }
}