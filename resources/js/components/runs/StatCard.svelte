<script lang="ts">
    type Stat = {
        label: string;
        value: string;
        unit: string | null;
    };

    let { title, icon, colorVar, stats }: { title: string; icon: string; colorVar: string; stats: Stat[] } = $props();
</script>

<div
    class="rounded-xl border p-5"
    style="background-color: color-mix(in srgb, {colorVar} 8%, var(--color-card)); border-color: color-mix(in srgb, {colorVar} 20%, transparent);"
>
    <div class="mb-4 flex items-center gap-2">
        <span
            class="material-symbols-outlined text-lg"
            style="color: {colorVar};"
        >
            {icon}
        </span>
        <h2 class="text-sm font-semibold">{title}</h2>
    </div>
    <div class="flex flex-col">
        {#each stats as stat, i (stat.label)}
            <div
                class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0"
                style={i > 0 ? `border-top: 1px solid color-mix(in srgb, ${colorVar} 15%, transparent);` : ''}
            >
                <span class="text-xs font-semibold">{stat.label}</span>
                <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums"
                    style="background-color: color-mix(in srgb, {colorVar} 15%, transparent); color: {colorVar};"
                >
                    {stat.value}{#if stat.unit}&nbsp;{stat.unit}{/if}
                </span>
            </div>
        {/each}
    </div>
</div>
