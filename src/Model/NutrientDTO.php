<?php

namespace App\Model;

class NutrientDTO
{
    public function __construct(
        public int $id,
        // En el Swagger de salida, el 'type' es un objeto con id, name y unit
        public array $type, 
        public float $quantity
    ) {}
}