<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { Button } from '@/components/ui/button';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';

    let {
        canRegister = true,
    }: {
        canRegister: boolean;
    } = $props();

    const auth = $derived($page.props.auth);
</script>

<AppHead title="Currere — Run Smarter" />

<div class="flex min-h-screen flex-col bg-background text-foreground">
    <!-- Nav -->
    <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
        <div class="flex items-center gap-2">
            <AppLogoIcon class="size-7 fill-current text-primary" />
            <span class="text-lg font-semibold">Currere</span>
        </div>
        <nav class="flex items-center gap-3">
            {#if auth.user}
                <Button asChild variant="default" size="sm">
                    {#snippet children(props)}
                        <Link href={toUrl(dashboard())} {...props}>Dashboard</Link>
                    {/snippet}
                </Button>
            {:else}
                <Button asChild variant="ghost" size="sm">
                    {#snippet children(props)}
                        <Link href={toUrl(login())} {...props}>Log in</Link>
                    {/snippet}
                </Button>
                {#if canRegister}
                    <Button asChild variant="default" size="sm">
                        {#snippet children(props)}
                            <Link href={toUrl(register())} {...props}>Register</Link>
                        {/snippet}
                    </Button>
                {/if}
            {/if}
        </nav>
    </header>

    <!-- Hero -->
    <main class="flex flex-1 flex-col items-center justify-center px-6 pb-16 pt-12 text-center lg:pt-20">
        <AppLogoIcon class="mb-6 size-16 fill-current text-primary lg:size-20" />
        <h1 class="mb-4 text-4xl font-bold tracking-tight lg:text-5xl">
            Run smarter, not just further
        </h1>
        <p class="mb-8 max-w-lg text-lg text-muted-foreground">
            Sync your runs from Android, get AI-powered insights, and track your performance over time.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            {#if auth.user}
                <Button asChild size="lg">
                    {#snippet children(props)}
                        <Link href={toUrl(dashboard())} {...props}>Go to Dashboard</Link>
                    {/snippet}
                </Button>
            {:else}
                {#if canRegister}
                    <Button asChild size="lg">
                        {#snippet children(props)}
                            <Link href={toUrl(register())} {...props}>Get Started</Link>
                        {/snippet}
                    </Button>
                {/if}
                <Button asChild variant="outline" size="lg">
                    {#snippet children(props)}
                        <Link href={toUrl(login())} {...props}>Log in</Link>
                    {/snippet}
                </Button>
            {/if}
        </div>

        <!-- Features -->
        <div class="mt-20 grid w-full max-w-4xl gap-6 sm:grid-cols-3">
            <div class="rounded-lg border border-border bg-card p-6 text-left">
                <span class="material-symbols-outlined mb-3 text-3xl text-primary">directions_run</span>
                <h3 class="mb-1 font-semibold">Run Tracking</h3>
                <p class="text-sm text-muted-foreground">
                    Automatically sync runs from your Android device. View splits, pace, distance, and heart rate data.
                </p>
            </div>
            <div class="rounded-lg border border-border bg-card p-6 text-left">
                <span class="material-symbols-outlined mb-3 text-3xl text-primary">psychology</span>
                <h3 class="mb-1 font-semibold">AI Insights</h3>
                <p class="text-sm text-muted-foreground">
                    Get personalized analysis of your training patterns, recovery trends, and actionable coaching tips.
                </p>
            </div>
            <div class="rounded-lg border border-border bg-card p-6 text-left">
                <span class="material-symbols-outlined mb-3 text-3xl text-primary">monitoring</span>
                <h3 class="mb-1 font-semibold">Performance Analytics</h3>
                <p class="text-sm text-muted-foreground">
                    Track progress with interactive charts. Compare splits, monitor trends, and hit your goals.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-sm text-muted-foreground">
        Currere
    </footer>
</div>
