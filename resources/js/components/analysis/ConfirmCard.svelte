<script lang="ts" module>
    export type PendingActionType = 'edit_run' | 'delete_run';

    export type PendingAction = {
        id: string;
        action_type: PendingActionType;
        run_id: number;
        changes: Record<string, unknown>;
        summary: string;
        expires_at: string;
    };

    export type ConfirmResult =
        | { status: 'executed'; result: Record<string, unknown> }
        | { status: 'cancelled' }
        | { status: 'error'; message: string };
</script>

<script lang="ts">
    import Check from 'lucide-svelte/icons/check';
    import LoaderCircle from 'lucide-svelte/icons/loader-circle';
    import X from 'lucide-svelte/icons/x';
    import { Button } from '@/components/ui/button';

    let { action, onResult }: { action: PendingAction; onResult: (result: ConfirmResult) => void } = $props();

    let state = $state<'idle' | 'submitting' | 'done' | 'cancelled' | 'error'>('idle');
    let errorMessage = $state<string | null>(null);

    function csrfToken(): string {
        return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
    }

    async function confirm(): Promise<void> {
        state = 'submitting';
        errorMessage = null;
        try {
            const response = await fetch(`/analysis/actions/${action.id}/confirm`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(body?.message ?? `Request failed (${response.status})`);
            }
            state = 'done';
            onResult({ status: 'executed', result: body.result ?? {} });
        } catch (err) {
            errorMessage = (err as Error).message || 'Could not run that action.';
            state = 'error';
            onResult({ status: 'error', message: errorMessage });
        }
    }

    function cancel(): void {
        state = 'cancelled';
        onResult({ status: 'cancelled' });
    }

    const title = $derived(action.action_type === 'delete_run' ? 'Confirm delete' : 'Confirm edit');
    const changeEntries = $derived(Object.entries(action.changes ?? {}));
</script>

<div
    class="mt-3 rounded-xl border border-amber-300/70 bg-amber-50 p-3 text-amber-950 dark:border-amber-700/40 dark:bg-amber-950/30 dark:text-amber-100"
    role="region"
    aria-label={title}
>
    <p class="text-sm font-semibold">{title}</p>
    <p class="mt-1 text-sm">{action.summary}</p>

    {#if action.action_type === 'edit_run' && changeEntries.length > 0}
        <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs">
            {#each changeEntries as [field, value] (field)}
                <li>
                    <span class="font-medium">{field}</span>: {String(value)}
                </li>
            {/each}
        </ul>
    {/if}

    {#if state === 'done'}
        <p class="mt-3 text-sm font-medium">Done.</p>
    {:else if state === 'cancelled'}
        <p class="mt-3 text-sm text-muted-foreground">Cancelled.</p>
    {:else if state === 'error'}
        <p class="mt-3 text-sm text-destructive">{errorMessage}</p>
    {:else}
        <div class="mt-3 flex gap-2">
            <Button size="sm" onclick={confirm} disabled={state === 'submitting'}>
                {#if state === 'submitting'}
                    <LoaderCircle class="size-4 animate-spin" />
                {:else}
                    <Check class="size-4" />
                {/if}
                Confirm
            </Button>
            <Button size="sm" variant="outline" onclick={cancel} disabled={state === 'submitting'}>
                <X class="size-4" />
                Cancel
            </Button>
        </div>
    {/if}
</div>
