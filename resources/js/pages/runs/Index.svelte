<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import PaceTrendChart from '@/components/runs/PaceTrendChart.svelte';
    import RunHistory from '@/components/runs/RunHistory.svelte';
    import RunRecordsAndAverages from '@/components/runs/RunRecordsAndAverages.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { dashboard } from '@/routes';
    import type { BreadcrumbItem } from '@/types';

    type Run = {
        id: number;
        start_time: string;
        distance_km: number;
        duration_seconds: number;
        avg_pace_seconds_per_km: number | null;
        avg_heart_rate: number | null;
    };

    type PaginatedRuns = {
        data: Run[];
        links: { first: string | null; last: string | null; prev: string | null; next: string | null };
        meta: { current_page: number; last_page: number; per_page: number; total: number; links: Array<{ url: string | null; label: string; active: boolean }> };
    };

    type Filters = {
        date_from?: string | null;
        date_to?: string | null;
        sort: string;
        direction: string;
    };

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

    type PaceTrendPoint = {
        date: string;
        pace: number;
        bucket_km: number;
    };

    let { runs, filters, records, averages, totals, paceTrend }: { runs: PaginatedRuns; filters: Filters; records: Records; averages: Averages; totals: Totals; paceTrend: PaceTrendPoint[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Run Diary',
            href: dashboard().url,
        },
    ];
</script>

<AppHead title="Run Diary" />

<AppLayout {breadcrumbs}>
    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Run Diary</h1>
        </div>

        <RunRecordsAndAverages {records} {averages} {totals} />

        <PaceTrendChart data={paceTrend} />

        <RunHistory runs={runs.data} {filters} {records} meta={runs.meta} />
    </div>
</AppLayout>
