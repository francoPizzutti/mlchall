<?php

namespace App\Controller;

use App\Entity\KnownSatellites;
use Throwable;
use App\Entity\Spaceship;
use App\Entity\Satellites;
use App\Service\CacheService;
use App\Service\SpaceshipService;
use App\Service\ValidatorService;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;

class SpaceshipController extends AbstractFOSRestController {

    private SpaceshipService $spaceshipService;
    private CacheService $cacheService;
    private ValidatorService $validatorService;
    private LoggerInterface $logger;

    function __construct(
        SpaceshipService $spaceshipService,
        CacheService $cacheService,
        ValidatorService $validatorService,
        LoggerInterface $logger
    ) {
        $this->spaceshipService = $spaceshipService;
        $this->cacheService = $cacheService;
        $this->validatorService = $validatorService;
        $this->logger = $logger;
    }

    /**
     * Calculates spaceship position and sent message based on given satellites data.
     * @Rest\Post("/api/topsecret")
     * @Rest\View(serializerGroups={"position"}, serializerEnableMaxDepthChecks=true)
     */
    public function determineSpaceshipData(Request $request): JsonResponse {
        try {
            
            $satellitesDTO = $this->validatorService->validateSatellitesData($request->getContent());
            
            $satellites = Satellites::fromDto($satellitesDTO);
            
            $spaceship = $this->spaceshipService->determineSpaceshipData($satellites);

            return new JsonResponse($this->formatSpaceShipResponse($spaceship), JsonResponse::HTTP_OK);

        } catch(Throwable $th) {
            $this->logger->error($th->getMessage());
            return new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Stores satellite data in cache
     * @Rest\Post("/api/topsecret_split/{satelliteName}")
     */
    public function storeSpaceshipDataFragment(Request $request, string $satelliteName): JsonResponse
    {
        //dismisses the request with no error if satellite is unknown, to avoid cache overcharging.
        if(KnownSatellites::isUnknown($satelliteName)) {
            return new JsonResponse([], JsonResponse::HTTP_OK);
        }

        try {
            $this->validatorService->validateSingleSatelliteData($request->getContent());
            $this->cacheService->cacheSatelliteData($satelliteName, $request->getContent());

            return new JsonResponse([sprintf('Data stored for satellite: %s', $satelliteName)], JsonResponse::HTTP_OK);
        } catch(Throwable $th) {
            $this->logger->error($th->getMessage());
            
            return new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Checks and calculates (if possible) spaceship position and message based on cached satelites data
     * @Rest\Get("/api/topsecret_split")
     * @Rest\View(serializerGroups={"position"}, serializerEnableMaxDepthChecks=true)
     */
    public function determineSpaceshipDataFromFragments(): JsonResponse
    {
        $kenobi = $this->cacheService->getItem('kenobi');
        $skywalker = $this->cacheService->getItem('skywalker');
        $sato = $this->cacheService->getItem('sato');

        $cacheItems = [$kenobi, $skywalker, $sato];
        var_dump($cacheItems);
        if(!$this->cacheService->validateAllItemsHit($cacheItems)) {
            return new JsonResponse([
                'Not enough information to retrieve Spaceship data yet.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $satellites = Satellites::fromCacheItems($cacheItems);
        $spaceship = $this->spaceshipService->determineSpaceshipData($satellites);

        $this->cacheService->resetCache();
        return new JsonResponse($this->formatSpaceShipResponse($spaceship), JsonResponse::HTTP_OK);
    }

    /**
     * Formats common response for two endpoints on this controller.
     * @return mixed[]
     */
    private function formatSpaceShipResponse(Spaceship $spaceship): array {
        return [
            'position' => $spaceship->getPosition()->serialize(),
            'message' => $spaceship->getMessage(),
        ];
    }
}



