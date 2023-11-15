<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mock\Entity;

use App\Entity\Position;

class PositionMock 
{
    public static function getStub(float $x, float $y): Position
    {
        return new Position($x, $y);
    }

    public static function getStubFromArray($data): Position
    {
        return new Position($data['x'], $data['y']);
    }
}