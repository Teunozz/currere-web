<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ArrowDown from 'lucide-svelte/icons/arrow-down';
    import ArrowUp from 'lucide-svelte/icons/arrow-up';
    import ArrowUpDown from 'lucide-svelte/icons/arrow-up-down';
    import { Badge } from '@/components/ui/badge';
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

    let { runs, filters, records }: { runs: Run[]; filters: Filters; records: Records } = $props();

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
</script>

<div class="overflow-x-auto rounded-xl border border-border">
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
