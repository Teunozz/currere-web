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
        { label: 'Distance', value: `${formatDistance(run.distance_km)} km` },
        { label: 'Duration', value: formatDuration(run.duration_seconds) },
        { label: 'Avg Pace', value: formatPace(run.avg_pace_seconds_per_km) },
        { label: 'Steps', value: run.steps?.toLocaleString() ?? '-' },
        { label: 'Avg Heart Rate', value: run.avg_heart_rate ? `${run.avg_heart_rate} bpm` : '-' },
    ]);
</script>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    {#each stats as stat (stat.label)}
        <div class="rounded-xl border border-border bg-card p-4">
            <p class="text-xs font-medium text-muted-foreground">{stat.label}</p>
            <p class="mt-1 text-xl font-bold tabular-nums">{stat.value}</p>
        </div>
    {/each}
</div>
