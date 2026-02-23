<script lang="ts">
    import { formatDistance, formatPace } from '@/lib/formatters';

    type RecordEntry = { run_id: number; value: number } | null;

    type Records = {
        longest_distance: RecordEntry;
        fastest_pace: RecordEntry;
    };

    type Averages = {
        avg_distance_km: number | null;
        avg_pace_seconds_per_km: number | null;
        avg_heart_rate: number | null;
    };

    let { records, averages }: { records: Records; averages: Averages } = $props();

    const recordCards = $derived([
        {
            label: 'Longest Distance',
            value: records.longest_distance ? formatDistance(records.longest_distance.value) : '-',
            unit: records.longest_distance ? 'km' : null,
            icon: 'straighten',
            colorVar: 'var(--chart-1)',
        },
        {
            label: 'Fastest Pace',
            value: records.fastest_pace
                ? formatPace(records.fastest_pace.value).replace('/km', '')
                : '-',
            unit: records.fastest_pace ? '/km' : null,
            icon: 'speed',
            colorVar: 'var(--chart-4)',
        },
    ]);

    const averageCards = $derived([
        {
            label: 'Avg Distance',
            value: averages.avg_distance_km !== null ? formatDistance(averages.avg_distance_km) : '-',
            unit: averages.avg_distance_km !== null ? 'km' : null,
            icon: 'straighten',
            colorVar: 'var(--chart-1)',
        },
        {
            label: 'Avg Pace',
            value: averages.avg_pace_seconds_per_km !== null
                ? formatPace(averages.avg_pace_seconds_per_km).replace('/km', '')
                : '-',
            unit: averages.avg_pace_seconds_per_km !== null ? '/km' : null,
            icon: 'speed',
            colorVar: 'var(--chart-4)',
        },
        {
            label: 'Avg Heart Rate',
            value: averages.avg_heart_rate !== null ? `${averages.avg_heart_rate}` : '-',
            unit: averages.avg_heart_rate !== null ? 'bpm' : null,
            icon: 'favorite_border',
            colorVar: 'var(--chart-heart-rate)',
        },
    ]);
</script>

<div class="flex flex-col gap-4">
    <div>
        <h2 class="mb-3 text-sm font-medium text-muted-foreground">Personal Records</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {#each recordCards as card (card.label)}
                <div class="rounded-xl border border-amber-500/30 bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            style="background-color: color-mix(in srgb, {card.colorVar} 15%, transparent);"
                        >
                            <span
                                class="material-symbols-outlined text-xl"
                                style="color: {card.colorVar};"
                            >
                                {card.icon}
                            </span>
                        </div>
                        <p class="text-xs font-medium text-muted-foreground">{card.label}</p>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1">
                        <p class="text-2xl font-bold tabular-nums">{card.value}</p>
                        {#if card.unit}
                            <p class="text-sm text-muted-foreground">{card.unit}</p>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <div>
        <h2 class="mb-3 text-sm font-medium text-muted-foreground">All-time Averages</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {#each averageCards as card (card.label)}
                <div class="rounded-xl border border-border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            style="background-color: color-mix(in srgb, {card.colorVar} 15%, transparent);"
                        >
                            <span
                                class="material-symbols-outlined text-xl"
                                style="color: {card.colorVar};"
                            >
                                {card.icon}
                            </span>
                        </div>
                        <p class="text-xs font-medium text-muted-foreground">{card.label}</p>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1">
                        <p class="text-2xl font-bold tabular-nums">{card.value}</p>
                        {#if card.unit}
                            <p class="text-sm text-muted-foreground">{card.unit}</p>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </div>
</div>
