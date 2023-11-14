<?php 

namespace App\Service;

use App\Entity\Position;
use App\Entity\Spaceship;
use Nubs\Vectorix\Vector;
use App\Entity\Satellites;
use App\Entity\KnownSatellites;
use App\Service\Dto\SatelliteDTO;

class SpaceshipService
{
    public function determineSpaceshipData(Satellites $satellites): Spaceship
    {
        $position = $this->getLocation($satellites->getSatellitesDistances());
        $message = $this->getMessage($satellites->getSatellitesMessage());

        return new Spaceship(
            new Position($position['x'], $position['y']),
            $message
        );
    }

    /**
     * @param SatelliteDTO[] $satellites
     * @return mixed[]
     */
    private function getLocation(array $distances): array 
    {
        $kenobi = new Vector(KnownSatellites::KENOBI);
        $skywalker = new Vector(KnownSatellites::SKYWALKER);
        $sato = new Vector(KnownSatellites::SATO);
        
        $ex = $skywalker
            ->subtract($kenobi)
            ->divideByScalar($skywalker->subtract($kenobi)->length()); // (P2 - P1)/|P2-P1|  => unit vector pointing from Kenobi to Skywalker (U1)
        $i = $ex->dotProduct($sato->subtract($kenobi)); // (P2 - P1)/|P2-P1| . (P3 - P1) => orthogonality calcle between U1 and P3-P1 vector.

        $ey = $sato
            ->subtract($kenobi)
            ->subtract($ex->multiplyByScalar($i))->normalize(); // Unit vector from Skywalker to Sato without the component that is in the direction of U1 

        $d = $skywalker->subtract($kenobi)->length(); // |P2 - P1| => distance from kenobi to skywalker centers.

        $j = $ey->dotProduct($sato->subtract($kenobi)); // Projection of the vector P3 - P1 (kenobi to sato) in the direction of $ey

        $r1 = $distances['kenobi'];
        $r2 = $distances['skywalker'];
        $r3 = $distances['sato'];
        
        //calculation below lead by https://es.wikipedia.org/wiki/Trilateraci%C3%B3n

        $x = (float) (pow($r1, 2) - pow($r2, 2) + pow($d, 2)) / (2 * $d); 
        $y = (float) ((pow($r1 ,2) - pow($r3, 2) + pow($i, 2) + pow($j, 2)) / (2 * $j)) - (( $i / $j) * $x);

        $res = $kenobi->add($ex->multiplyByScalar($x))->add($ey->multiplyByScalar($y));

        return [
            'x' => $this->standarizeCoordComponent($res->components()[0]), 
            'y' => $this->standarizeCoordComponent($res->components()[1])
        ];
    }

    /**
     * @param mixed[] $messages Array of messages array indexed by satellite name
     * @return string
     */
    private function getMessage(array $messages): string
    {
        $kenobiMessage = $messages['kenobi']; 
        $skywalkerMessage = $messages['skywalker'];
        $satoMessage = $messages['sato'];
        
        $decodedMessage = [];
        $maxArraySize = max(count($kenobiMessage), count($skywalkerMessage), count($satoMessage));

        for ($i = 0; $i < $maxArraySize; $i++) {
            //check for empty (falsy) at same position of each array, if string is not empty, add to the decoded message, if index does not exists, pass "".
            $decodedMessage[$i] = $this->getWord($kenobiMessage[$i] ?? "", $skywalkerMessage[$i] ?? "", $satoMessage[$i] ?? "");
        }

        //create string from decodedMessage array, separating by blank space, and strip whitespaces at start/end of that string.
        return trim(implode(" ", array_filter($decodedMessage)));
    }

    private function getWord(string $kenobiWord, string $skywalkerWord, string $satoWord): string
    {
        $word = $kenobiWord ?: $skywalkerWord ?: $satoWord;
        return trim($word);
    }

    private function standarizeCoordComponent($coordComponent): float
    {
        return round($coordComponent, 5);
    }
}
