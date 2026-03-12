<script lang="ts">
    import { formatDuration, formatPace } from '@/lib/formatters';
    import SectionCard from './SectionCard.svelte';

    type PaceSplit = {
        kilometer_number: number;
        split_time_seconds: number;
        pace_seconds_per_km: number;
        is_partial: boolean;
        partial_distance_km: number | null;
    };

    let { splits }: { splits: PaceSplit[] } = $props();

    const minPace = $derived(
        splits.length > 0 ? Math.min(...splits.map((s) => s.pace_seconds_per_km)) : 0
    );

    const maxPace = $derived(
        splits.length > 0 ? Math.max(...splits.map((s) => s.pace_seconds_per_km)) : 0
    );

    function normalizedPace(pace: number): number {
        const range = maxPace - minPace;
        if (range === 0) return 0.5;
        return Math.min(Math.max((pace - minPace) / range, 0), 1);
    }

    function paceBarWidth(pace: number): number {
        return (0.3 + 0.7 * normalizedPace(pace)) * 100;
    }

    function paceBarColor(pace: number): string {
        const t = normalizedPace(pace);

        const h = 122 - t * 122;
        const s = 40 + t * 35;
        const l = 45 + t * 10;

        return `hsl(${h}, ${s}%, ${l}%)`;
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
    <SectionCard title="Pace Splits" icon="speed" colorVar="var(--chart-pace)" plain>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th class="px-4 py-2 text-left font-medium text-muted-foreground">KM</th>
                        <th class="px-4 py-2 text-left font-medium text-muted-foreground">Split Pace</th>
                        <th class="w-1/3 px-4 py-2 text-left font-medium text-muted-foreground"></th>
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
    </SectionCard>
{/if}
