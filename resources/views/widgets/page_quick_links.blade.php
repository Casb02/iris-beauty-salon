<ui-widget title="Page Quick Links" icon="collections">
    <div class="space-y-4 px-4 py-3">
        @if ($collections->isEmpty())
            <div class="rounded border border-gray-200 px-3 py-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300">
                No page collections are available for your role.
            </div>
        @endif

        @foreach ($collections as $collection)
            <section class="overflow-hidden rounded border border-gray-200 dark:border-gray-700">
                <header class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-850">
                    <a
                        href="{{ $collection['listing_url'] }}"
                        class="truncate text-sm font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-300"
                    >
                        {{ $collection['title'] }}
                    </a>

                    @if ($collection['create_url'])
                        <a
                            href="{{ $collection['create_url'] }}"
                            class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-200"
                        >
                            + New
                        </a>
                    @endif
                </header>

                @if ($collection['entries']->isEmpty())
                    <p class="px-3 py-3 text-sm text-gray-500 dark:text-gray-300">
                        No pages yet.
                    </p>
                @else
                    <div class="max-h-44 divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
                        @foreach ($collection['entries'] as $entry)
                            <a
                                href="{{ $entry['edit_url'] }}"
                                class="block truncate px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                {{ $entry['title'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</ui-widget>
