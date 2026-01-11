<?php

namespace App\Model;

class IngredientDTO
{
    public function __construct(
        public string $name,
        public float $quantity,
        public string $unit
    ) {}
}