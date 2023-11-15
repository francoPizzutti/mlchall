<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Entity;

use App\Entity\Spaceship;

class SpaceshipMock 
{
    public static function getStub(array $spaceshipData): Spaceship
    {
        return new Spaceship(
            $spaceshipData['position'],
            $spaceshipData['message']
        );
    }
}