<?php

namespace Tests\Feature;

use App\Enums\CourseCategory;
use App\Filament\Learn\Resources\Courses\CourseResource as StudentCourseResource;
use App\Filament\Learn\Resources\Courses\Pages\ListCourses as StudentCourseList;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\Program;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_course_and_assigns_a_professor(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $program = Program::factory()->create();
        $professor = User::factory()->professor()->create();

        $this->get(route('filament.admin.resources.programs.index'))->assertOk()->assertSee($program->name);
        $this->get(route('filament.admin.resources.courses.create'))->assertOk();

        Livewire::test(CreateCourse::class)
            ->fillForm([
                'program_id' => $program->id,
                'code' => 'DS 201',
                'title' => 'Statistical Inference',
                'slug' => 'ds-201-statistical-inference',
                'units' => 3,
                'category' => CourseCategory::Core->value,
                'professors' => [$professor->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::where('code', 'DS 201')->sole();

        $this->assertSame($program->id, $course->program_id);
        $this->assertTrue($course->isHandledBy($professor));
    }

    public function test_professors_only_see_and_edit_the_courses_they_handle(): void
    {
        $professor = User::factory()->professor()->create();
        $theirs = Course::factory()->create();
        $theirs->professors()->attach($professor);
        $someoneElses = Course::factory()->create();
        $this->actingAs($professor);

        Livewire::test(ListCourses::class)
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords([$someoneElses]);

        $this->get(route('filament.admin.resources.courses.create'))->assertForbidden();
        $this->get(route('filament.admin.resources.courses.edit', $someoneElses))->assertNotFound();
        $this->get(route('filament.admin.resources.programs.index'))->assertForbidden();
    }

    public function test_professors_can_update_the_description_but_not_the_course_code(): void
    {
        $professor = User::factory()->professor()->create();
        $course = Course::factory()->create(['code' => 'DS 230']);
        $course->professors()->attach($professor);
        $this->actingAs($professor);

        Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertFormFieldDisabled('code')
            ->fillForm(['description' => 'Supervised and unsupervised learning.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $course->refresh();
        $this->assertSame('Supervised and unsupervised learning.', $course->description);
        $this->assertSame('DS 230', $course->code);
    }

    public function test_students_browse_only_published_courses(): void
    {
        $student = User::factory()->create();
        $published = Course::factory()->create();
        $draft = Course::factory()->unpublished()->create();
        $inHiddenProgram = Course::factory()->for(Program::factory()->unpublished())->create();

        $this->actingAs($student);
        $this->get(route('filament.learn.resources.courses.index'))->assertOk()->assertSee($published->title);
        Filament::setCurrentPanel('learn');

        Livewire::test(StudentCourseList::class)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft, $inHiddenProgram]);

        $this->get(StudentCourseResource::getUrl('view', ['record' => $published], panel: 'learn'))->assertOk();
        $this->get(StudentCourseResource::getUrl('view', ['record' => $draft], panel: 'learn'))->assertNotFound();
        $this->get(route('filament.admin.resources.courses.index'))->assertForbidden();
    }
}
