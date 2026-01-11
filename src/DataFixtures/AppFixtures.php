<?php

namespace App\DataFixtures;

use App\Entity\NutrientType;
use App\Entity\RecipeType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- 1. CREAR TIPOS DE RECETA ---
        // Lista de tipos fijos según el enunciado (nombre, descripción)
        $recipeTypes = [
            ['Postre', 'Platos dulces para finalizar la comida'],
            ['Ensalada', 'Platos fríos a base de vegetales'],
            ['Carne', 'Platos principales de origen animal'],
            ['Potaje', 'Platos de cuchara y legumbres'],
            ['Pescado', 'Delicias provenientes del mar']
        ];

        foreach ($recipeTypes as $data) {
            $type = new RecipeType();
            $type->setName($data[0]);
            $type->setDescription($data[1]);
            $manager->persist($type);
        }

        // --- 2. CREAR TIPOS DE NUTRIENTES ---
        // Lista de nutrientes fijos (nombre, unidad)
        $nutrientTypes = [
            ['Proteínas', 'Gramos'],
            ['Energía', 'Kcal'],
            ['Grasas', 'Gramos'],
            ['Hidratos de Carbono', 'Gramos'],
            ['Fibra', 'Gramos'],
            ['Sal', 'Miligramos']
        ];

        foreach ($nutrientTypes as $data) {
            $nutrient = new NutrientType();
            $nutrient->setName($data[0]);
            $nutrient->setUnit($data[1]); // Aquí usamos la unidad fija del tipo
            $manager->persist($nutrient);
        }

        // --- 3. GUARDAR TODO EN LA BBDD ---
        $manager->flush();
    }
}