<x-filament-widgets::widget>
    <section class="flex flex-wrap items-center justify-between gap-6 rounded-3xl bg-primary-50 p-6 sm:p-9 dark:bg-primary-400/10">
        <div class="flex min-w-0 flex-[1_1_22rem] flex-col gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl dark:text-white">
                Hi {{ $firstName }}, ready to review?
            </h1>
            <p class="text-base text-gray-600 dark:text-gray-300">
                @if ($totalPdfs === 0)
                    No readings have been posted yet. Check back soon.
                @else
                    You've finished {{ $completedPdfs }} of {{ $totalPdfs }} readings. Pick up where you left off.
                @endif
            </p>

            <form method="GET" action="{{ $searchUrl }}" role="search" class="mt-2 max-w-lg">
                <label for="welcome-search" class="sr-only">Search readings</label>
                <div class="flex h-13 items-center gap-3 rounded-2xl bg-white px-4 ring-1 ring-primary-200 focus-within:ring-2 focus-within:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="size-5 text-gray-500" />
                    <input
                        id="welcome-search"
                        type="search"
                        name="q"
                        placeholder="Search a term across all PDFs"
                        class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base text-gray-950 placeholder:text-gray-500 focus:ring-0 dark:text-white"
                    >
                </div>
            </form>
        </div>

        <dl class="flex flex-wrap gap-4">
            <div class="flex min-w-32 flex-col-reverse rounded-2xl bg-white px-6 py-5 dark:bg-gray-900">
                <dt class="text-sm text-gray-600 dark:text-gray-400">PDFs done</dt>
                <dd class="text-3xl font-bold text-primary-700 dark:text-primary-400">{{ $completedPdfs }}</dd>
            </div>
            <div class="flex min-w-32 flex-col-reverse rounded-2xl bg-white px-6 py-5 dark:bg-gray-900">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Courses started</dt>
                <dd class="text-3xl font-bold text-primary-700 dark:text-primary-400">{{ $coursesStarted }}</dd>
            </div>
            <div class="flex min-w-32 flex-col-reverse rounded-2xl bg-white px-6 py-5 dark:bg-gray-900">
                <dt class="text-sm text-gray-600 dark:text-gray-400">Notes</dt>
                <dd class="text-3xl font-bold text-primary-700 dark:text-primary-400">{{ $notes }}</dd>
            </div>
        </dl>
    </section>
</x-filament-widgets::widget>
