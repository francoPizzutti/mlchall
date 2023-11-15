<?php 

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use Prophecy\Prophet;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;
use App\Service\ValidatorService;
use App\Service\Dto\SatellitesDTO;
use App\Service\Dto\SatelliteDataDTO;
use Prophecy\Prophecy\ObjectProphecy;
use App\Tests\Unit\Mock\Dto\SatellitesDTOMock;
use App\Tests\Unit\Mock\Dto\SatelliteDataDTOMock;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidatorServiceTest extends TestCase
{
    private ObjectProphecy $validator;
    private ObjectProphecy $serializer;
    private Prophet $prophet;

    private ValidatorService $validatorService;

    protected function setUp(): void
    {
        $this->prophet = new Prophet();
        $this->validator = $this->prophet->prophesize(ValidatorInterface::class);
        $this->serializer = $this->prophet->prophesize(SerializerInterface::class);

        $this->validatorService = new ValidatorService(
            $this->validator->reveal(),
            $this->serializer->reveal()
        );
    }

    /**
     * Happy-path unit test for SatellitesDTO validation.
     * TODO: implement dataProvider for more test cases.
     *
     * @return void
     */
    public function testItValidateSatellitesData(): void
    {
        $payload = [
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
            ],
        ];

        $content = json_encode($payload);

        $satellitesDTO = SatellitesDTOMock::getStub($payload['satellites']);
        
        $this->serializer->deserialize(
            Argument::exact($content), 
            Argument::exact(SatellitesDTO::class), 
            Argument::exact('json')
        )->shouldBeCalled()
        ->willReturn($satellitesDTO);

        
        $constraintViolation = $this->prophet->prophesize(ConstraintViolationListInterface::class);
        $constraintViolation->count()->shouldBeCalled()->willReturn(0);

        $this->validator->validate(Argument::exact($satellitesDTO))->shouldBeCalled()->willReturn($constraintViolation->reveal());
        
        $result = $this->validatorService->validateSatellitesData($content);
        $this->assertEquals(SatellitesDTO::class, $result::class);
        $this->prophet->checkPredictions();
    }


    /**
     * Happy-path unit test for SatelliteDataDTO validation.
     * TODO: implement dataProvider for more test cases.
     * TODO2: encapsulate testing logic to be agnostic of classes since validation process is the same for both.
     *
     * @return void
     */
    public function testItValidateSatelliteData(): void
    {
        $payload = [
            'distance' => 500,
            'message' => ['este', 'es', 'un', 'mensaje', '', '']
        ];

        $content = json_encode($payload);

        $satelliteDataDTO = SatelliteDataDTOMock::getStub($payload);

        $this->serializer->deserialize(
            Argument::exact($content), 
            Argument::exact(SatelliteDataDTO::class), 
            Argument::exact('json')
        )->shouldBeCalled()
        ->willReturn($satelliteDataDTO);

        $constraintViolation = $this->prophet->prophesize(ConstraintViolationListInterface::class);
        $constraintViolation->count()->shouldBeCalled()->willReturn(0);

        $this->validator->validate(Argument::any())->shouldBeCalled()->willReturn($constraintViolation->reveal());
        
        $result = $this->validatorService->validateSingleSatelliteData($content);
        $this->assertEquals(SatelliteDataDTO::class, $result::class);
        $this->prophet->checkPredictions();
    }
}