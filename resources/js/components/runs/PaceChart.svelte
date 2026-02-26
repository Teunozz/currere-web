<script lang="ts">
    import { Area, Axis, Chart, Highlight, LinearGradient, Spline, Svg, Tooltip } from 'layerchart';
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

    const paces = $derived(data.map((d) => d.pace));
    const minPace = $derived(Math.min(...paces));
    const maxPace = $derived(Math.max(...paces));
    const yDomain = $derived.by(() => {
        const range = maxPace - minPace;
        const padding = Math.max(range * 0.5, 15);
        return [minPace - padding, maxPace + padding];
    });
</script>

{#if splits.length === 0}
    <div
        class="flex h-64 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground"
    >
        No pace data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card p-4">
        <div class="mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-base" style="color: var(--chart-pace);">
                speed
            </span>
            <h3 class="text-sm font-medium text-muted-foreground">Pace per Kilometer</h3>
        </div>
        <div class="h-64">
            <Chart
                {data}
                x="km"
                y="pace"
                yDomain={yDomain}
                yNice={false}
                padding={{ top: 8, bottom: 24, left: 56, right: 8 }}
            >
                <Svg>
                    <defs>
                        <LinearGradient
                            id="pace-gradient"
                            vertical
                            stops={[
                                [0, 'var(--chart-pace)'],
                                [0.6, 'color-mix(in srgb, var(--chart-pace) 20%, transparent)'],
                                [1, 'transparent'],
                            ]}
                        />
                    </defs>
                    <Axis
                        placement="bottom"
                        format={(v) => `${v} km`}
                        ticks={Math.min(splits.length, 10)}
                        rule
                        class="text-muted-foreground"
                    />
                    <Axis
                        placement="left"
                        format={(v) => formatPace(v)}
                        ticks={5}
                        grid
                        class="text-muted-foreground"
                        gridProps={{ class: 'stroke-border/50' }}
                    />
                    <Area fill="url(#pace-gradient)" />
                    <Spline class="stroke-[var(--chart-pace)] stroke-2" />
                    <Highlight
                        points={{ class: 'fill-[var(--chart-pace)] stroke-card stroke-2', r: 4 }}
                        lines={{ class: 'stroke-[var(--chart-pace)]/30 stroke-[1px] [stroke-dasharray:4]' }}
                    />
                </Svg>
                <Tooltip.Context mode="bisect-x">
                    <Tooltip.Root>
                        {#snippet children({ data: d })}
                            <div
                                class="rounded-lg border border-border bg-popover px-3 py-2 text-xs shadow-lg"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2 w-2 rounded-full"
                                        style="background-color: var(--chart-pace);"
                                    ></span>
                                    <span class="font-bold">{formatPace(d.pace)}</span>
                                </div>
                                <span class="text-muted-foreground">
                                    km {d.km}{d.isPartial ? ' (partial)' : ''}
                                </span>
                            </div>
                        {/snippet}
                    </Tooltip.Root>
                </Tooltip.Context>
            </Chart>
        </div>
    </div>
{/if}
