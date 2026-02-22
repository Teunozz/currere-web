<script lang="ts">
    import { Area, Axis, Chart, Highlight, Svg, Tooltip } from 'layerchart';
    import { formatPace } from '@/lib/formatters';

    type PaceSplit = {
        kilometer_number: number;
        pace_seconds_per_km: number;
        is_partial: boolean;
    };

    let { splits }: { splits: PaceSplit[] } = $props();

    const data = $derived(
        splits.map((s) => ({
            km: s.kilometer_number,
            pace: s.pace_seconds_per_km,
            isPartial: s.is_partial,
        })),
    );
</script>

{#if splits.length === 0}
    <div class="flex h-64 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground">
        No pace data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card p-4">
        <h3 class="mb-4 text-sm font-medium text-muted-foreground">Pace per Kilometer</h3>
        <div class="h-64">
            <Chart
                {data}
                x="km"
                y="pace"
                padding={{ top: 8, bottom: 24, left: 56, right: 8 }}
            >
                <Svg>
                    <Axis placement="bottom" format={(v) => `${v} km`} ticks={Math.min(splits.length, 10)} />
                    <Axis placement="left" format={(v) => formatPace(v)} ticks={5} />
                    <Area class="fill-[var(--chart-pace)]/20" />
                    <Highlight points lines />
                </Svg>
                <Tooltip.Context mode="bisect-x">
                    <Tooltip.Root>
                        {#snippet children({ data: d })}
                            <div class="rounded-lg bg-popover px-3 py-1.5 text-xs shadow-md">
                                <span class="font-bold">{formatPace(d.pace)}</span>
                                <span class="text-muted-foreground ml-2">km {d.km}{d.isPartial ? ' (partial)' : ''}</span>
                            </div>
                        {/snippet}
                    </Tooltip.Root>
                </Tooltip.Context>
            </Chart>
        </div>
    </div>
{/if}
