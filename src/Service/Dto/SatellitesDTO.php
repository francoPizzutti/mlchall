<?php

namespace App\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SatellitesDTO
{
    /**
     * @Assert\Valid
     * @Assert\NotNull
     * @Assert\Count(
     *     min = 3,
     *     minMessage = "At least one satellite must be provided"
     * )
     *
     * @var SatelliteDTO[]
     */
    private array $satellites;

    public function __construct(
        array $satellites
    ) {
        $this->satellites = $satellites;
    }

    public function getSatellites(): array 
    {
        return $this->satellites;
    }

    /**
     * @param SatelliteDTO[] $data
     * @return void
     */
    public function setSatellites(array $satellites): void 
    {
        $this->satellites = $satellites;
    }
}
