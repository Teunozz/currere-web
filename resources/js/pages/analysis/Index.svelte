<script lang="ts">
    import ArrowUp from 'lucide-svelte/icons/arrow-up';
    import ChartBar from 'lucide-svelte/icons/chart-bar';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import FileText from 'lucide-svelte/icons/file-text';
    import GitCompareArrows from 'lucide-svelte/icons/git-compare-arrows';
    import HeartPulse from 'lucide-svelte/icons/heart-pulse';
    import LoaderCircle from 'lucide-svelte/icons/loader-circle';
    import Search from 'lucide-svelte/icons/search';
    import Sparkles from 'lucide-svelte/icons/sparkles';
    import Square from 'lucide-svelte/icons/square';
    import Trophy from 'lucide-svelte/icons/trophy';
    import Wrench from 'lucide-svelte/icons/wrench';
    import { tick } from 'svelte';
    import { Markdown } from 'svelte-exmarkdown';
    import { gfmPlugin } from 'svelte-exmarkdown/gfm';
    import AppHead from '@/components/AppHead.svelte';
    import ConfirmCard, { type ConfirmResult, type PendingAction } from '@/components/analysis/ConfirmCard.svelte';
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    type IconComponent = typeof Search;
    type StarterPrompt = { id: string; label: string; prompt: string };
    type ToolCall = { name: string; args: Record<string, unknown> };
    type Role = 'user' | 'assistant';
    type ConfirmStatus = 'executed' | 'cancelled' | 'error';
    type ChatMessage = {
        id: string;
        role: Role;
        content: string;
        toolCalls?: ToolCall[];
        pendingAction?: PendingAction;
        confirmStatus?: ConfirmStatus;
    };

    let { starterPrompts, runCount }: { starterPrompts: StarterPrompt[]; runCount: number } = $props();

    let messages = $state<ChatMessage[]>([]);
    let input = $state('');
    let streaming = $state(false);
    let currentAssistant = $state<{
        id: string;
        content: string;
        toolCalls: ToolCall[];
        pendingAction?: PendingAction;
    } | null>(null);
    let error = $state<string | null>(null);
    let isAtBottom = $state(true);

    let abortController: AbortController | null = null;
    let scrollContainer: HTMLDivElement | null = null;
    let textarea: HTMLTextAreaElement | null = null;

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'AI Coach', href: '/analysis' }];
    const insufficientData = $derived(runCount < 3);
    const canSend = $derived(input.trim().length > 0 && !streaming);

    const toolMeta: Record<string, { label: string; icon: IconComponent }> = {
        fetch_runs: { label: 'Searching your runs', icon: Search },
        fetch_run_stats: { label: 'Crunching totals', icon: ChartBar },
        fetch_heart_rate_data: { label: 'Reading heart-rate data', icon: HeartPulse },
        fetch_run_detail: { label: 'Opening run detail', icon: FileText },
        compare_periods: { label: 'Comparing periods', icon: GitCompareArrows },
        fetch_personal_bests: { label: 'Looking up PBs', icon: Trophy },
        propose_run_edit: { label: 'Proposing edit', icon: Wrench },
        propose_run_delete: { label: 'Proposing delete', icon: Wrench },
    };

    function toolLabel(name: string): string {
        return toolMeta[name]?.label ?? humanise(name);
    }

    function toolIcon(name: string): IconComponent {
        return toolMeta[name]?.icon ?? Wrench;
    }

    function humanise(name: string): string {
        return name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function newId(): string {
        return crypto.randomUUID();
    }

    function csrfToken(): string {
        return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
    }

    const markdownPlugins = [gfmPlugin()];

    const proseChat =
        'text-sm leading-relaxed ' +
        '[&_p]:my-0 [&_p+p]:mt-3 ' +
        '[&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-2 hover:[&_a]:opacity-80 ' +
        '[&_strong]:font-semibold [&_em]:italic ' +
        '[&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 ' +
        '[&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 ' +
        '[&_li]:my-0.5 ' +
        '[&_code]:rounded [&_code]:bg-muted [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs ' +
        '[&_pre]:my-2 [&_pre]:rounded-lg [&_pre]:bg-muted [&_pre]:p-3 [&_pre]:text-xs [&_pre_code]:bg-transparent [&_pre_code]:p-0 ' +
        '[&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-3 [&_blockquote]:italic [&_blockquote]:text-muted-foreground ' +
        '[&_h1]:mt-3 [&_h1]:mb-1 [&_h1]:text-base [&_h1]:font-semibold ' +
        '[&_h2]:mt-3 [&_h2]:mb-1 [&_h2]:text-base [&_h2]:font-semibold ' +
        '[&_h3]:mt-2 [&_h3]:mb-1 [&_h3]:text-sm [&_h3]:font-semibold ' +
        '[&_hr]:my-3 [&_hr]:border-border ' +
        '[&_table]:my-2 [&_table]:w-full [&_table]:border-collapse [&_table]:text-xs ' +
        '[&_th]:border [&_th]:border-border [&_th]:px-2 [&_th]:py-1 [&_th]:text-left [&_th]:font-semibold ' +
        '[&_td]:border [&_td]:border-border [&_td]:px-2 [&_td]:py-1';

    const streamingCursor =
        " [&>*:last-child]:after:ml-0.5 [&>*:last-child]:after:content-['▍'] [&>*:last-child]:after:animate-pulse [&>*:last-child]:after:text-muted-foreground/70";

    function resizeTextarea(): void {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = `${Math.min(textarea.scrollHeight, 200)}px`;
    }

    function focusComposer(): void {
        textarea?.focus();
        resizeTextarea();
    }

    function applyStarter(prompt: StarterPrompt): void {
        if (insufficientData) return;
        input = prompt.prompt;
        tick().then(() => focusComposer());
    }

    function onScroll(): void {
        if (!scrollContainer) return;
        const distance = scrollContainer.scrollHeight - scrollContainer.scrollTop - scrollContainer.clientHeight;
        isAtBottom = distance < 80;
    }

    function scrollToBottom(smooth = true): void {
        if (!scrollContainer) return;
        scrollContainer.scrollTo({
            top: scrollContainer.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto',
        });
    }

    function maybeAutoScroll(): void {
        if (isAtBottom) {
            tick().then(() => scrollToBottom(false));
        }
    }

    async function send(): Promise<void> {
        const text = input.trim();
        if (!text || streaming) return;

        const userMessage: ChatMessage = { id: newId(), role: 'user', content: text };
        messages = [...messages, userMessage];
        input = '';
        tick().then(() => resizeTextarea());
        error = null;

        currentAssistant = { id: newId(), content: '', toolCalls: [] };
        streaming = true;
        isAtBottom = true;
        tick().then(() => scrollToBottom(false));

        abortController = new AbortController();

        try {
            const response = await fetch('/analysis/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    messages: messages.map((m) => ({ role: m.role, content: m.content })),
                }),
                signal: abortController.signal,
            });

            if (!response.ok || !response.body) {
                const body = await response.json().catch(() => ({}));
                throw new Error(body?.message ?? `Request failed (${response.status})`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                let boundary = buffer.indexOf('\n\n');
                while (boundary !== -1) {
                    const rawEvent = buffer.slice(0, boundary);
                    buffer = buffer.slice(boundary + 2);
                    handleEvent(rawEvent);
                    boundary = buffer.indexOf('\n\n');
                }
            }
        } catch (err) {
            if ((err as Error).name === 'AbortError') {
                // user stopped — keep partial reply
            } else {
                error = (err as Error).message || 'Something went wrong. Please try again.';
                // restore input so the user can retry/edit
                input = userMessage.content;
                messages = messages.filter((m) => m.id !== userMessage.id);
            }
        } finally {
            finalize();
        }
    }

    function handleEvent(raw: string): void {
        for (const line of raw.split('\n')) {
            if (!line.startsWith('data: ')) continue;
            const payload = line.slice(6);
            if (payload === '[DONE]') {
                finalize();
                return;
            }
            let parsed: {
                type?: string;
                delta?: string;
                tool_name?: string;
                arguments?: Record<string, unknown>;
                id?: string;
                action_type?: 'edit_run' | 'delete_run';
                run_id?: number;
                changes?: Record<string, unknown>;
                summary?: string;
                expires_at?: string;
            };
            try {
                parsed = JSON.parse(payload);
            } catch {
                continue;
            }
            if (!currentAssistant) continue;
            if (parsed.type === 'text_delta' && typeof parsed.delta === 'string') {
                currentAssistant = {
                    ...currentAssistant,
                    content: currentAssistant.content + parsed.delta,
                };
                maybeAutoScroll();
            } else if (parsed.type === 'tool_call' && typeof parsed.tool_name === 'string') {
                currentAssistant = {
                    ...currentAssistant,
                    toolCalls: [
                        ...currentAssistant.toolCalls,
                        { name: parsed.tool_name, args: parsed.arguments ?? {} },
                    ],
                };
                maybeAutoScroll();
            } else if (
                parsed.type === 'pending_action' &&
                typeof parsed.id === 'string' &&
                (parsed.action_type === 'edit_run' || parsed.action_type === 'delete_run')
            ) {
                currentAssistant = {
                    ...currentAssistant,
                    pendingAction: {
                        id: parsed.id,
                        action_type: parsed.action_type,
                        run_id: parsed.run_id ?? 0,
                        changes: parsed.changes ?? {},
                        summary: parsed.summary ?? '',
                        expires_at: parsed.expires_at ?? '',
                    },
                };
                maybeAutoScroll();
            }
        }
    }

    function finalize(): void {
        if (
            currentAssistant &&
            (currentAssistant.content || currentAssistant.toolCalls.length || currentAssistant.pendingAction)
        ) {
            messages = [
                ...messages,
                {
                    id: currentAssistant.id,
                    role: 'assistant',
                    content: currentAssistant.content,
                    toolCalls: currentAssistant.toolCalls,
                    pendingAction: currentAssistant.pendingAction,
                },
            ];
        }
        currentAssistant = null;
        streaming = false;
        abortController = null;
    }

    function handleConfirmResult(messageId: string, result: ConfirmResult): void {
        messages = messages.map((m) => (m.id === messageId ? { ...m, confirmStatus: result.status } : m));
    }

    function stop(): void {
        abortController?.abort();
    }

    function retry(): void {
        const lastUser = [...messages].reverse().find((m) => m.role === 'user');
        if (lastUser) {
            input = lastUser.content;
        }
        error = null;
        focusComposer();
    }

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            send();
        } else if (event.key === 'Escape' && streaming) {
            event.preventDefault();
            stop();
        }
    }
