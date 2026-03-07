<script lang="ts">
    import { Area, Axis, Chart, Highlight, LinearGradient, Spline, Svg, Tooltip } from 'layerchart';

    type HeartRateSample = {
        timestamp: string;
        bpm: number;
    };

    let { samples, startTime }: { samples: HeartRateSample[]; startTime: string } = $props();

    const data = $derived(() => {
        const start = new Date(startTime).getTime();
        return samples.map((s) => ({
            elapsed: (new Date(s.timestamp).getTime() - start) / 60000,
            bpm: s.bpm,
        }));
    });

    function formatElapsed(minutes: number): string {
        const m = Math.floor(minutes);
        return `${m} min`;
    }
</script>

{#if samples.length === 0}
    <div
        class="flex h-64 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground"
    >
        No heart rate data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card p-4">
        <div class="mb-4 flex items-center gap-2">
            <span
                class="material-symbols-outlined text-base"
                style="color: var(--chart-heart-rate);"
            >
                favorite
            </span>
            <h3 class="text-sm font-medium text-muted-foreground">Heart Rate</h3>
        </div>
        <div class="h-64">
            <Chart
                data={data()}
                x="elapsed"
                y="bpm"
                padding={{ top: 8, bottom: 24, left: 40, right: 8 }}
            >
                <Svg>
                    <defs>
                        <LinearGradient
                            id="hr-gradient"
                            vertical
                            stops={[
                                [0, 'var(--chart-heart-rate)'],
                                [0.6, 'color-mix(in srgb, var(--chart-heart-rate) 20%, transparent)'],
                                [1, 'transparent'],
                            ]}
                        />
                    </defs>
                    <Axis
                        placement="bottom"
                        format={formatElapsed}
                        ticks={5}
                        rule
                        class="text-muted-foreground"
                    />
                    <Axis
                        placement="left"
                        format={(v) => `${v}`}
                        ticks={5}
                        grid={{ class: 'stroke-border/50' }}
                        class="text-muted-foreground"
                    />
                    <Area fill="url(#hr-gradient)" />
                    <Spline class="stroke-[var(--chart-heart-rate)] stroke-2" />
                    <Highlight
                        points={{ class: 'fill-[var(--chart-heart-rate)] stroke-card stroke-2', r: 4 }}
                        lines={{ class: 'stroke-[var(--chart-heart-rate)]/30 stroke-[1px] [stroke-dasharray:4]' }}
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
                                        style="background-color: var(--chart-heart-rate);"
                                    ></span>
                                    <span class="font-bold">{d.bpm} bpm</span>
                                </div>
                                <span class="text-muted-foreground">{formatElapsed(d.elapsed)}</span>
                            </div>
                        {/snippet}
                    </Tooltip.Root>
                </Tooltip.Context>
            </Chart>
        </div>
    </div>
{/if}
