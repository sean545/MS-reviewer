<?php

namespace Database\Factories;

use App\Enums\CourseCategory;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'DS '.fake()->unique()->numberBetween(200, 299);

        return [
            'program_id' => Program::factory(),
            'code' => $code,
            'title' => Str::title(fake()->words(3, true)),
            'slug' => Str::slug($code.' '.Str::random(6)),
            'units' => 3,
            'category' => CourseCategory::Core,
            'description' => fake()->paragraph(),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
