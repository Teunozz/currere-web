<script lang="ts">
    import AlertTriangle from 'lucide-svelte/icons/alert-triangle';
    import Brain from 'lucide-svelte/icons/brain';
    import Calendar from 'lucide-svelte/icons/calendar';
    import HeartPulse from 'lucide-svelte/icons/heart-pulse';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Trophy from 'lucide-svelte/icons/trophy';
    import { Skeleton } from '@/components/ui/skeleton';

    type Skill = {
        id: string;
        name: string;
        description: string;
        icon: string;
    };

    let {
        skills,
        loading = null,
        disabled = false,
        onselect,
    }: {
        skills: Skill[];
        loading?: string | null;
        disabled?: boolean;
        onselect: (skillId: string) => void;
    } = $props();

    const iconMap: Record<string, typeof Calendar> = {
        calendar: Calendar,
        'trending-up': TrendingUp,
        trophy: Trophy,
        'heart-pulse': HeartPulse,
        'alert-triangle': AlertTriangle,
        brain: Brain,
    };
</script>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    {#each skills as skill (skill.id)}
        <button
            class="flex flex-col gap-2 rounded-xl border border-border bg-card p-4 text-left transition-colors hover:border-primary/50 hover:bg-muted/30 disabled:opacity-50 disabled:cursor-not-allowed"
            onclick={() => onselect(skill.id)}
            disabled={disabled || loading !== null}
        >
            {#if loading === skill.id}
                <Skeleton class="h-5 w-5 rounded" />
                <Skeleton class="h-4 w-3/4" />
                <Skeleton class="h-3 w-full" />
            {:else}
                {@const Icon = iconMap[skill.icon]}
                {#if Icon}
                    <Icon class="h-5 w-5 text-primary" />
                {/if}
                <h3 class="text-sm font-semibold">{skill.name}</h3>
                <p class="text-xs text-muted-foreground">{skill.description}</p>
            {/if}
        </button>
    {/each}
</div>
