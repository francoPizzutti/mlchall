<?php 

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Position 
{
    private float $x;
    private float $y;

    public function __construct(
        float $x,
        float $y
    ) {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Own serialize function to tackle FOS serializer not working as expected.
     *
     * @return mixed[]
     */
    function serialize(): array {
        return get_object_vars($this);
    }
}