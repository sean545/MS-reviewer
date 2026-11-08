<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state: a published, site-wide announcement.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'published_at' => now()->subHour(),
        ];
    }

    public function forCourse(?Course $course = null): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course ?? Course::factory(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->addDay(),
        ]);
    }
}
