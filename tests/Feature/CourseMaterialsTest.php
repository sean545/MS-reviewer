<?php

namespace Tests\Feature;

use App\Filament\Learn\Resources\Courses\CourseResource as StudentCourseResource;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\TopicsRelationManager;
use App\Jobs\ProcessMaterial;
use App\Models\Course;
use App\Models\Material;
use App\Models\Topic;
use App\Models\User;
use Database\Factories\MaterialFactory;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CourseMaterialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Material::DISK);
    }

    public function test_professor_adds_a_topic_with_a_pdf_to_their_course(): void
    {
        Queue::fake();
        $professor = User::factory()->professor()->create();
        $course = Course::factory()->create();
        $course->professors()->attach($professor);
        $this->actingAs($professor);
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(TopicsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'title' => 'Week 1 · Foundations',
                'is_published' => true,
                'materials' => [
                    [
                        'file_path' => [UploadedFile::fake()->createWithContent('intro-notes.pdf', MaterialFactory::samplePdfContents('Intro'))],
                        'title' => 'Intro Notes',
                    ],
                ],
            ])
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $material = $course->topics()->sole()->materials()->sole();

        $this->assertSame('Intro Notes', $material->title);
        $this->assertSame('intro-notes.pdf', $material->original_name);
        $this->assertTrue($material->uploader->is($professor));
        Storage::disk(Material::DISK)->assertExists($material->file_path);
        Queue::assertPushed(ProcessMaterial::class, fn (ProcessMaterial $job): bool => $job->material->is($material));
    }

    public function test_processing_reads_the_page_count_and_renders_a_thumbnail(): void
    {
        $material = Material::factory()->create();

        $material->refresh();

        $this->assertSame(1, $material->page_count);
        $this->assertGreaterThan(0, $material->size_bytes);
        $this->assertNotNull($material->processed_at);
        Storage::disk(Material::DISK)->assertExists($material->thumbnail_path);
    }

    public function test_students_read_published_lessons_in_the_portal(): void
    {
        $material = Material::factory()->create(['title' => 'Linear Regression']);
        $course = $material->topic->course;
        $this->actingAs(User::factory()->create());

        $this->get(StudentCourseResource::getUrl('view', ['record' => $course], panel: 'learn'))
            ->assertOk()
            ->assertSee('Linear Regression')
            ->assertSee($material->topic->title);

        $this->get(StudentCourseResource::readUrl($material))
            ->assertOk()
            ->assertSee(route('materials.file', $material), escape: false);

        $this->get(route('materials.file', $material))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('materials.thumbnail', $material))->assertOk();
    }

    public function test_unpublished_topics_stay_hidden_from_students_but_not_from_their_professor(): void
    {
        $material = Material::factory()->for(Topic::factory()->unpublished())->create();
        $professor = User::factory()->professor()->create();
        $material->topic->course->professors()->attach($professor);

        $this->actingAs(User::factory()->create());
        $this->get(route('materials.file', $material))->assertForbidden();
        $this->get(StudentCourseResource::readUrl($material))->assertNotFound();

        $this->actingAs($professor);
        $this->get(route('materials.file', $material))->assertOk();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $material = Material::factory()->create();

        $this->get(route('materials.file', $material))->assertRedirect(route('filament.learn.auth.login'));
    }

    public function test_deleting_a_topic_removes_its_files(): void
    {
        $material = Material::factory()->create();
        $material->refresh();
        $files = [$material->file_path, $material->thumbnail_path];

        $material->topic->delete();

        $this->assertModelMissing($material);
        foreach ($files as $file) {
            Storage::disk(Material::DISK)->assertMissing($file);
        }
    }
}
