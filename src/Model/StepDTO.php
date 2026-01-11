<?php

namespace App\Model;

class StepDTO
{
    public function __construct(
        public int $order,
        public string $description
    ) {}
}