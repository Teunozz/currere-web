<script lang="ts">
    import { formatDistance, formatDuration, formatPace } from '@/lib/formatters';

    type Run = {
        distance_km: number;
        duration_seconds: number;
        avg_pace_seconds_per_km: number | null;
        steps: number | null;
        avg_heart_rate: number | null;
    };

    let { run }: { run: Run } = $props();

    const stats = $derived([
        {
            label: 'Distance',
            value: formatDistance(run.distance_km),
            unit: 'km',
            icon: 'straighten',
            colorVar: 'var(--chart-1)',
        },
        {
            label: 'Duration',
            value: formatDuration(run.duration_seconds),
            unit: null,
            icon: 'timer',
            colorVar: 'var(--chart-2)',
        },
        {
            label: 'Avg Pace',
            value: formatPace(run.avg_pace_seconds_per_km).replace('/km', ''),
            unit: run.avg_pace_seconds_per_km ? '/km' : null,
            icon: 'speed',
            colorVar: 'var(--chart-4)',
        },
        {
            label: 'Heart Rate',
            value: run.avg_heart_rate ? `${run.avg_heart_rate}` : '-',
            unit: run.avg_heart_rate ? 'bpm' : null,
            icon: 'favorite_border',
            colorVar: 'var(--chart-heart-rate)',
        },
        {
            label: 'Steps',
            value: run.steps?.toLocaleString() ?? '-',
            unit: null,
            icon: 'directions_walk',
            colorVar: 'var(--chart-4)',
        },
    ]);
</script>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    {#each stats as stat (stat.label)}
        <div class="rounded-xl border border-border bg-card p-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: color-mix(in srgb, {stat.colorVar} 15%, transparent);"
                >
                    <span
                        class="material-symbols-outlined text-xl"
                        style="color: {stat.colorVar};"
                    >
                        {stat.icon}
                    </span>
                </div>
                <p class="text-xs font-medium text-muted-foreground">{stat.label}</p>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
                <p class="text-2xl font-bold tabular-nums">{stat.value}</p>
                {#if stat.unit}
                    <p class="text-sm text-muted-foreground">{stat.unit}</p>
                {/if}
            </div>
        </div>
    {/each}
</div>
