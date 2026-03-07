<script lang="ts">
    import { Axis, Chart, Highlight, Points, Spline, Svg, Tooltip } from 'layerchart';
    import { scaleTime } from 'd3-scale';
    import { formatPace, formatShortDate } from '@/lib/formatters';

    type TrendPoint = {
        date: string;
        pace: number;
        bucket_km: number;
    };

    let { data }: { data: TrendPoint[] } = $props();

    type ChartPoint = { date: Date; pace: number; bucket_km: number };
    type Range = '3M' | '6M' | '1Y' | 'All';

    const rangeOptions: Range[] = ['3M', '6M', '1Y', 'All'];
    let selectedRange = $state<Range>('1Y');

    const cutoffDate = $derived.by(() => {
        if (selectedRange === 'All') return null;
        const now = new Date();
        const months = selectedRange === '3M' ? 3 : selectedRange === '6M' ? 6 : 12;
        return new Date(now.getFullYear(), now.getMonth() - months, now.getDate());
    });

    const allPoints = $derived<ChartPoint[]>(
        data.map((d) => ({ date: new Date(d.date), pace: d.pace, bucket_km: d.bucket_km })),
    );

    const filteredPoints = $derived(
        cutoffDate ? allPoints.filter((p) => p.date >= cutoffDate) : allPoints,
    );

    const buckets = $derived(
        [...new Set(filteredPoints.map((d) => d.bucket_km))].sort((a, b) => a - b),
    );

    let enabledBuckets = $state<Set<number>>(new Set());

    $effect(() => {
        const counts = new Map<number, number>();
        for (const km of buckets) {
            counts.set(km, filteredPoints.filter((d) => d.bucket_km === km).length);
        }
        const top3 = [...counts.entries()]
            .sort((a, b) => b[1] - a[1])
            .slice(0, 3)
            .map(([km]) => km);
        enabledBuckets = new Set(top3);
    });

    function toggleBucket(km: number): void {
        if (enabledBuckets.has(km) && enabledBuckets.size === 1) {
            return;
        }
        const next = new Set(enabledBuckets);
        if (next.has(km)) {
            next.delete(km);
        } else {
            next.add(km);
        }
        enabledBuckets = next;
    }

    function bucketColor(km: number): string {
        const index = buckets.indexOf(km);
        const varIndex = (index % 8) + 1;
        return `var(--chart-bucket-${varIndex})`;
    }

    const visiblePoints = $derived(
        filteredPoints.filter((p) => enabledBuckets.has(p.bucket_km)),
    );

    const seriesData = $derived(
        buckets
            .filter((km) => enabledBuckets.has(km))
            .map((km) => ({
                km,
                points: filteredPoints
                    .filter((p) => p.bucket_km === km)
                    .sort((a, b) => a.date.getTime() - b.date.getTime()),
            })),
    );

    const paces = $derived(visiblePoints.map((p) => p.pace));
    const minPace = $derived(Math.min(...paces));
    const maxPace = $derived(Math.max(...paces));
    const yDomain = $derived.by(() => {
        const range = maxPace - minPace;
        const padding = Math.max(range * 0.5, 15);
        return [maxPace + padding, minPace - padding];
    });
</script>

{#if data.length === 0}
    <div
        class="flex h-64 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground"
    >
        No pace trend data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card p-4">
        <div class="mb-4 flex items-center gap-2">
            <span
                class="material-symbols-outlined text-lg"
                style="color: var(--chart-4);"
            >
                trending_up
            </span>
            <h2 class="text-sm font-semibold">Pace Trend</h2>
            <div class="ml-auto flex gap-1">
                {#each rangeOptions as range}
                    <button
                        type="button"
                        class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors {selectedRange === range ? 'border-primary bg-primary text-primary-foreground' : 'border-border text-muted-foreground hover:border-primary/50 hover:text-foreground'}"
                        onclick={() => (selectedRange = range)}
                    >
                        {range}
                    </button>
                {/each}
            </div>
        </div>

        <div class="mb-3 flex flex-wrap gap-1.5">
            {#each buckets as km}
                <button
                    type="button"
                    class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors"
                    class:opacity-40={!enabledBuckets.has(km)}
                    style="border-color: {bucketColor(km)}; color: {enabledBuckets.has(km) ? bucketColor(km) : 'inherit'};"
                    onclick={() => toggleBucket(km)}
                >
                    {km}km
                </button>
            {/each}
        </div>

        <div class="h-64">
            <Chart
                data={visiblePoints}
                x="date"
                xScale={scaleTime()}
                y="pace"
                {yDomain}
                yNice={false}
                padding={{ top: 8, bottom: 24, left: 56, right: 8 }}
            >
                <Svg>
                    <Axis
                        placement="bottom"
                        format={(v) => formatShortDate(v instanceof Date ? v : new Date(v))}
                        ticks={5}
                        rule
                        class="text-muted-foreground"
                    />
                    <Axis
                        placement="left"
                        format={(v) => formatPace(v)}
                        ticks={5}
                        grid={{ class: 'stroke-border/50' }}
                        class="text-muted-foreground"
                    />
                    {#each seriesData as series (series.km)}
                        <Spline
                            data={series.points}
                            class="stroke-2"
                            style="stroke: {bucketColor(series.km)};"
                        />
                        <Points
                            data={series.points}
                            r={3}
                            class="stroke-card stroke-2"
                            style="fill: {bucketColor(series.km)};"
                        />
                    {/each}
                    <Highlight
                        points={{ class: 'stroke-card stroke-2', r: 4 }}
                        lines={{ class: 'stroke-border/50 stroke-[1px] [stroke-dasharray:4]' }}
                    />
                </Svg>
                <Tooltip.Context mode="bisect-x">
                    <Tooltip.Root>
                        {#snippet children({ data: d })}
                            {@const hoveredDate = d.date.getTime()}
                            {@const matchingPoints = filteredPoints.filter(
                                (p) =>
                                    p.date.getTime() === hoveredDate &&
                                    enabledBuckets.has(p.bucket_km),
                            )}
                            <div
                                class="rounded-lg border border-border bg-popover px-3 py-2 text-xs shadow-lg"
                            >
                                <div class="mb-1 text-muted-foreground">
                                    {formatShortDate(d.date)}
                                </div>
                                {#each matchingPoints.sort((a, b) => a.bucket_km - b.bucket_km) as point}
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-block h-2 w-2 rounded-full"
                                            style="background-color: {bucketColor(point.bucket_km)};"
                                        ></span>
                                        <span class="font-medium">{point.bucket_km}km</span>
                                        <span class="font-bold">{formatPace(point.pace)}</span>
                                    </div>
                                {/each}
                            </div>
                        {/snippet}
                    </Tooltip.Root>
                </Tooltip.Context>
            </Chart>
        </div>
    </div>
{/if}
