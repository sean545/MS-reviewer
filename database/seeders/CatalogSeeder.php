<?php

namespace Database\Seeders;

use App\Enums\CourseCategory;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample MS Data Science catalog so the panels have something to show.
 * Course codes and titles are a generic placeholder curriculum — edit them in /admin.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::updateOrCreate(
            ['code' => 'MSDS'],
            [
                'name' => 'Master of Science in Data Science',
                'slug' => 'ms-data-science',
                'description' => 'Graduate program covering statistics, machine learning, data engineering and responsible data practice.',
                'is_published' => true,
            ],
        );

        foreach ($this->courses() as $order => [$code, $title, $units, $category]) {
            $program->courses()->updateOrCreate(
                ['code' => $code],
                [
                    'title' => $title,
                    'slug' => Str::slug("{$code} {$title}"),
                    'units' => $units,
                    'category' => $category,
                    'sort_order' => $order,
                    'is_published' => true,
                ],
            );
        }

        $professor = User::where('email', 'prof@reviewer.test')->first();

        if ($professor !== null) {
            $program->courses()->whereIn('code', ['DS 210', 'DS 230'])->get()
                ->each(fn ($course) => $course->professors()->syncWithoutDetaching([$professor->id]));
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: int, 3: CourseCategory}>
     */
    private function courses(): array
    {
        return [
            ['DS 200', 'Mathematics for Data Science', 3, CourseCategory::Foundation],
            ['DS 201', 'Programming for Data Science', 3, CourseCategory::Foundation],
            ['DS 210', 'Statistical Inference', 3, CourseCategory::Core],
            ['DS 220', 'Data Management and Warehousing', 3, CourseCategory::Core],
            ['DS 230', 'Machine Learning', 3, CourseCategory::Core],
            ['DS 240', 'Data Visualization and Communication', 3, CourseCategory::Core],
            ['DS 250', 'Big Data Systems', 3, CourseCategory::Core],
            ['DS 260', 'Data Ethics and Governance', 3, CourseCategory::Core],
            ['DS 270', 'Deep Learning', 3, CourseCategory::Elective],
            ['DS 271', 'Natural Language Processing', 3, CourseCategory::Elective],
            ['DS 272', 'Time Series Analysis and Forecasting', 3, CourseCategory::Elective],
            ['DS 273', 'Bayesian Data Analysis', 3, CourseCategory::Elective],
            ['DS 299', 'Graduate Seminar', 1, CourseCategory::Thesis],
            ['DS 300', 'Master\'s Thesis', 6, CourseCategory::Thesis],
        ];
    }
}
