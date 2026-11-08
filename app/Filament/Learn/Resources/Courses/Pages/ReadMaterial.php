<?php

namespace App\Filament\Learn\Resources\Courses\Pages;

use App\Filament\Learn\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\Material;
use App\Models\Note;
use App\Models\ReadingProgress;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * In-browser PDF reader for one lesson: records progress, mark-as-done, page-pinned notes and prev/next navigation.
 */
class ReadMaterial extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = CourseResource::class;

    protected string $view = 'filament.learn.pages.read-material';

    public Material $material;

    /**
     * Page to open the PDF at (from a note or a search result).
     */
    #[Url]
    public ?int $page = null;

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

        ReadingProgress::updateOrCreate(
            ['user_id' => $this->user()->getKey(), 'material_id' => $this->material->getKey()],
            ['last_viewed_at' => now()],
        );
    }

    public function getTitle(): string|Htmlable
    {
        return $this->material->title;
    }

    public function getSubheading(): ?string
    {
        $status = $this->material->isCompletedBy($this->user()) ? ' · ✓ Done' : '';

        return "{$this->material->topic->title} · {$this->material->details()}{$status}";
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $course = $this->course();

        return [
            CourseResource::getUrl() => 'Courses',
            CourseResource::getUrl('view', ['record' => $course]) => $course->code,
            $this->material->topic->title,
        ];
    }

    public function pdfUrl(): string
    {
        $fragment = $this->page !== null && $this->page > 0 ? "#page={$this->page}&view=FitH" : '#view=FitH';

        return route('materials.file', $this->material).$fragment;
    }

    protected function getHeaderActions(): array
    {
        $isCompleted = $this->material->isCompletedBy($this->user());

        return [
            Action::make('previous')
                ->label('Previous')
                ->color('gray')
                ->icon(Heroicon::OutlinedChevronLeft)
                ->url(fn (): ?string => $this->neighbourUrl(-1))
                ->visible(fn (): bool => $this->neighbourUrl(-1) !== null),
            Action::make('toggleDone')
                ->label($isCompleted ? 'Mark as not done' : 'Mark as done')
                ->color($isCompleted ? 'gray' : 'success')
                ->icon($isCompleted ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheckCircle)
                ->action(fn () => $this->toggleDone()),
            Action::make('addNote')
                ->label('Add note')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->schema(static::noteFormSchema())
                ->fillForm(fn (): array => ['page' => $this->page])
                ->action(function (array $data): void {
                    $this->user()->notes()->create([...$data, 'material_id' => $this->material->getKey()]);
                    Notification::make()->title('Note saved')->success()->send();
                }),
            Action::make('next')
                ->label('Next')
                ->icon(Heroicon::OutlinedChevronRight)
                ->iconPosition('after')
                ->url(fn (): ?string => $this->neighbourUrl(1))
                ->visible(fn (): bool => $this->neighbourUrl(1) !== null),
            ActionGroup::make([
                Action::make('download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(route('materials.file', ['material' => $this->material, 'download' => 1])),
                Action::make('openInNewTab')
                    ->label('Open full screen')
                    ->icon(Heroicon::OutlinedArrowsPointingOut)
                    ->url($this->pdfUrl(), shouldOpenInNewTab: true),
            ])->color('gray'),
        ];
    }

    /**
     * The student's notes on this PDF, shown under the reader.
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('My notes')
            ->query(fn (): Builder => Note::query()
                ->whereBelongsTo($this->user())
                ->whereBelongsTo($this->material))
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('page is null')->orderBy('page')->orderBy('id'))
            ->paginated(false)
            ->columns([
                TextColumn::make('page')
                    ->label('Page')
                    ->placeholder('—')
                    ->badge()
                    ->url(fn (Note $record): ?string => $record->page !== null
                        ? CourseResource::readUrl($this->material, $record->page)
                        : null),
                TextColumn::make('body')
                    ->label('Note')
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->label('Saved')
                    ->since()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make()->schema(static::noteFormSchema()),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Use "Add note" to jot something down. Add a page number to bookmark it.')
            ->emptyStateIcon(Heroicon::OutlinedPencilSquare);
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    public static function noteFormSchema(): array
    {
        return [
            TextInput::make('page')
                ->label('Page (optional)')
                ->helperText('Add a page to bookmark it; clicking the note opens the PDF there.')
                ->numeric()
                ->minValue(1),
            Textarea::make('body')
                ->label('Note')
                ->required()
                ->rows(4)
                ->maxLength(5000),
        ];
    }

    protected function toggleDone(): void
    {
        $progress = ReadingProgress::firstOrNew([
            'user_id' => $this->user()->getKey(),
            'material_id' => $this->material->getKey(),
        ]);

        $progress->forceFill([
            'completed_at' => $progress->isCompleted() ? null : now(),
            'last_viewed_at' => now(),
        ])->save();

        $this->material->unsetRelation('progress');

        Notification::make()
            ->title($progress->isCompleted() ? 'Marked as done' : 'Marked as not done')
            ->success()
            ->send();

        $next = $this->neighbourUrl(1);

        if ($progress->isCompleted() && $next !== null) {
            Notification::make()
                ->title('Up next')
                ->actions([Action::make('openNext')->label('Open next PDF')->url($next)])
                ->send();
        }
    }

    protected function neighbourUrl(int $offset): ?string
    {
        $order = once(fn (): array => $this->course()->readingOrder());
        $position = array_search($this->material->getKey(), $order, true);
        $neighbourId = $position === false ? null : ($order[$position + $offset] ?? null);

        if ($neighbourId === null) {
            return null;
        }

        return CourseResource::getUrl('read', ['record' => $this->course(), 'materialId' => $neighbourId], panel: 'learn');
    }

    protected function course(): Course
    {
        /** @var Course $course */
        $course = $this->getRecord();

        return $course;
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
