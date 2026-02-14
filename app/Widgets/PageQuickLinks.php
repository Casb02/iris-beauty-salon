<?php

namespace App\Widgets;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Widgets\Widget;

class PageQuickLinks extends Widget
{
    public function html(): View
    {
        return view('widgets.page_quick_links', [
            'collections' => $this->collections(),
        ]);
    }

    protected function collections(): Collection
    {
        $user = User::current();
        $siteHandle = Site::selected()->handle();

        return CollectionFacade::all()
            ->filter(fn ($collection): bool => $user->can('view', $collection))
            ->map(function ($collection) use ($user, $siteHandle): array {
                $entries = $collection
                    ->queryEntries()
                    ->orderBy($collection->sortField(), $collection->sortDirection())
                    ->get()
                    ->filter(fn ($entry): bool => $entry->locale() === $siteHandle)
                    ->filter(fn ($entry): bool => $user->can('edit', $entry))
                    ->map(function ($entry): array {
                        $entryTitle = $entry->get('title');

                        return [
                            'title' => filled($entryTitle) ? $entryTitle : $entry->slug(),
                            'edit_url' => $entry->editUrl(),
                        ];
                    })
                    ->values();

                $canCreate = $user->can('create', [EntryContract::class, $collection]) && $collection->hasVisibleEntryBlueprint();

                return [
                    'title' => $collection->title(),
                    'listing_url' => $collection->showUrl(),
                    'create_url' => $canCreate ? $collection->createEntryUrl($siteHandle) : null,
                    'entries' => $entries,
                ];
            })
            ->filter(fn (array $collection): bool => $collection['entries']->isNotEmpty() || filled($collection['create_url']))
            ->sortBy('title')
            ->values();
    }
}
