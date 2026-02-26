<script lang="ts">
    import { formatDuration, formatPace } from '@/lib/formatters';

    type PaceSplit = {
        kilometer_number: number;
        split_time_seconds: number;
        pace_seconds_per_km: number;
        is_partial: boolean;
        partial_distance_km: number | null;
    };

    let { splits }: { splits: PaceSplit[] } = $props();

    const avgPace = $derived(
        splits.length > 0
            ? splits.reduce((sum, s) => sum + s.pace_seconds_per_km, 0) / splits.length
            : 0
    );

    function paceBarWidth(pace: number): number {
        if (avgPace === 0) return 50;
        const ratio = pace / avgPace;
        return Math.min(Math.max(ratio * 50, 10), 100);
    }

    function paceBarColor(pace: number): string {
        if (pace <= avgPace) return 'var(--split-fast)';
        return 'var(--split-slow)';
    }

    function cumulativeTime(index: number): number {
        return splits.slice(0, index + 1).reduce((sum, s) => sum + s.split_time_seconds, 0);
    }
</script>

{#if splits.length === 0}
    <div class="flex h-32 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground">
        No pace split data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card">
        <h3 class="px-4 pt-4 text-sm font-medium text-muted-foreground">Pace Splits</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th class="px-4 py-2 text-left font-medium text-muted-foreground">KM</th>
                        <th class="px-4 py-2 text-left font-medium text-muted-foreground">Split Pace</th>
                        <th class="px-4 py-2 text-left font-medium text-muted-foreground"></th>
                        <th class="px-4 py-2 text-right font-medium text-muted-foreground">Cumulative</th>
                    </tr>
                </thead>
                <tbody>
                    {#each splits as split, i (split.kilometer_number)}
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-2 tabular-nums">
                                {split.kilometer_number}
                                {#if split.is_partial && split.partial_distance_km}
                                    <span class="text-xs text-muted-foreground">({split.partial_distance_km} km)</span>
                                {/if}
                            </td>
                            <td class="px-4 py-2 tabular-nums">{formatPace(split.pace_seconds_per_km)}</td>
                            <td class="px-4 py-2">
                                <div
                                    class="h-3 rounded-full"
                                    style="width: {paceBarWidth(split.pace_seconds_per_km)}%; background-color: {paceBarColor(split.pace_seconds_per_km)};"
                                ></div>
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums text-muted-foreground">
                                {formatDuration(cumulativeTime(i))}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </div>
{/if}
