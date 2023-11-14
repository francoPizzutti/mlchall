<?php

namespace App\Entity;

use App\Entity\Position;

class Spaceship
{
    private Position $position;
    private string $message;

    public function __construct(
        Position $position,
        string $message
    ) {
        $this->position = $position;
        $this->message = $message;
    }

    public function getPosition(): Position 
    {
        return $this->position;
    }

    public function getMessage(): string 
    {
        return $this->message;    
    }
}
