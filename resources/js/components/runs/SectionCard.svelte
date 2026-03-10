<script lang="ts">
    import type { Snippet } from 'svelte';

    let { title, icon, colorVar, plain = false, children }: { title: string; icon: string; colorVar: string; plain?: boolean; children: Snippet } = $props();
</script>

<div
    class="rounded-xl border p-4"
    style={plain
        ? 'background-color: var(--color-card); border-color: var(--color-border);'
        : `background-color: color-mix(in srgb, ${colorVar} 8%, var(--color-card)); border-color: color-mix(in srgb, ${colorVar} 20%, transparent);`}
>
    <div class="mb-4 flex items-center gap-2">
        <span
            class="material-symbols-outlined text-base"
            style="color: {colorVar};"
        >
            {icon}
        </span>
        <h3 class="text-sm font-semibold">{title}</h3>
    </div>
    {@render children()}
</div>
