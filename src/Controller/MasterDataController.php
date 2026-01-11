<?php

namespace App\Controller;

use App\Repository\NutrientTypeRepository;
use App\Repository\RecipeTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MasterDataController extends AbstractController
{
    // Fíjate: Ya no usamos __construct para crear datos falsos.
    // Los datos ya están en la BBDD gracias a las Fixtures que hicimos antes.

    #[Route('/recipe-types', name: 'app_recipe_types', methods: ['GET'])]
    public function getRecipeTypes(RecipeTypeRepository $repository): JsonResponse
    {
        // 1. Vamos a la BBDD a por los datos (en tu código viejo esto era $this->restauranteItaliano)
        $typesFromDB = $repository->findAll();

        // 2. Symfony es listo y al hacer json() convierte las Entidades a JSON automáticamente.
        // (Nota: Si necesitamos formato exacto de DTO, lo mapeamos aquí, pero para empezar esto vale)
        return $this->json($typesFromDB);
    }

    #[Route('/nutrient-types', name: 'app_nutrient_types', methods: ['GET'])]
    public function getNutrientTypes(NutrientTypeRepository $repository): JsonResponse
    {
        // 1. Buscamos todos los nutrientes en la BBDD
        $nutrientsFromDB = $repository->findAll();

        // 2. Devolvemos el JSON
        return $this->json($nutrientsFromDB);
    }
}