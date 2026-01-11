<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\RecipeNutrient;
use App\Entity\Step;
use App\Model\IngredientDTO;
use App\Model\NutrientDTO;
use App\Model\RecipeDTO;
use App\Model\RecipeTypeDTO;
use App\Model\StepDTO;
use App\Repository\NutrientTypeRepository;
use App\Repository\RatingRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipes')]
class RecipeController extends AbstractController
{
    // Método 1: GET (Listar recetas con filtro opcional)
    #[Route('', name: 'app_recipe_list', methods: ['GET'])]
    public function list(Request $request, RecipeRepository $recipeRepo): JsonResponse
    {
        // 1. Miramos si hay filtro en la URL (?type=X)
        $typeId = $request->query->get('type');

        if ($typeId) {
            // Buscamos solo las de ese tipo
            $recipes = $recipeRepo->findBy(['recipeType' => $typeId, 'deletedAt' => null]);
        } else {
            // Buscamos todas las que NO estén borradas
            $recipes = $recipeRepo->findBy(['deletedAt' => null]);
        }

        // 2. Convertimos las Entidades (BBDD) a DTOs (JSON)
        $data = [];
        foreach ($recipes as $recipe) {
            $data[] = $this->mapEntityToDTO($recipe);
        }

        return $this->json($data);
    }

    // Método 2: POST (Crear receta nueva)
    #[Route('', name: 'app_recipe_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        RecipeTypeRepository $typeRepo,
        NutrientTypeRepository $nutrientRepo
    ): JsonResponse
    {
        $data = $request->toArray(); // Recibimos el JSON del Postman

        // 1. Validaciones básicas
        if (empty($data['ingredients']) || empty($data['steps'])) {
            return $this->json(['error' => 'La receta debe tener ingredientes y pasos'], 400);
        }

        // 2. Buscar el Tipo de Receta en BBDD (usando el 'type-id' del JSON)
        $type = $typeRepo->find($data['type-id']);
        if (!$type) {
            return $this->json(['error' => 'Tipo de receta no válido'], 400);
        }

        // 3. Crear la Receta (Padre)
        $recipe = new Recipe();
        $recipe->setTitle($data['title']);
        $recipe->setDiners($data['number-diner']);
        $recipe->setRecipeType($type);
        
        // 4. Guardar Ingredientes (Hijos)
        foreach ($data['ingredients'] as $ingData) {
            $ingredient = new Ingredient();
            $ingredient->setName($ingData['name']);
            $ingredient->setQuantity($ingData['quantity']);
            $ingredient->setUnit($ingData['unit']);
            // La magia: conectamos hijo con padre
            $recipe->addIngredient($ingredient); 
            $em->persist($ingredient);
        }

        // 5. Guardar Pasos (Hijos)
        foreach ($data['steps'] as $stepData) {
            $step = new Step();
            $step->setStepOrder($stepData['order']);
            $step->setDescription($stepData['description']);
            $recipe->addStep($step);
            $em->persist($step);
        }

        // 6. Guardar Nutrientes (Tabla Pivote)
        if (isset($data['nutrients'])) {
            foreach ($data['nutrients'] as $nutData) {
                $nutrientType = $nutrientRepo->find($nutData['type-id']);
                if ($nutrientType) {
                    $recipeNutrient = new RecipeNutrient();
                    $recipeNutrient->setRecipe($recipe); // Conecta con receta
                    $recipeNutrient->setNutrientType($nutrientType); // Conecta con tipo nutriente
                    $recipeNutrient->setQuantity($nutData['quantity']);
                    $em->persist($recipeNutrient);
                }
            }
        }

        // 7. Guardar todo en BBDD de golpe
        $em->persist($recipe);
        $em->flush();

        // Devolvemos la receta creada convertida a DTO
        return $this->json($this->mapEntityToDTO($recipe));
    }

    // Método 3: DELETE (Borrado lógico)
    #[Route('/{id}', name: 'app_recipe_delete', methods: ['DELETE'])]
    public function delete(int $id, RecipeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $recipe = $repo->find($id);

        if (!$recipe) {
            return $this->json(['error' => 'Receta no encontrada'], 404);
        }

        // Borrado lógico: Ponemos la fecha actual
        $recipe->setDeletedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->mapEntityToDTO($recipe));
    }

    // Método 4: Valorar (Rating)
    #[Route('/{recipeId}/rating/{rate}', name: 'app_recipe_rate', methods: ['POST'])]
    public function rate(
        int $recipeId, 
        int $rate, 
        Request $request, 
        RecipeRepository $recipeRepo,
        RatingRepository $ratingRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        // Validaciones
        if ($rate < 0 || $rate > 5) {
            return $this->json(['error' => 'El voto debe ser entre 0 y 5'], 400);
        }

        $recipe = $recipeRepo->find($recipeId);
        if (!$recipe) {
            return $this->json(['error' => 'Receta no encontrada'], 404);
        }

        // Validar IP única
        $ip = $request->getClientIp() ?? '127.0.0.1';
        $existingVote = $ratingRepo->findOneBy(['recipe' => $recipe, 'ipAddress' => $ip]);

        if ($existingVote) {
            return $this->json(['error' => 'Ya has votado esta receta'], 400);
        }

        // Crear voto
        $rating = new Rating();
        $rating->setScore($rate);
        $rating->setIpAddress($ip);
        $recipe->addRating($rating); // Vinculamos

        $em->persist($rating);
        $em->flush();

        return $this->json($this->mapEntityToDTO($recipe));
    }

    /**
     * Esta función privada es tu "Traductor"
     * Convierte la Entidad compleja de BBDD al DTO bonito del Swagger
     */
    private function mapEntityToDTO(Recipe $recipe): RecipeDTO
    {
        // 1. Mapear Ingredientes
        $ingredientsDTO = [];
        foreach ($recipe->getIngredients() as $ing) {
            $ingredientsDTO[] = new IngredientDTO($ing->getName(), $ing->getQuantity(), $ing->getUnit());
        }

        // 2. Mapear Pasos
        $stepsDTO = [];
        foreach ($recipe->getSteps() as $step) {
            $stepsDTO[] = new StepDTO($step->getStepOrder(), $step->getDescription());
        }

        // 3. Mapear Nutrientes (Ojo, que aquí sacamos datos de la pivote)
        $nutrientsDTO = [];
        foreach ($recipe->getRecipeNutrients() as $rn) {
            $typeDTO = [
                'id' => $rn->getNutrientType()->getId(),
                'name' => $rn->getNutrientType()->getName(),
                'unit' => $rn->getNutrientType()->getUnit(),
            ];
            $nutrientsDTO[] = new NutrientDTO($rn->getId(), $typeDTO, $rn->getQuantity());
        }

        // 4. Calcular Rating (Media y total)
        $totalVotes = count($recipe->getRatings());
        $sum = 0;
        foreach ($recipe->getRatings() as $r) {
            $sum += $r->getScore();
        }
        $avg = $totalVotes > 0 ? $sum / $totalVotes : 0;

        // 5. Devolver el DTO Gigante
        return new RecipeDTO(
            $recipe->getId(),
            $recipe->getTitle(),
            $recipe->getDiners(),
            [
                'id' => $recipe->getRecipeType()->getId(),
                'name' => $recipe->getRecipeType()->getName(),
                'description' => $recipe->getRecipeType()->getDescription()
            ],
            $ingredientsDTO,
            $stepsDTO,
            $nutrientsDTO,
            ['number-votes' => $totalVotes, 'rating-avg' => round($avg, 1)]
        );
    }
}