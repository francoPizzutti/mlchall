<?php 

declare(strict_types=1);

namespace App\Tests\Controller;

use Prophecy\Prophet;
use Prophecy\Argument;
use App\Entity\Satellites;
use Psr\Log\LoggerInterface;
use App\Service\CacheService;
use App\Service\SpaceshipService;
use App\Service\ValidatorService;
use Prophecy\Prophecy\ObjectProphecy;
use App\Exception\ValidationException;
use App\Controller\SpaceshipController;
use App\Tests\Unit\Mock\Entity\SpaceshipMock;
use App\Tests\Unit\Mock\Entity\PositionMock;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\Unit\Mock\Dto\SatellitesDTOMock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class SpaceshipControllerTest extends WebTestCase
{
    private Prophet $prophet;

    private SpaceshipController $spaceshipController;
    private ObjectProphecy $spaceshipService;
    private ObjectProphecy $cacheService;
    private ObjectProphecy $validatorService;
    private ObjectProphecy $logger;
    private array $wrongPayloads = [];
    private FilesystemAdapter $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prophet = new Prophet();
        $this->spaceshipService = $this->prophet->prophesize(SpaceshipService::class);
        $this->cacheService = $this->prophet->prophesize(CacheService::class);
        $this->validatorService = $this->prophet->prophesize(ValidatorService::class);
        $this->logger = $this->prophet->prophesize(LoggerInterface::class);
        $this->cache = new FilesystemAdapter();
        $this->spaceshipController = new SpaceshipController(
            $this->spaceshipService->reveal(),
            $this->cacheService->reveal(),
            $this->validatorService->reveal(),
            $this->logger->reveal()
        );

        $this->wrongPayloads = [
            'topsecret' => [
                'wrong-key-that-wouldnt-pass-validator' => [
                    [
                        'name' => 'kenobi',
                        'distance' => 500,
                        'message' => ['este', 'es', 'un', 'mensaje', '', '']
                    ],
                    [
                        'name' => 'skywalker',
                        'distance' => 424.2640687119285,
                        'message' => ['mensaje', 'este', '', 'un', '', 'pa']
                    ],
                    [
                        'name' => 'sato',
                        'distance' => 707.1067811865476,
                        'message' => ['', '', 'es', 'mensaje']
                    ]
                ]
            ],
            'topsecret_split' => [
                'distance' => 'wrong-floating-number',
                'message' => [],
            ]
        ];
    }

    /**
     * @dataProvider topSecretProvider
     */
    public function testItGetsSpaceshipDataCorrectly(array $body, array $satellitesData, array $expectedLocationCoords, string $expectedMessage): void
    {
        $this->validatorService
        ->validateSatellitesData(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(SatellitesDTOMock::getStub($satellitesData));

        $this->spaceshipService
        ->determineSpaceshipData(Argument::type(Satellites::class))
        ->shouldBeCalled()
        ->willReturn(
            SpaceshipMock::getStub([
                'position' => PositionMock::getStubFromArray($expectedLocationCoords),
                'message' => $expectedMessage
            ])
        );

        $result = $this->spaceshipController->determineSpaceshipData(new Request([], [], [], [], [], [], json_encode($body)));

        $expected = json_encode([
            'position' => $expectedLocationCoords,
            'message' => $expectedMessage
        ]);

        $this->assertEquals($expected, $result->getContent());
        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $this->prophet->checkPredictions();
    }

    public function testTopsecretCatchesHandlesValidationErrors() {
        $this->validatorService
        ->validateSatellitesData(Argument::type('string'))
        ->shouldBeCalled()
        ->willThrow(ValidationException::class);

        $this->logger
        ->error(Argument::type('string'))
        ->shouldBeCalled();
        
        $result = $this->spaceshipController->determineSpaceshipData(
            new Request([], [], [], [], [], [], json_encode($this->wrongPayloads['topsecret']))
        );

        $this->assertEquals(json_encode([]), $result->getContent());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result->getStatusCode());
        $this->prophet->checkPredictions();
    }

    /**
     * @dataProvider cacheStoreProvider
     */
    public function testItStoresSatelliteDataFragmentCorrectly(string $satelliteName, array $body, bool $isKnown = true, string $responseShouldContain): void
    {
        //happy path
        if($isKnown) {
            $this->validatorService
            ->validateSingleSatelliteData(Argument::type('string'))
            ->shouldBeCalled();
    
            $this->cacheService->cacheSatelliteData(Argument::exact($satelliteName), Argument::that(function ($requestContent) {
                return 
                    in_array('distance', array_keys(json_decode($requestContent, true))) && 
                    in_array('message', array_keys(json_decode($requestContent, true)));
            }))->shouldBeCalled();
        }

        //unknown satellite code branch
        $result = $this->spaceshipController->storeSpaceshipDataFragment(new Request([], [], [], [], [], [], json_encode($body)), $satelliteName);
        $this->assertStringContainsString($responseShouldContain, $result->getContent());
        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        
        $this->prophet->checkPredictions();
    }

    public function testItHandlesExceptionBeforeCachingData(): void
    {
        $this->validatorService
        ->validateSingleSatelliteData(Argument::type('string'))
        ->shouldBeCalled()
        ->willThrow(ValidationException::class);

        $this->logger
        ->error(Argument::type('string'))
        ->shouldBeCalled();
        
        $result = $this->spaceshipController->storeSpaceshipDataFragment(
            new Request([], [], [], [], [], [], json_encode($this->wrongPayloads['topsecret_split'])), 
            'kenobi'
        );

        $this->assertEquals(json_encode([]), $result->getContent());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result->getStatusCode());
        $this->prophet->checkPredictions();
    }


    /**
     * topsecret_split method test
     * @dataProvider topSecretSplitProvider
     *
     */
    public function testItGetSpaceshipDataFromFragments(array $satellitesData,  $allHit = true, array $expectedLocationCoords = [], string $expectedMessage = ''): void
    {
        $this->startCacheManagementForTest($satellitesData);

        $this->cacheService->validateAllItemsHit(Argument::type('array'))->willReturn($allHit);

        if(!$allHit) {
            $result = $this->spaceshipController->determineSpaceshipDataFromFragments();
    
            $this->assertStringContainsString('Not enough information to retrieve Spaceship data yet.', $result->getContent());
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getStatusCode());
            return;
        }
        
        $this->spaceshipService
        ->determineSpaceshipData(Argument::type(Satellites::class))
        ->shouldBeCalled()
        ->willReturn(
            SpaceshipMock::getStub([
                'position' => PositionMock::getStubFromArray($expectedLocationCoords),
                'message' => $expectedMessage
            ])
        );

        $this->cacheService->resetCache()->shouldBeCalled();

        $expected = json_encode([
            'position' => $expectedLocationCoords,
            'message' => $expectedMessage
        ]);

        $result = $this->spaceshipController->determineSpaceshipDataFromFragments();
        
        $this->assertEquals($expected, $result->getContent());
        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        
        $this->prophet->checkPredictions();

        //clears cache after test case.
        $this->cache->clear();
    }

    /**
     * @param mixed[] $satellitesData
     */
    private function startCacheManagementForTest(array $satellitesData): void
    {
        foreach($satellitesData as $key => $value) {
            $cacheItem = $this->cache->getItem($key)
            ->set(json_encode($value));

            $this->cache->save($cacheItem);

            $this->cacheService->getItem(Argument::exact($key))->willReturn($this->cache->getItem($key));
        }
    }

    public function cacheStoreProvider(): array
    {
        return [
            'happypath' => [
                'satelliteName' => 'kenobi',
                'body' => [
                    'distance' => 12312,
                    'message' => ["", "", "es", "mensaje"]
                ],
                'isKnown' => true,
                'responseShouldContain' => 'kenobi'
            ],
            'unknown satellite' => [
                'satelliteName' => 'unknown',
                'body' => [
                    'distance' => 12312,
                    'message' => ["", "", "es", "mensaje"]
                ],
                'isKnown' => false,
                'responseShouldContain' => '[]'
            ]
        ];
    }

    public function topSecretProvider(): array
    {
        return [
            'happypath' => [
                'body' => [
                    'satellites' => [
                        [
                            'name' => 'kenobi',
                            'distance' => 500,
                            'message' => ['este', 'es', 'un', 'mensaje', '', '']
                        ],
                        [
                            'name' => 'skywalker',
                            'distance' => 424.2640687119285,
                            'message' => ['mensaje', 'este', '', 'un', '', 'pa']
                        ],
                        [
                            'name' => 'sato',
                            'distance' => 707.1067811865476,
                            'message' => ['', '', 'es', 'mensaje']
                        ]
                    ]
                ],
                'satellitesData' => [
                    [   
                        "name" => 'kenobi',
                        "distance" => 500,
                        "message" => ["este", "", "", "mensaje", ""],
                    ],
                    [
                        "name" => 'skywalker',
                        "distance" => 424.2640687119285,
                        "message" => ["", "es", "", "", "secreto"],
                    ],
                    [
                        "name" => 'skywalker',
                        "distance" => 707.1067811865476,
                        "message" => ["este", "", "un", "", ""],
                    ]
                ],
                'expectedLocationCoords' => [
                    'x' => -200,
                    'y' => 200
                ],
                'expectedMessage' => 'este es un mensaje secreto'
            ],
            'multiple unknown satellites on request body' => [
                'body' => [
                    'satellites' => [
                        [
                            'name' => 'kenobi',
                            'distance' => 500,
                            'message' => ['este', 'es', 'un', 'mensaje', '', '']
                        ],
                        [
                            'name' => 'skywalker',
                            'distance' => 424.2640687119285,
                            'message' => ['mensaje', 'este', '', 'un', '', 'pa']
                        ],
                        [
                            'name' => 'sato',
                            'distance' => 707.1067811865476,
                            'message' => ['', '', 'es', 'mensaje']
                        ],
                        [
                            'name' => 'first-unknown',
                            'distance' => 707.1067811865476,
                            'message' => ['', '', 'es', 'mensaje']
                        ],
                        [
                            'name' => 'second-unknown',
                            'distance' => 707.1067811865476,
                            'message' => ['', '', 'es', 'mensaje']
                        ]
                    ]
                ],
                'satellitesData' => [
                    [   
                        "name" => 'kenobi',
                        "distance" => 500,
                        "message" => ["este", "", "", "mensaje", ""],
                    ],
                    [
                        "name" => 'skywalker',
                        "distance" => 424.2640687119285,
                        "message" => ["", "es", "", "", "secreto"],
                    ],
                    [
                        "name" => 'skywalker',
                        "distance" => 707.1067811865476,
                        "message" => ["este", "", "un", "", ""],
                    ]
                ],
                'expectedLocationCoords' => [
                    'x' => -200,
                    'y' => 200
                ],
                'expectedMessage' => 'este es un mensaje secreto'
            ]
        ];
    }

    public function topSecretSplitProvider(): array
    {
        return [
            'happy path' => [
                'satellitesData' => [
                    'kenobi' => [
                        "distance" => 707.1067811865476,
                        "message" => ["este", "es", "un", "mensaje", "", ""]
                    ],
                    'skywalker' => [
                        "distance" => 707.1067811865476,
                        "message" => ["mensaje", "este", "", "un", "", "secreto"]
                    ],
                    'sato' => [
                        "distance" => 707.1067811865476,
                        "message" => ["", "", "es", "mensaje"]
                    ]
                ],
                'allHit' => true,
                'expectedLocationCoords' => [
                    'x' => -200,
                    'y' => 200
                ],
                'expectedMessage' => 'este es un mensaje secreto',
            ],
            'Not enough data' => [
                //three satellites data just to keep the test unified. In a real test scenario, "not enough data" is satellitesData < 3
                'satellitesData' => [
                    'kenobi' => [
                        "distance" => 707.1067811865476,
                        "message" => ["este", "es", "un", "mensaje", "", ""]
                    ],
                    'skywalker' => [
                        "distance" => 707.1067811865476,
                        "message" => ["mensaje", "este", "", "un", "", "secreto"]
                    ],
                    'sato' => [
                        "distance" => 707.1067811865476,
                        "message" => ["mensaje", "este", "", "un", "", "secreto"]
                    ],
                ],
                'allHit' => false
            ]
        ];
    }
}