<?php 

namespace App\Service;

use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CacheService
{
    private FilesystemAdapter $cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }
    
    public function resetCache(): void {
        $this->cache->clear();
    }

    public function getItem(string $key): CacheItem
    {
        return $this->cache->getItem($key);
    }

    public function cacheSatelliteData(string $satelliteName, string $satelliteDataDTO): void
    {
        $satellites = $this->cache->getItem($satelliteName);
        $satellites->set($satelliteDataDTO);
        $this->cache->save($satellites);
    }

    /**
     * @param CacheItem[] $cacheItems
     */
    public function validateAllItemsHit(array $cacheItems): bool
    {
        foreach ($cacheItems as $item) {
            if (!$item->isHit()) {
                return false;
            }
        }

        return true;
    }
}
