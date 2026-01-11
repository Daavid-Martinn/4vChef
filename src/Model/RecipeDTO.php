<?php

namespace App\Model;

class RecipeDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public int $numberDiner, // Swagger: number-diner
        
        // Objeto completo del tipo de receta ['id'=>1, 'name'=>'Postre'...]
        public array $type,
        
        /** @var IngredientDTO[] */
        public array $ingredients,
        
        /** @var StepDTO[] */
        public array $steps,
        
        /** @var NutrientDTO[] */
        public array $nutrients,
        
        // Objeto rating ['number-votes'=>10, 'rating-avg'=>4.5]
        public array $rating
    ) {}
}