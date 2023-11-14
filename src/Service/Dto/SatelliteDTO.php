<?php

namespace App\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SatelliteDTO
{
    /**
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private string $name;

    /**
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private float $distance;

    /**
     * @var string[]
     * @Assert\NotNull(message="Message mut not be null")
     * @Assert\NotBlank(message="Message must not be empty.")
     * @Assert\Count(min = 1)
     */
    private array $message;

    public function __construct(
        string $name,
        float $distance,
        array $message
    ) {
        $this->name = $name;
        $this->distance = $distance;
        $this->message = $message;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDistance(): float {
        return $this->distance;
    }

    public function setDistance(float $distance): void
    {
        $this->distance = $distance;
    }

    public function setMessage(array $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string[]
     */
    public function getMessage(): array {
        return $this->message;
    }
}