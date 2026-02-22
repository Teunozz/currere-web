<script lang="ts">
    import { Area, Axis, Chart, Highlight, Svg, Tooltip } from 'layerchart';

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
    <div class="flex h-64 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground">
        No heart rate data
    </div>
{:else}
    <div class="rounded-xl border border-border bg-card p-4">
        <h3 class="mb-4 text-sm font-medium text-muted-foreground">Heart Rate</h3>
        <div class="h-64">
            <Chart
                data={data()}
                x="elapsed"
                y="bpm"
                padding={{ top: 8, bottom: 24, left: 40, right: 8 }}
            >
                <Svg>
                    <Axis placement="bottom" format={formatElapsed} ticks={5} />
                    <Axis placement="left" format={(v) => `${v}`} ticks={5} />
                    <Area class="fill-[var(--chart-heart-rate)]/20" />
                    <Highlight points lines />
                </Svg>
                <Tooltip.Context mode="bisect-x">
                    <Tooltip.Root>
                        {#snippet children({ data: d })}
                            <div class="rounded-lg bg-popover px-3 py-1.5 text-xs shadow-md">
                                <span class="font-bold">{d.bpm} bpm</span>
                                <span class="text-muted-foreground ml-2">{formatElapsed(d.elapsed)}</span>
                            </div>
                        {/snippet}
                    </Tooltip.Root>
                </Tooltip.Context>
            </Chart>
        </div>
    </div>
{/if}
