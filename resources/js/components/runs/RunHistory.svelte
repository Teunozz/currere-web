<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import ArrowDown from 'lucide-svelte/icons/arrow-down';
    import ArrowUp from 'lucide-svelte/icons/arrow-up';
    import ArrowUpDown from 'lucide-svelte/icons/arrow-up-down';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { formatDate, formatDistance, formatDuration, formatPace } from '@/lib/formatters';

    type Run = {
        id: number;
        start_time: string;
        distance_km: number;
        duration_seconds: number;
        avg_pace_seconds_per_km: number | null;
        avg_heart_rate: number | null;
    };

    type Filters = {
        sort: string;
        direction: string;
        date_from?: string | null;
        date_to?: string | null;
    };

    type RecordEntry = { run_id: number; value: number } | null;

    type Records = {
        longest_distance: RecordEntry;
        fastest_pace: RecordEntry;
    };

    type PaginationLink = { url: string | null; label: string; active: boolean };

    type PaginationMeta = {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };

    let { runs, filters, records, meta }: { runs: Run[]; filters: Filters; records: Records; meta: PaginationMeta } = $props();

    let dateFrom = $state(filters.date_from ?? '');
    let dateTo = $state(filters.date_to ?? '');

    const recordRunIds = $derived(new Set(
        [records.longest_distance, records.fastest_pace]
            .filter((r): r is NonNullable<RecordEntry> => r !== null)
            .map(r => r.run_id)
    ));

    const columns = [
        { key: 'start_time', label: 'Date & Time' },
        { key: 'distance_km', label: 'Distance (km)' },
        { key: 'duration_seconds', label: 'Duration' },
        { key: 'avg_pace_seconds_per_km', label: 'Avg Pace' },
        { key: 'avg_heart_rate', label: 'Avg HR (bpm)' },
    ] as const;

    function sort(column: string) {
        const direction = filters.sort === column && filters.direction === 'desc' ? 'asc' : 'desc';
        router.get('/dashboard', {
            ...filters,
            sort: column,
            direction,
        }, { preserveState: true, preserveScroll: true });
    }

    function viewRun(id: number) {
        router.get(`/runs/${id}`);
    }

    function applyFilters() {
        router.get('/dashboard', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            sort: filters.sort,
            direction: filters.direction,
        }, { preserveState: true, preserveScroll: true });
    }

    function clearFilters() {
        dateFrom = '';
        dateTo = '';
        router.get('/dashboard', {
            sort: filters.sort,
            direction: filters.direction,
        }, { preserveState: true, preserveScroll: true });
    }
</script>

<div class="rounded-xl border border-border bg-card">
    <div class="p-4">
        <div class="mb-4 flex items-center gap-2">
            <span
                class="material-symbols-outlined text-lg"
                style="color: var(--chart-2);"
            >
                history
            </span>
            <h2 class="text-sm font-semibold">Run History</h2>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-1.5">
                <Label for="date-from">From</Label>
                <Input id="date-from" type="date" bind:value={dateFrom} class="w-40" />
            </div>
            <div class="grid gap-1.5">
                <Label for="date-to">To</Label>
                <Input id="date-to" type="date" bind:value={dateTo} class="w-40" />
            </div>
            <Button onclick={applyFilters} variant="default" size="sm">Filter</Button>
            {#if dateFrom || dateTo}
                <Button onclick={clearFilters} variant="ghost" size="sm">Clear</Button>
            {/if}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border bg-muted/50">
                    {#each columns as col (col.key)}
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">
                            <button
                                class="inline-flex items-center gap-1 hover:text-foreground transition-colors"
                                onclick={() => sort(col.key)}
                            >
                                {col.label}
                                {#if filters.sort === col.key}
                                    {#if filters.direction === 'asc'}
                                        <ArrowUp class="h-3.5 w-3.5" />
                                    {:else}
                                        <ArrowDown class="h-3.5 w-3.5" />
                                    {/if}
                                {:else}
                                    <ArrowUpDown class="h-3.5 w-3.5 opacity-40" />
                                {/if}
                            </button>
                        </th>
                    {/each}
                </tr>
            </thead>
            <tbody>
                {#each runs as run (run.id)}
                    <tr
                        class="border-b border-border last:border-0 cursor-pointer transition-colors {recordRunIds.has(run.id) ? 'bg-amber-500/8 hover:bg-amber-500/15' : 'hover:bg-muted/30'}"
                        onclick={() => viewRun(run.id)}
                    >
                        <td class="px-4 py-3">{formatDate(run.start_time)}</td>
                        <td class="px-4 py-3 tabular-nums">
                            {formatDistance(run.distance_km)}
                            {#if records.longest_distance?.run_id === run.id}
                                <Badge class="ml-1.5 bg-amber-500 text-white border-transparent text-[10px] px-1.5 py-0">PR</Badge>
                            {/if}
                        </td>
                        <td class="px-4 py-3 tabular-nums">{formatDuration(run.duration_seconds)}</td>
                        <td class="px-4 py-3 tabular-nums">
                            {formatPace(run.avg_pace_seconds_per_km)}
                            {#if records.fastest_pace?.run_id === run.id}
                                <Badge class="ml-1.5 bg-amber-500 text-white border-transparent text-[10px] px-1.5 py-0">PR</Badge>
                            {/if}
                        </td>
                        <td class="px-4 py-3 tabular-nums">{run.avg_heart_rate ?? '-'}</td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                            No runs yet. Connect your Android app to start syncing!
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>

    {#if meta.last_page > 1}
        <nav class="flex items-center justify-center gap-1 border-t border-border px-4 py-3">
            <!-- eslint-disable svelte/no-at-html-tags -- pagination labels from trusted Laravel backend contain HTML entities -->
            {#each meta.links as link, i (i)}
                {#if link.url}
                    <Link
                        href={link.url}
                        class="rounded-lg px-3 py-1.5 text-sm transition-colors {link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}"
                        preserveScroll
                    >
                        {@html link.label}
                    </Link>
                {:else}
                    <span class="px-3 py-1.5 text-sm text-muted-foreground">
                        {@html link.label}
                    </span>
                {/if}
            {/each}
            <!-- eslint-enable svelte/no-at-html-tags -->
        </nav>
    {/if}
</div>
