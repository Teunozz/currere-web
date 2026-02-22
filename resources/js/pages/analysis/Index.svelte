<script lang="ts">
    import SkillResultCard from '@/components/analysis/SkillResultCard.svelte';
    import SkillSelector from '@/components/analysis/SkillSelector.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    type Skill = {
        id: string;
        name: string;
        description: string;
        icon: string;
    };

    let { skills, runCount }: { skills: Skill[]; runCount: number } = $props();

    let loading = $state<string | null>(null);
    let result = $state<{ skill: string; data: Record<string, any> } | null>(null);
    let error = $state<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'AI Analysis', href: '/analysis' },
    ];

    const insufficientData = $derived(runCount < 3);

    async function runSkill(skillId: string) {
        loading = skillId;
        error = null;
        result = null;

        try {
            const response = await fetch(`/analysis/${skillId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                    ),
                },
            });

            const body = await response.json();

            if (!response.ok) {
                error = body.message ?? 'Something went wrong.';
                return;
            }

            result = { skill: body.skill, data: body.data };
        } catch {
            error = 'Failed to connect. Please try again.';
        } finally {
            loading = null;
        }
    }
</script>

<AppHead title="AI Analysis" />

<AppLayout {breadcrumbs}>
    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-bold">AI Analysis</h1>
            <p class="mt-1 text-sm text-muted-foreground">Select a skill to analyze your running data.</p>
        </div>

        {#if insufficientData}
            <Alert>
                <AlertTitle>Not enough data</AlertTitle>
                <AlertDescription>
                    You need at least 3 runs to use AI analysis. You currently have {runCount} run{runCount === 1 ? '' : 's'}.
                    Sync more runs from your Android app to get started.
                </AlertDescription>
            </Alert>
        {/if}

        <SkillSelector {skills} {loading} disabled={insufficientData} onselect={runSkill} />

        {#if error}
            <Alert variant="destructive">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        {/if}

        {#if result}
            <SkillResultCard skill={result.skill} data={result.data} />
        {/if}
    </div>
</AppLayout>
