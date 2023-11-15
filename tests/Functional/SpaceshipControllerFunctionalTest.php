<?php 

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class SpaceshipControllerFuncTest extends WebTestCase
{
    private KernelBrowser $client;
    private FilesystemAdapter $cache;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->cache = new FilesystemAdapter();
    }

    protected function tearDown(): void {
        $this->cache->clear();
    }

    /**
     * @dataProvider correctScenariosProviderForTopSecret
     * @param mixed[] $requestBody
     */
    public function testTopsecretValidScenarios(string $method, string $uri, array $requestBody, array $expectedOutput) {
        $this->sendRequest($method, $uri, $requestBody);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(json_encode($expectedOutput), $this->client->getResponse()->getContent());
    } 

    /**
     * @dataProvider invalidScenariosProviderForTopSecret
     * @param mixed[] $requestBody
     */
    public function testTopsecretInvalidScenarios(string $method, string $uri, array $requestBody): void
    {
        $this->sendRequest($method, $uri, $requestBody);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider topsecretSplitDataStoreScenarios
     */
    public function testItStoresSpaceshipDataFragment(
        string $method, 
        string $uri, 
        array $requestBody, 
        array $expectedOutput, 
        int $expectedResponseCode = 200
    ): void
    {
        $this->sendRequest($method, $uri, $requestBody);
        $this->assertEquals($expectedResponseCode, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(json_encode($expectedOutput), $this->client->getResponse()->getContent());
    }

    /**
     * @dataProvider topsecretSplitGetDataScenarios
     */
    public function testItGetsSpaceshipDataFromFragments(array $postRequestsData, array $expectedOutput, int $expectedResponseCode) {

        foreach($postRequestsData as $postRequestData) {
            $this->sendRequest('POST', '/api/topsecret_split/'. $postRequestData['satelliteName'], $postRequestData['body']);
        }

        $this->sendRequest('GET', '/api/topsecret_split');
        $this->assertEquals($expectedResponseCode, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(json_encode($expectedOutput), $this->client->getResponse()->getContent());
        //clear cache after each dataset because it's actually filled by previous executions and can lead to unintended 200's.
        $this->cache->clear();
    }

    private function sendRequest(string $method, string $uri, array $requestBody = []): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($requestBody)
        );
    }

    public function topsecretSplitGetDataScenarios(): array
    {
        return [
            'happy path' => [
                'postRequestsData' => [
                    [
                        'satelliteName' => 'kenobi',
                        'body' => [
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ]
                    ],
                    [
                        'satelliteName' => 'skywalker',
                        'body' => [
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ]
                    ],
                    [
                        'satelliteName' => 'sato',
                        'body' => [
                            "distance" => 707.1067811865476,
                            "message" => ["este", "", "un", "", ""],
                        ]
                    ]
                ],
                'expectedOutput' => [
                    'position' => [
                        'x' => -200,
                        'y' => 200,
                    ],
                    'message' => "este es un mensaje secreto"
                ],
                'expectedResponseCode' => Response::HTTP_OK
            ],
            'not enough data (only one request made)' => [
                'postRequestsData' => [
                    [
                        'satelliteName' => 'kenobi',
                        'body' => [
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ]
                    ],
                ],
                'expectedOutput' => ['Not enough information to retrieve Spaceship data yet.'],
                'expectedResponseCode' => Response::HTTP_BAD_REQUEST
            ],
            'not enough data (only two request made)' => [
                'postRequestsData' => [
                    [
                        'satelliteName' => 'kenobi',
                        'body' => [
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ]
                    ],
                    [
                        'satelliteName' => 'skywalker',
                        'body' => [
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ]
                    ],
                ],
                'expectedOutput' => ['Not enough information to retrieve Spaceship data yet.'],
                'expectedResponseCode' => Response::HTTP_BAD_REQUEST
            ],
        ];
    }

    public function correctScenariosProviderForTopSecret(): array
    {
        return [
            'happy path' => [
                'method' => 'POST',
                'uri' => '/api/topsecret',
                'requestBody' => [
                    "satellites" => [
                        [
                            "name" => "kenobi",
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ],
                        [
                            "name" => "skywalker",
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ],
                        [
                            "name" => "sato",
                            "distance" => 707.1067811865476,
                            "message" => ["este", "", "un", "", ""]
                        ],
                    ]
                ],
                'expectedOutput' => [
                    'position' => [
                        'x' => -200,
                        'y' => 200,
                    ],
                    'message' => "este es un mensaje secreto"
                ]
            ],
            'more than three satellites (exceeds the needed data, but can scale request body)' => [
                'method' => 'POST',
                'uri' => '/api/topsecret',
                'requestBody' => [
                    "satellites" => [
                        [
                            "name" => "kenobi",
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ],
                        [
                            "name" => "skywalker",
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ],
                        [
                            "name" => "sato",
                            "distance" => 707.1067811865476,
                            "message" => ["este", "", "un", "", ""]
                        ],
                        [
                            "name" => "yoda",
                            "distance" => 231,
                            "message" => ["este", "", "un", "", "mensaje2"]
                        ],
                    ]
                ],
                'expectedOutput' => [
                    'position' => [
                        'x' => -200,
                        'y' => 200,
                    ],
                    'message' => "este es un mensaje secreto"
                ]
            ],
            //TODO: add more cases with different coords.
        ];
    }

    public function invalidScenariosProviderForTopSecret(): array
    {
        return [
            'empty body' => [
                'method' => 'POST',
                'uri' => '/api/topsecret',
                'requestBody' => []
            ],
            'wrong keys on body' => [
                'method' => 'POST',
                'uri' => '/api/topsecret',
                'requestBody' => [
                    "wrong-key" => [
                        [
                            "name" => "kenobi",
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ],
                        [
                            "name" => "skywalker",
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ],
                        [
                            "name" => "sato",
                            "distance" => 707.1067811865476,
                            "message" => ["este", "", "un", "", ""]
                        ],
                    ]
                ]
            ],
            'less than three satellites' => [
                'method' => 'POST',
                'uri' => '/api/topsecret',
                'requestBody' => [
                    "satellites" => [
                        [
                            "name" => "kenobi",
                            "distance" => 500,
                            "message" => ["este", "", "", "mensaje", ""],
                        ],
                        [
                            "name" => "skywalker",
                            "distance" => 424.2640687119285,
                            "message" => ["", "es", "", "", "secreto"],
                        ],
                    ],
                ]
            ]
        ];
    }


    public function topsecretSplitDataStoreScenarios(): array
    {
        return [
            'topsecret_split' => [
                'method' => 'POST',
                'uri' => '/api/topsecret_split/kenobi',
                'requestBody' => [
                    "distance" => 500,
                    "message" => ["este", "", "", "mensaje", ""],
                ],
                'expectedOutput' => ["Data stored for satellite: kenobi"]
            ],
            'topsecret_split fails because of wrong distance' => [
                'method' => 'POST',
                'uri' => '/api/topsecret_split/kenobi',
                'requestBody' => [
                    "distance" => "asd",
                    "message" => ["este", "", "", "mensaje", ""],
                ],
                'expectedOutput' => [],
                'expectedResponseCode' => Response::HTTP_NOT_FOUND
            ],
            'topsecret_split fails because of empty message' => [
                'method' => 'POST',
                'uri' => '/api/topsecret_split/kenobi',
                'requestBody' => [
                    "distance" => "asd",
                    "message" => [],
                ],
                'expectedOutput' => [],
                'expectedResponseCode' => Response::HTTP_NOT_FOUND
            ],
            'topsecret_split fails because of no message' => [
                'method' => 'POST',
                'uri' => '/api/topsecret_split/kenobi',
                'requestBody' => [
                    "distance" => "asd",
                ],
                'expectedOutput' => [],
                'expectedResponseCode' => Response::HTTP_NOT_FOUND
            ],
            'topsecret_split dismisses the request' => [
                'method' => 'POST',
                'uri' => '/api/topsecret_split/unknown_satellite',
                'requestBody' => [
                    "distance" => 500,
                    "message" => ["este", "", "", "mensaje", ""],
                ],
                'expectedOutput' => [],
                'expectedResponseCode' => Response::HTTP_OK
            ],
        ];
    }
}
       