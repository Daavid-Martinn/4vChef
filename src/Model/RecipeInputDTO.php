<?php

namespace App\Model;

class RecipeInputDTO
{
    public string $title;
    public int $numberDiner; // Swagger: number-diner
    public int $typeId;      // Swagger: type-id

    /** @var IngredientDTO[] */
    public array $ingredients = [];

    /** @var StepDTO[] */
    public array $steps = [];

    // Para la entrada, los nutrientes son simples: [['type-id' => 1, 'quantity' => 10], ...]
    public array $nutrients = [];
}