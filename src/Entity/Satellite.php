<?php

namespace App\Entity;

use App\Service\Dto\SatelliteDTO;
use Symfony\Component\Cache\CacheItem;

class Satellite
{
    /**
     * @var string[]
     */
    private array $message;
    private string $name;
    private float $distance;

    public function __construct(
        string $name,
        float $distance,
        array $message
    ) {
        $this->name = $name;
        $this->distance = $distance;
        $this->message = $message;
    }

    public function getName(): string 
    {
        return $this->name;
    }

    public function getMessage(): array 
    {
        return $this->message;    
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public static function fromDto(SatelliteDTO $dto): self
    {
        return new self(
            $dto->getName(),
            $dto->getDistance(),
            $dto->getMessage(),
        );
    }

    public static function fromCachedItem(CacheItem $cacheItem): self 
    {
        $name = $cacheItem->getKey();
        $data = json_decode($cacheItem->get(), true);

        return new self(
            $name,
            $data['distance'],
            $data['message']
        );
    }

}
