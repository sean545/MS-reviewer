<?php

namespace App\Filament\Learn\Resources\Courses\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\Material;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * In-browser PDF reader for one lesson material of a course.
 */
class ReadMaterial extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CourseResource::class;

    protected string $view = 'filament.learn.pages.read-material';

    public Material $material;

    public function mount(int|string $record, int|string $materialId): void
    {
        $this->record = $this->resolveRecord($record);

        $this->material = Material::query()
            ->with('topic')
            ->whereKey($materialId)
            ->whereHas('topic', fn (Builder $topic) => $topic
                ->where('course_id', $this->record->getKey())
                ->where('is_published', true))
            ->firstOrFail();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->material->title;
    }

    public function getSubheading(): ?string
    {
        return "{$this->material->topic->title} · {$this->material->details()}";
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        /** @var Course $course */
        $course = $this->getRecord();

        return [
            CourseResource::getUrl() => 'Courses',
            CourseResource::getUrl('view', ['record' => $course]) => $course->code,
            $this->material->topic->title,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(route('materials.file', ['material' => $this->material, 'download' => 1])),
            Action::make('openInNewTab')
                ->label('Full screen')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowsPointingOut)
                ->url(route('materials.file', $this->material), shouldOpenInNewTab: true),
        ];
    }
}