</script>

<AppHead title="AI Coach" />

<AppLayout {breadcrumbs}>
    <div class="flex h-[calc(100svh-4rem)] min-h-0 flex-col">
        <div
            bind:this={scrollContainer}
            onscroll={onScroll}
            role="log"
            aria-live="polite"
            aria-busy={streaming}
            class="relative flex-1 overflow-y-auto"
        >
            <div class="mx-auto w-full max-w-3xl px-4 py-6">
                {#if messages.length === 0 && !currentAssistant}
                    <div class="flex flex-col items-center justify-center gap-6 py-12 text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-accent text-accent-foreground">
                            <Sparkles class="size-6" />
                        </div>
                        <div class="space-y-2">
                            <h1 class="text-2xl font-semibold tracking-tight">Your running coach</h1>
                            <p class="max-w-md text-sm text-muted-foreground">
                                Ask anything about your training. I can pull stats, compare periods, and call out trends.
                            </p>
                        </div>

                        {#if insufficientData}
                            <Alert class="text-left">
                                <AlertTitle>Not enough data yet</AlertTitle>
                                <AlertDescription>
                                    You currently have {runCount} run{runCount === 1 ? '' : 's'}. With at least 3 runs I can spot real
                                    trends — sync more from your Android app to unlock the starter prompts.
                                </AlertDescription>
                            </Alert>
                        {/if}

                        <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2">
                            {#each starterPrompts as prompt (prompt.id)}
                                <button
                                    type="button"
                                    onclick={() => applyStarter(prompt)}
                                    disabled={insufficientData}
                                    class="group rounded-2xl border border-border bg-card px-4 py-3 text-left text-sm transition hover:border-ring/40 hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-card"
                                >
                                    <span class="font-medium">{prompt.label}</span>
                                </button>
                            {/each}
                        </div>
                    </div>
                {:else}
                    <div class="space-y-6">
                        {#each messages as message (message.id)}
                            {#if message.role === 'user'}
                                <div class="flex justify-end" aria-label="Your message">
                                    <div
                                        class="ml-auto max-w-[80%] whitespace-pre-wrap break-words rounded-2xl rounded-tr-md bg-primary px-4 py-2.5 text-sm text-primary-foreground"
                                    >
                                        {message.content}
                                    </div>
                                </div>
                            {:else}
                                <div class="flex gap-3" aria-label="Coach reply">
                                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
                                        <Sparkles class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        {#if message.toolCalls && message.toolCalls.length}
                                            <div class="mb-2 flex flex-wrap gap-1.5">
                                                {#each message.toolCalls as call, i (i)}
                                                    {@const Icon = toolIcon(call.name)}
                                                    <span
                                                        class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                                                    >
                                                        <Icon class="size-3.5" />
                                                        {toolLabel(call.name)}
                                                    </span>
                                                {/each}
                                            </div>
                                        {/if}
                                        <div class={proseChat}>
                                            <Markdown md={message.content} plugins={markdownPlugins} />
                                        </div>
                                        {#if message.pendingAction && !message.confirmStatus}
                                            <ConfirmCard
                                                action={message.pendingAction}
                                                onResult={(r) => handleConfirmResult(message.id, r)}
                                            />
                                        {/if}
                                    </div>
                                </div>
                            {/if}
                        {/each}

                        {#if currentAssistant}
                            <div class="flex gap-3" aria-label="Coach reply">
                                <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
                                    <Sparkles class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    {#if currentAssistant.toolCalls.length}
                                        <div class="mb-2 flex flex-wrap gap-1.5">
                                            {#each currentAssistant.toolCalls as call, i (i)}
                                                {@const Icon = toolIcon(call.name)}
                                                {@const isLatest = i === currentAssistant.toolCalls.length - 1 && currentAssistant.content === ''}
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground"
                                                >
                                                    {#if isLatest}
                                                        <LoaderCircle class="size-3.5 animate-spin" />
                                                    {:else}
                                                        <Icon class="size-3.5" />
                                                    {/if}
                                                    {toolLabel(call.name)}
                                                </span>
                                            {/each}
                                        </div>
                                    {/if}

                                    {#if currentAssistant.content}
                                        <div class={proseChat + streamingCursor}>
                                            <Markdown md={currentAssistant.content} plugins={markdownPlugins} />
                                        </div>
                                    {:else}
                                        <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                            <span class="sr-only">Thinking</span>
                                            <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.3s]"></span>
                                            <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.15s]"></span>
                                            <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60"></span>
                                        </div>
                                    {/if}
                                    {#if currentAssistant.pendingAction}
                                        <ConfirmCard action={currentAssistant.pendingAction} onResult={() => {}} />
                                    {/if}
                                </div>
                            </div>
                        {/if}

                        {#if error}
                            <Alert variant="destructive">
                                <AlertTitle>Couldn't reach the coach</AlertTitle>
                                <AlertDescription>
                                    <span class="block">{error}</span>
                                    <button
                                        type="button"
                                        onclick={retry}
                                        class="mt-2 inline-flex rounded-md border border-current px-2.5 py-1 text-xs font-medium hover:opacity-80"
                                    >
                                        Retry
                                    </button>
                                </AlertDescription>
                            </Alert>
                        {/if}
                    </div>
                {/if}
            </div>

            {#if !isAtBottom && (messages.length > 0 || currentAssistant)}
                <button
                    type="button"
                    onclick={() => scrollToBottom(true)}
                    class="sticky bottom-4 ml-auto mr-4 flex size-9 items-center justify-center rounded-full border border-border bg-card text-muted-foreground shadow-sm transition hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                    aria-label="Jump to latest"
                >
                    <ChevronDown class="size-4" />
                </button>
            {/if}
        </div>

        <div class="sticky bottom-0 border-t border-border bg-background/80 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto w-full max-w-3xl">
                <div
                    class="relative rounded-2xl border border-input bg-card shadow-sm transition focus-within:border-ring focus-within:ring-2 focus-within:ring-ring/20"
                >
                    <textarea
                        bind:this={textarea}
                        bind:value={input}
                        oninput={resizeTextarea}
                        onkeydown={onKeydown}
                        rows="1"
                        placeholder="Ask your coach…"
                        aria-label="Message"
                        class="w-full resize-none bg-transparent px-4 py-3 pr-12 text-sm focus:outline-none"
                    ></textarea>
                    {#if streaming}
                        <button
                            type="button"
                            onclick={stop}
                            aria-label="Stop generating"
                            class="absolute bottom-2 right-2 grid size-9 place-items-center rounded-full bg-primary text-primary-foreground transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                        >
                            <Square class="size-3.5 fill-current" />
                        </button>
                    {:else}
                        <button
                            type="button"
                            onclick={send}
                            disabled={!canSend}
                            aria-label="Send message"
                            class="absolute bottom-2 right-2 grid size-9 place-items-center rounded-full bg-primary text-primary-foreground transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 disabled:opacity-40"
                        >
                            <ArrowUp class="size-4" />
                        </button>
                    {/if}
                </div>
                <p class="mt-1.5 px-1 text-xs text-muted-foreground">
                    Enter to send · Shift+Enter for newline{streaming ? ' · Esc to stop' : ''}
                </p>
            </div>
        </div>
    </div>
</AppLayout>
