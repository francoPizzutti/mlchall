<?php

namespace App\Entity;

use App\Entity\Satellite;
use App\Entity\KnownSatellites;
use App\Service\Dto\SatellitesDTO;
use Symfony\Component\Cache\CacheItem;

class Satellites
{
    /**
     * @var Satellite[]
     */
    private array $satellites;

    /**
     * @param Satellite[] $satellites
     */
    public function __construct(
        array $satellites = []
    ) {
        $this->satellites = $satellites;
    }
    
    public function setSatellites(array $satellites): void
    {
        $this->satellites = $satellites;
    }

    /**
     * @return mixed[] $distances
     */
    public function getSatellitesDistances(): array
    {
        foreach($this->satellites as $satellite) {
            if(KnownSatellites::isUnknown($satellite->getName())) {
                continue;
            }

            $distances[$satellite->getName()] = $satellite->getDistance();
        }

        return $distances;
    }

    /**
     * @return mixed[] $messages
     */
    public function getSatellitesMessage(): array
    {
        foreach($this->satellites as $satellite) {
            if(KnownSatellites::isUnknown($satellite->getName())) {
                continue;
            }

            $messages[$satellite->getName()] = $satellite->getMessage();
        }

        return $messages;
    }

    public static function fromDto(SatellitesDTO $dto): self
    {
        $satelliteCollection = [];
        foreach($dto->getSatellites() as $satelliteDto) {
            $satelliteCollection[] = Satellite::fromDto($satelliteDto);
        }

        $entity = new self();
        $entity->setSatellites($satelliteCollection);

        return $entity;
    }

    /**
     * @param CacheItem[] $cacheItemList
     * @return self
     */
    public static function fromCacheItems(array $cacheItemList): self {
        $satellites = [];
        foreach($cacheItemList as $cachedSatellite) {
            $satellite = Satellite::fromCachedItem($cachedSatellite);
            $satellites[] = $satellite;
        }
        
        $entity = new self();
        $entity->setSatellites($satellites);

        return $entity;
    }

}
