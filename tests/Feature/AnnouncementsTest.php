<?php

namespace Tests\Feature;

use App\Filament\Learn\Resources\Courses\CourseResource as StudentCourseResource;
use App\Filament\Learn\Widgets\LatestAnnouncements;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_posts_a_site_wide_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Exam week', 'body' => 'Good luck!', 'course_id' => null])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::sole();
        $this->assertNull($announcement->course_id);
        $this->assertTrue($announcement->author->is($admin));
        $this->assertSame('All students', $announcement->audienceLabel());
    }

    public function test_professors_announce_only_to_their_own_courses(): void
    {
        $professor = User::factory()->professor()->create();
        $theirCourse = Course::factory()->create();
        $theirCourse->professors()->attach($professor);
        $otherCourse = Course::factory()->create();
        $othersAnnouncement = Announcement::factory()->forCourse($otherCourse)->create();
        $this->actingAs($professor);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Site-wide?', 'body' => 'Nope', 'course_id' => null])
            ->call('create')
            ->assertHasFormErrors(['course_id']);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Quiz moved', 'body' => 'Now on Friday.', 'course_id' => $theirCourse->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Announcement::where('title', 'Quiz moved')->sole()->course->is($theirCourse));

        Livewire::test(ListAnnouncements::class)
            ->assertCanNotSeeTableRecords([$othersAnnouncement]);
    }

    public function test_students_see_published_announcements_only(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('learn');
        $siteWide = Announcement::factory()->create(['title' => 'Welcome']);
        $course = Course::factory()->create();
        $courseNotice = Announcement::factory()->forCourse($course)->create(['title' => 'Quiz on Friday']);
        $scheduled = Announcement::factory()->scheduled()->create();
        $draft = Announcement::factory()->create(['published_at' => null]);
        $hiddenCourseNotice = Announcement::factory()->forCourse(Course::factory()->unpublished()->create())->create();

        Livewire::test(LatestAnnouncements::class)
            ->assertCanSeeTableRecords([$siteWide, $courseNotice])
            ->assertCanNotSeeTableRecords([$scheduled, $draft, $hiddenCourseNotice]);

        $this->get(StudentCourseResource::getUrl('view', ['record' => $course], panel: 'learn'))
            ->assertOk()
            ->assertSee('Quiz on Friday');
    }
}
