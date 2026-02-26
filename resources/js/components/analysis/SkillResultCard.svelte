<script lang="ts">
    import { formatDistance, formatDuration, formatPace } from '@/lib/formatters';

    let { data }: { skill: string; data: Record<string, any> } = $props();

    function isStatValue(key: string, value: any): boolean {
        return typeof value === 'number' || typeof value === 'string';
    }

    function formatStatLabel(key: string): string {
        return key
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatStatValue(key: string, value: any): string {
        if (typeof value !== 'number') return String(value);
        if (key.includes('distance_km')) return `${formatDistance(value)} km`;
        if (key.includes('time_seconds') || key.includes('total_time')) return formatDuration(value);
        if (key.includes('pace_seconds')) return formatPace(value);
        if (key.includes('percent')) return `${value > 0 ? '+' : ''}${value.toFixed(1)}%`;
        if (key.includes('heart_rate') || key.includes('max_hr') || key.includes('bpm')) return `${value} bpm`;
        return String(value);
    }

    const summaryKey = $derived(
        Object.keys(data).find((k) =>
            k.includes('summary_text') || k.includes('analysis_text') || k.includes('explanation_text') || k.includes('recommendation_text')
        )
    );

    const summaryText = $derived(summaryKey ? data[summaryKey] : null);

    const statEntries = $derived(
        Object.entries(data).filter(
            ([key, value]) =>
                isStatValue(key, value) &&
                key !== summaryKey &&
                !key.includes('text')
        )
    );

    const arrayEntries = $derived(
        Object.entries(data).filter(([_, value]) => Array.isArray(value))
    );
</script>

<div class="rounded-xl border border-border bg-card">
    <div class="border-b border-border px-4 py-3">
        <h3 class="text-sm font-semibold">Analysis Results</h3>
    </div>

    <div class="p-4 space-y-4">
        {#if statEntries.length > 0}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {#each statEntries as [key, value] (key)}
                    <div class="rounded-lg bg-muted/50 p-3">
                        <p class="text-xs text-muted-foreground">{formatStatLabel(key)}</p>
                        <p class="mt-0.5 text-sm font-bold tabular-nums">{formatStatValue(key, value)}</p>
                    </div>
                {/each}
            </div>
        {/if}

        {#each arrayEntries as [key, items] (key)}
            {#if items.length > 0}
                <div>
                    <h4 class="mb-2 text-xs font-medium text-muted-foreground">{formatStatLabel(key)}</h4>
                    <div class="space-y-1.5">
                        {#each items as item, idx (idx)}
                            <div class="rounded-lg bg-muted/30 px-3 py-2 text-xs">
                                {#if typeof item === 'object'}
                                    {#each Object.entries(item) as [k, v] (k)}
                                        <span class="mr-3">
                                            <span class="text-muted-foreground">{formatStatLabel(k)}:</span>
                                            <span class="font-medium">{typeof v === 'number' ? formatStatValue(k, v) : v}</span>
                                        </span>
                                    {/each}
                                {:else}
                                    {item}
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
            {/if}
        {/each}

        {#if summaryText}
            <div class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3">
                <p class="text-sm leading-relaxed">{summaryText}</p>
            </div>
        {/if}
    </div>
</div>
