<script lang="ts">
    import { formatDistance, formatPace } from '@/lib/formatters';
    import StatCard from './StatCard.svelte';

    type HeartRateSample = {
        timestamp: string;
        bpm: number;
    };

    type PaceSplit = {
        kilometer_number: number;
        pace_seconds_per_km: number;
        is_partial: boolean;
    };

    type Run = {
        distance_km: number;
        avg_pace_seconds_per_km: number | null;
        steps: number | null;
        avg_heart_rate: number | null;
        heart_rate_samples: HeartRateSample[];
        pace_splits: PaceSplit[];
    };

    let { run }: { run: Run } = $props();

    const fastestSplit = $derived.by(() => {
        const fullSplits = run.pace_splits.filter((s) => !s.is_partial);
        if (fullSplits.length === 0) return null;
        return Math.min(...fullSplits.map((s) => s.pace_seconds_per_km));
    });

    const minHR = $derived(
        run.heart_rate_samples.length > 0
            ? Math.min(...run.heart_rate_samples.map((s) => s.bpm))
            : null,
    );

    const maxHR = $derived(
        run.heart_rate_samples.length > 0
            ? Math.max(...run.heart_rate_samples.map((s) => s.bpm))
            : null,
    );

    type CategoryCard = {
        title: string;
        icon: string;
        colorVar: string;
        stats: { label: string; value: string; unit: string | null }[];
    };

    const categoryCards: CategoryCard[] = $derived([
        {
            title: 'Distance',
            icon: 'straighten',
            colorVar: 'var(--chart-1)',
            stats: [
                {
                    label: 'Distance',
                    value: formatDistance(run.distance_km),
                    unit: 'km',
                },
                ...(run.steps
                    ? [{ label: 'Steps', value: run.steps.toLocaleString(), unit: null }]
                    : []),
            ],
        },
        {
            title: 'Pace',
            icon: 'speed',
            colorVar: 'var(--chart-4)',
            stats: [
                {
                    label: 'Average',
                    value: run.avg_pace_seconds_per_km
                        ? formatPace(run.avg_pace_seconds_per_km).replace('/km', '')
                        : '-',
                    unit: run.avg_pace_seconds_per_km ? '/km' : null,
                },
                ...(fastestSplit
                    ? [{
                        label: 'Fastest Split',
                        value: formatPace(fastestSplit).replace('/km', ''),
                        unit: '/km' as string | null,
                    }]
                    : []),
            ],
        },
        {
            title: 'Heart Rate',
            icon: 'favorite_border',
            colorVar: 'var(--chart-heart-rate)',
            stats: [
                {
                    label: 'Average',
                    value: run.avg_heart_rate ? `${run.avg_heart_rate}` : '-',
                    unit: run.avg_heart_rate ? 'bpm' : null,
                },
                ...(minHR !== null && maxHR !== null
                    ? [
                        { label: 'Minimum', value: `${minHR}`, unit: 'bpm' as string | null },
                        { label: 'Maximum', value: `${maxHR}`, unit: 'bpm' as string | null },
                    ]
                    : []),
            ],
        },
    ]);
</script>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    {#each categoryCards as card (card.title)}
        <StatCard title={card.title} icon={card.icon} colorVar={card.colorVar} stats={card.stats} />
    {/each}
</div>
