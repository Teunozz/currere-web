<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import HeartRateChart from '@/components/runs/HeartRateChart.svelte';
    import PaceChart from '@/components/runs/PaceChart.svelte';
    import PaceSplitsTable from '@/components/runs/PaceSplitsTable.svelte';
    import RunStats from '@/components/runs/RunStats.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import { formatDate, formatTimeRange } from '@/lib/formatters';
    import { dashboard } from '@/routes';
    import type { BreadcrumbItem } from '@/types';

    type HeartRateSample = {
        timestamp: string;
        bpm: number;
    };

    type PaceSplit = {
        kilometer_number: number;
        split_time_seconds: number;
        pace_seconds_per_km: number;
        is_partial: boolean;
        partial_distance_km: number | null;
    };

    type RunDetail = {
        data: {
            id: number;
            start_time: string;
            end_time: string;
            distance_km: number;
            duration_seconds: number;
            steps: number | null;
            avg_heart_rate: number | null;
            avg_pace_seconds_per_km: number | null;
            heart_rate_samples: HeartRateSample[];
            pace_splits: PaceSplit[];
        };
    };

    let { run }: { run: RunDetail } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Run Diary', href: dashboard().url },
        { title: formatDate(run.data.start_time) },
    ];

    let deleting = $state(false);

    function deleteRun() {
        deleting = true;
        router.delete(`/runs/${run.data.id}`, {
            onFinish: () => { deleting = false; },
        });
    }
</script>

<AppHead title="Run Detail" />

<AppLayout {breadcrumbs}>
    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" onclick={() => router.get('/dashboard')}>
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <h1 class="text-2xl font-bold">{formatDate(run.data.start_time)}</h1>
            </div>

            <Dialog>
                <DialogTrigger>
                    {#snippet children(props)}
                        <Button {...props} variant="destructive" size="sm">
                            <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                            Delete
                        </Button>
                    {/snippet}
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Delete Run</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this run? This action cannot be undone.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose>
                            {#snippet children(props)}
                                <Button {...props} variant="outline">Cancel</Button>
                            {/snippet}
                        </DialogClose>
                        <Button variant="destructive" onclick={deleteRun} disabled={deleting}>
                            {deleting ? 'Deleting...' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <span class="material-symbols-outlined text-lg" style="color: var(--primary);">
                directions_run
            </span>
            <span>Outdoor Run</span>
            <span class="text-border">&middot;</span>
            <span>{formatTimeRange(run.data.start_time, run.data.end_time)}</span>
        </div>

        <div class="h-px bg-primary/20"></div>

        <RunStats run={run.data} />

        <h2 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Performance Charts
        </h2>
        <div class="grid gap-6 lg:grid-cols-2">
            <HeartRateChart samples={run.data.heart_rate_samples} startTime={run.data.start_time} />
            <PaceChart splits={run.data.pace_splits} />
        </div>

        <h2 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Kilometer Splits
        </h2>
        <PaceSplitsTable splits={run.data.pace_splits} />
    </div>
</AppLayout>
