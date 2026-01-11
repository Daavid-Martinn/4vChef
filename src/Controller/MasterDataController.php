<?php

namespace App\Controller;

use App\Repository\NutrientTypeRepository;
use App\Repository\RecipeTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MasterDataController extends AbstractController
{
    #[Route('/recipe-types', name: 'app_recipe_types', methods: ['GET'])]
    public function getRecipeTypes(RecipeTypeRepository $repository): JsonResponse
    {
        $typesFromDB = $repository->findAll();
        
        // Convertimos las Entidades privadas a Arrays públicos
        $data = [];
        foreach ($typesFromDB as $type) {
            $data[] = [
                'id' => $type->getId(),
                'name' => $type->getName(),
                'description' => $type->getDescription()
            ];
        }

        return $this->json($data);
    }

    #[Route('/nutrient-types', name: 'app_nutrient_types', methods: ['GET'])]
    public function getNutrientTypes(NutrientTypeRepository $repository): JsonResponse
    {
        $nutrientsFromDB = $repository->findAll();

        // Lo mismo para los nutrientes
        $data = [];
        foreach ($nutrientsFromDB as $nutrient) {
            $data[] = [
                'id' => $nutrient->getId(),
                'name' => $nutrient->getName(),
                'unit' => $nutrient->getUnit()
            ];
        }

        return $this->json($data);
    }
}