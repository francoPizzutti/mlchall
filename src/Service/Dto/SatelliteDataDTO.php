<?php

namespace App\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SatelliteDataDTO
{
    /**
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotNull(message="Distance must not be null.")
     * @Assert\NotBlank(message="Distance must not be empty.")
     */
    private float $distance;

    /**
     * @var string[]
     * @Assert\NotNull(message="Message mut not be null")
     * @Assert\NotBlank(message="Message must not be empty.")
     */
    private array $message;

    public function __construct(
        float $distance,
        array $message
    ) {
        $this->distance = $distance;
        $this->message = $message;
    }
    
    public function setDistance(float $distance): void
    {
        $this->distance = $distance;
    }

    public function setMessage(array $message): void
    {
        $this->message = $message;
    }
}