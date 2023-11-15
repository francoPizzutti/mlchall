<?php 

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Spaceship;
use App\Entity\Satellites;
use PHPUnit\Framework\TestCase;
use App\Service\SpaceshipService;
use App\Tests\Unit\Mock\Entity\PositionMock;
use App\Tests\Unit\Mock\Entity\SatelliteMock;
use App\Tests\Unit\Mock\Entity\SpaceshipMock;
use App\Tests\Unit\Mock\Entity\SatellitesMock;



class SpaceshipServiceTest extends TestCase
{
    private SpaceshipService $spaceshipService; 

    protected function setUp(): void
    {
        $this->spaceshipService = new SpaceshipService();
    }

    /**
     * @dataProvider satellitesProvider
     */
    public function testItDeterminesSpaceshipData(Satellites $satellites, Spaceship $expected): void
    {
        $result = $this->spaceshipService->determineSpaceshipData($satellites);
        $this->assertEquals($expected, $result);
    }

    public function satellitesProvider(): array
    {
        return [
            'case 1 (common use case)' => [
                'satellites' => SatellitesMock::getStub([
                        SatelliteMock::getStub([
                            'name' => 'kenobi', 
                            'distance' => 500, 
                            'message' => ["este", "es", "un", "mensaje", "", ""]
                        ]),
                        SatelliteMock::getStub([
                            'name' => 'skywalker',
                            'distance' => 424.2640687119285, 
                            'message' => ["mensaje", "este", "", "un", "", "secreto"]]),
                        SatelliteMock::getStub([
                            'name' => 'sato', 
                            'distance' => 707.1067811865476, 
                            'message' => ["", "", "es", "mensaje"]
                        ])
                    ]),
                'expected' => SpaceshipMock::getStub(
                    [
                        'position' => PositionMock::getStub(-200, 200),
                        'message' => 'este es un mensaje secreto'
                    ]
                ),
            ],
            'case 2 (common use case)' => [
                'satellites' => SatellitesMock::getStub([
                        SatelliteMock::getStub([
                            'name' => 'kenobi', 
                            'distance' => 1000, 
                            'message' => ["this", "", "a", "message", "", "space"]
                        ]),
                        SatelliteMock::getStub([
                            'name' => 'skywalker',
                            'distance' => 700, 
                            'message' => ["this", "is", "", "", "from"]]),
                        SatelliteMock::getStub([
                            'name' => 'sato', 
                            'distance' => 640.3124237432849, 
                            'message' => ["this", "", "", "", "", "space"]
                        ])
                    ]),
                'expected' => SpaceshipMock::getStub(
                    [
                        'position' => PositionMock::getStub(100, 600),
                        'message' => 'this is a message from space'
                    ]
                ),
            ],
            'case 2 with more than three satellites' => [
                'satellites' => SatellitesMock::getStub([
                        SatelliteMock::getStub([
                            'name' => 'kenobi', 
                            'distance' => 1000, 
                            'message' => ["this", "", "a", "message", "", "space"]
                        ]),
                        SatelliteMock::getStub([
                            'name' => 'skywalker',
                            'distance' => 700, 
                            'message' => ["this", "is", "", "", "from"]
                        ]),
                        SatelliteMock::getStub([
                            'name' => 'sato', 
                            'distance' => 640.3124237432849, 
                            'message' => ["this", "", "", "", "", "space"]
                        ]),
                        SatelliteMock::getStub([
                            'name' => 'aurora', 
                            'distance' => 640.3124237432849, 
                            'message' => ["this", "", "", ""]
                        ]),
                    ]),
                'expected' => SpaceshipMock::getStub(
                    [
                        'position' => PositionMock::getStub(100, 600),
                        'message' => 'this is a message from space'
                    ]
                ),
            ],
        ];
    }
}