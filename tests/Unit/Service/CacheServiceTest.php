<?php 

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use Prophecy\Prophet;
use Prophecy\Argument;
use App\Service\CacheService;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CacheServiceTest extends TestCase
{
    private ObjectProphecy $fakeCache;
    private Prophet $prophet;
    private CacheService $cacheService;

    private FilesystemAdapter $realCache;

    protected function setUp(): void
    {
        $this->prophet = new Prophet();

        $this->fakeCache = $this->prophet->prophesize(FilesystemAdapter::class);
        $this->cacheService = new CacheService(
            $this->fakeCache->reveal()
        );
        
        $this->realCache = new FilesystemAdapter();
    }

    public function tearDown(): void
    {
        $this->realCache->clear();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testItResetsCache(): void
    {
        $this->fakeCache->clear()->shouldBeCalled();
        $this->cacheService->resetCache();
        $this->prophet->checkPredictions();
    }

    public function testItGetsItem(): void
    {
        $key = 'test';
        $cacheItem = $this->realCache->getItem($key);
        $this->realCache->save($cacheItem);

        
        $this->fakeCache->getItem($key)->shouldBeCalled()->willReturn($cacheItem);

        $result = $this->cacheService->getItem($key);

        $this->assertInstanceOf(CacheItem::class, $result);
        $this->assertEquals($key, $result->getKey());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testItCachesSatelliteData(): void
    {
        $key = 'test';
        $testValue = '{abc: test}';
        $cacheItem = $this->realCache->getItem($key);
        $cacheItem->set($testValue);
        $this->realCache->save($cacheItem);

        $this->fakeCache->getItem($key)->shouldBeCalled()->willReturn($cacheItem);
        $this->fakeCache->save(Argument::exact($cacheItem))->shouldBeCalled();
        
        $this->cacheService->cacheSatelliteData($key, $testValue);
        $this->prophet->checkPredictions();
    }
}