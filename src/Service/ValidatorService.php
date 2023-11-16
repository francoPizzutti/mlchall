<?php 

namespace App\Service;

use App\Service\Dto\SatellitesDTO;
use App\Service\Dto\SatelliteDataDTO;
use App\Exception\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService
{
    public const DESERIALIZE_FORMAT = 'json';
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;

    public function __construct(
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }
    
    public function validateSatellitesData(string $requestContent): SatellitesDTO
    {
        return $this->validateDTO($requestContent, SatellitesDTO::class);
    }

    public function validateSingleSatelliteData(string $requestContent): SatelliteDataDTO
    {
        return $this->validateDTO($requestContent, SatelliteDataDTO::class);
    }

    /**
     * @return object
     * @throws ValidationException
     */
    public function validateDTO(string $requestContent, string $dtoClass): object
    {
        $dto = $this->serializer->deserialize($requestContent, $dtoClass, self::DESERIALIZE_FORMAT);
        $errors = $this->validator->validate($dto);

        if (!empty($errors->count())) {
            throw new ValidationException($dtoClass);
        }

        return $dto;
    }
}
