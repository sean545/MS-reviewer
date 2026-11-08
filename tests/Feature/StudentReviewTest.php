<?php

namespace Tests\Feature;

use App\Filament\Learn\Pages\SearchReadings;
use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Filament\Learn\Resources\Courses\Pages\ListCourses;
use App\Filament\Learn\Resources\Courses\Pages\ReadMaterial;
use App\Filament\Learn\Resources\Notes\Pages\ManageNotes;
use App\Filament\Learn\Widgets\ContinueReviewing;
use App\Filament\Learn\Widgets\ReviewStats;
use App\Models\Material;
use App\Models\Note;
use App\Models\ReadingProgress;
use App\Models\Topic;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Material::DISK);
        $this->student = User::factory()->create();
        $this->actingAs($this->student);
        Filament::setCurrentPanel('learn');
    }

    public function test_opening_a_reading_records_it_and_it_can_be_marked_done(): void
    {
        $material = Material::factory()->create();
        $course = $material->topic->course;

        $component = Livewire::test(ReadMaterial::class, ['record' => $course->getRouteKey(), 'materialId' => $material->id]);

        $progress = ReadingProgress::whereBelongsTo($this->student)->whereBelongsTo($material)->sole();
        $this->assertNotNull($progress->last_viewed_at);
        $this->assertFalse($progress->isCompleted());

        $component->callAction('toggleDone');
        $this->assertTrue($material->isCompletedBy($this->student));

        Livewire::test(ListCourses::class)
            ->assertTableColumnStateSet('progress', '1 / 1 done', $course);

        $component->callAction('toggleDone');
        $this->assertFalse($material->fresh()->isCompletedBy($this->student));
    }

    public function test_previous_and_next_follow_topic_then_material_order(): void
    {
        $firstTopic = Topic::factory()->create(['sort_order' => 0]);
        $course = $firstTopic->course;
        $secondTopic = Topic::factory()->for($course)->create(['sort_order' => 1]);
        $hiddenTopic = Topic::factory()->for($course)->unpublished()->create(['sort_order' => 2]);
        $first = Material::factory()->for($firstTopic)->create(['sort_order' => 0]);
        $second = Material::factory()->for($firstTopic)->create(['sort_order' => 1]);
        $third = Material::factory()->for($secondTopic)->create(['sort_order' => 0]);
        Material::factory()->for($hiddenTopic)->create();

        $this->assertSame([$first->id, $second->id, $third->id], $course->readingOrder());

        Livewire::test(ReadMaterial::class, ['record' => $course->getRouteKey(), 'materialId' => $first->id])
            ->assertActionHidden('previous')
            ->assertActionHasUrl('next', CourseResource::getUrl('read', ['record' => $course, 'materialId' => $second->id], panel: 'learn'));

        Livewire::test(ReadMaterial::class, ['record' => $course->getRouteKey(), 'materialId' => $third->id])
            ->assertActionVisible('previous')
            ->assertActionHidden('next');
    }

    public function test_notes_are_saved_with_a_page_and_stay_private(): void
    {
        $material = Material::factory()->create();
        $someoneElsesNote = Note::factory()->for($material)->create(['body' => 'Not mine']);

        Livewire::test(ReadMaterial::class, ['record' => $material->topic->course->getRouteKey(), 'materialId' => $material->id])
            ->callAction('addNote', data: ['page' => 3, 'body' => 'Remember the bias-variance trade-off'])
            ->assertHasNoActionErrors()
            ->assertCanNotSeeTableRecords([$someoneElsesNote]);

        $note = Note::whereBelongsTo($this->student)->sole();
        $this->assertSame(3, $note->page);

        Livewire::test(ManageNotes::class)
            ->assertCanSeeTableRecords([$note])
            ->assertCanNotSeeTableRecords([$someoneElsesNote]);

        $this->get(CourseResource::readUrl($material, 3))
            ->assertOk()
            ->assertSee('#page=3', escape: false);
    }

    public function test_search_finds_the_page_that_mentions_a_term_in_published_readings_only(): void
    {
        $visible = Material::factory()->create(['title' => 'Gradient Descent Notes']);
        $hidden = Material::factory()->for(Topic::factory()->unpublished())->create(['title' => 'Gradient Secrets']);

        $this->assertSame(1, $visible->pages()->count(), 'Processing should index the PDF text.');

        Livewire::test(SearchReadings::class)
            ->assertCountTableRecords(0)
            ->searchTable('gradient')
            ->assertCanSeeTableRecords($visible->pages)
            ->assertCanNotSeeTableRecords($hidden->pages);

        $this->get(SearchReadings::getUrl(panel: 'learn'))->assertOk();
    }

    public function test_dashboard_shows_progress_and_recent_readings(): void
    {
        $material = Material::factory()->create(['title' => 'Bayes Theorem']);
        ReadingProgress::factory()->for($this->student)->for($material)->completed()->create();

        $otherMaterial = Material::factory()->create();
        ReadingProgress::factory()->for($otherMaterial)->create();

        $this->get(route('filament.learn.pages.dashboard'))->assertOk();

        Livewire::test(ContinueReviewing::class)
            ->assertCanSeeTableRecords($this->student->readingProgress)
            ->assertCountTableRecords(1)
            ->assertSee('Bayes Theorem');

        Livewire::test(ReviewStats::class)
            ->assertSee('PDFs reviewed')
            ->assertSee('1 / 2');
    }
}
