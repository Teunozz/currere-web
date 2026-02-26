<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    type Filters = {
        date_from?: string | null;
        date_to?: string | null;
        sort: string;
        direction: string;
    };

    let { filters }: { filters: Filters } = $props();

    let dateFrom = $state(filters.date_from ?? '');
    let dateTo = $state(filters.date_to ?? '');

    function applyFilters() {
        router.get('/dashboard', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            sort: filters.sort,
            direction: filters.direction,
        }, { preserveState: true, preserveScroll: true });
    }

    function clearFilters() {
        dateFrom = '';
        dateTo = '';
        router.get('/dashboard', {
            sort: filters.sort,
            direction: filters.direction,
        }, { preserveState: true, preserveScroll: true });
    }
</script>

<div class="flex flex-wrap items-end gap-4">
    <div class="grid gap-1.5">
        <Label for="date-from">From</Label>
        <Input id="date-from" type="date" bind:value={dateFrom} class="w-40" />
    </div>
    <div class="grid gap-1.5">
        <Label for="date-to">To</Label>
        <Input id="date-to" type="date" bind:value={dateTo} class="w-40" />
    </div>
    <Button onclick={applyFilters} variant="default" size="sm">Filter</Button>
    {#if dateFrom || dateTo}
        <Button onclick={clearFilters} variant="ghost" size="sm">Clear</Button>
    {/if}
</div>
