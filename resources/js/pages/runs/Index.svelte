<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import RunFilters from '@/components/runs/RunFilters.svelte';
    import RunTable from '@/components/runs/RunTable.svelte';
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

    let { runs, filters }: { runs: PaginatedRuns; filters: Filters } = $props();

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

        <RunFilters {filters} />

        <RunTable runs={runs.data} {filters} />

        {#if runs.meta.last_page > 1}
            <nav class="flex items-center justify-center gap-1">
                <!-- eslint-disable svelte/no-at-html-tags -- pagination labels from trusted Laravel backend contain HTML entities -->
                {#each runs.meta.links as link, i (i)}
                    {#if link.url}
                        <Link
                            href={link.url}
                            class="rounded-lg px-3 py-1.5 text-sm transition-colors {link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}"
                            preserveState
                            preserveScroll
                        >
                            {@html link.label}
                        </Link>
                    {:else}
                        <span class="px-3 py-1.5 text-sm text-muted-foreground">
                            {@html link.label}
                        </span>
                    {/if}
                {/each}
                <!-- eslint-enable svelte/no-at-html-tags -->
            </nav>
        {/if}
    </div>
</AppLayout>
