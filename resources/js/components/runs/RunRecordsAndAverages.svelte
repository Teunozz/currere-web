<script lang="ts">
    import { formatDistance, formatDuration, formatPace } from '@/lib/formatters';

    type RecordEntry = { run_id: number; value: number } | null;

    type Records = {
        longest_distance: RecordEntry;
        fastest_pace: RecordEntry;
        highest_heart_rate: RecordEntry;
    };

    type Averages = {
        avg_distance_km: number | null;
        avg_pace_seconds_per_km: number | null;
        avg_heart_rate: number | null;
    };

    type Totals = {
        total_distance_km: number;
        total_duration_seconds: number;
        total_runs: number;
    };

    type Stat = {
        label: string;
        value: string;
        unit: string | null;
    };

    type CategoryCard = {
        title: string;
        icon: string;
        colorVar: string;
        stats: Stat[];
    };

    let { records, averages, totals }: { records: Records; averages: Averages; totals: Totals } = $props();

    const categoryCards: CategoryCard[] = $derived([
        {
            title: 'Distance',
            icon: 'straighten',
            colorVar: 'var(--chart-1)',
            stats: [
                {
                    label: 'Average',
                    value: averages.avg_distance_km !== null ? formatDistance(averages.avg_distance_km) : '-',
                    unit: averages.avg_distance_km !== null ? 'km' : null,
                },
                {
                    label: 'Longest',
                    value: records.longest_distance ? formatDistance(records.longest_distance.value) : '-',
                    unit: records.longest_distance ? 'km' : null,
                },
                {
                    label: 'Total',
                    value: formatDistance(totals.total_distance_km),
                    unit: 'km',
                },
            ],
        },
        {
            title: 'Pace',
            icon: 'speed',
            colorVar: 'var(--chart-4)',
            stats: [
                {
                    label: 'Average',
                    value: averages.avg_pace_seconds_per_km !== null
                        ? formatPace(averages.avg_pace_seconds_per_km).replace('/km', '')
                        : '-',
                    unit: averages.avg_pace_seconds_per_km !== null ? '/km' : null,
                },
                {
                    label: 'Fastest',
                    value: records.fastest_pace
                        ? formatPace(records.fastest_pace.value).replace('/km', '')
                        : '-',
                    unit: records.fastest_pace ? '/km' : null,

                },
            ],
        },
        {
            title: 'Heart Rate',
            icon: 'favorite_border',
            colorVar: 'var(--chart-heart-rate)',
            stats: [
                {
                    label: 'Average',
                    value: averages.avg_heart_rate !== null ? `${averages.avg_heart_rate}` : '-',
                    unit: averages.avg_heart_rate !== null ? 'bpm' : null,
                },
                {
                    label: 'Highest',
                    value: records.highest_heart_rate ? `${records.highest_heart_rate.value}` : '-',
                    unit: records.highest_heart_rate ? 'bpm' : null,
                },
            ],
        },
        {
            title: 'Activity',
            icon: 'directions_run',
            colorVar: 'var(--chart-2)',
            stats: [
                {
                    label: 'Total Runs',
                    value: `${totals.total_runs}`,
                    unit: null,
                },
                {
                    label: 'Total Time',
                    value: totals.total_duration_seconds > 0 ? formatDuration(totals.total_duration_seconds) : '-',
                    unit: null,
                },
            ],
        },
    ]);
</script>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {#each categoryCards as card (card.title)}
        <div
            class="rounded-xl border p-5"
            style="background-color: color-mix(in srgb, {card.colorVar} 8%, var(--color-card)); border-color: color-mix(in srgb, {card.colorVar} 20%, transparent);"
        >
            <div class="mb-4 flex items-center gap-2">
                <span
                    class="material-symbols-outlined text-lg"
                    style="color: {card.colorVar};"
                >
                    {card.icon}
                </span>
                <h2 class="text-sm font-semibold">{card.title}</h2>
            </div>
            <div class="flex flex-col">
                {#each card.stats as stat, i (stat.label)}
                    <div
                        class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0"
                        style={i > 0 ? `border-top: 1px solid color-mix(in srgb, ${card.colorVar} 15%, transparent);` : ''}
                    >
                        <span class="text-xs font-semibold">{stat.label}</span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums"
                            style="background-color: color-mix(in srgb, {card.colorVar} 15%, transparent); color: {card.colorVar};"
                        >
                            {stat.value}{#if stat.unit}&nbsp;{stat.unit}{/if}
                        </span>
                    </div>
                {/each}
            </div>
        </div>
    {/each}
</div>
