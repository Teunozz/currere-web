<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Copy from 'lucide-svelte/icons/copy';
    import Plus from 'lucide-svelte/icons/plus';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import QRCode from 'qrcode';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import type { BreadcrumbItem } from '@/types';

    type Token = {
        id: number;
        name: string;
        created_at: string;
        last_used_at: string | null;
    };

    let {
        tokens,
        newToken = null,
        baseUrl,
    }: {
        tokens: Token[];
        newToken?: string | null;
        baseUrl: string;
    } = $props();

    let tokenName = $state('');
    let errors = $state<Record<string, string>>({});
    let processing = $state(false);
    let copied = $state(false);
    let qrDataUrl = $state('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'API Tokens', href: '/settings/tokens' },
    ];

    $effect(() => {
        if (newToken) {
            generateQrCode();
        }
    });

    async function generateQrCode() {
        if (!newToken) return;
        const payload = JSON.stringify({ token: newToken, base_url: baseUrl });
        qrDataUrl = await QRCode.toDataURL(payload, { width: 256, margin: 2 });
    }

    function createToken() {
        processing = true;
        router.post('/settings/tokens', { name: tokenName }, {
            preserveScroll: true,
            onSuccess: () => {
                tokenName = '';
                errors = {};
            },
            onError: (errs: Record<string, string>) => {
                errors = errs;
            },
            onFinish: () => {
                processing = false;
            },
        });
    }

    function revokeToken(tokenId: number) {
        router.delete(`/settings/tokens/${tokenId}`, {
            preserveScroll: true,
        });
    }

    function copyToken() {
        if (newToken) {
            navigator.clipboard.writeText(newToken);
            copied = true;
            setTimeout(() => { copied = false; }, 2000);
        }
    }

    function formatDate(iso: string): string {
        return new Date(iso).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }
</script>

<AppHead title="API Tokens" />

<AppLayout breadcrumbs={breadcrumbs}>
    <h1 class="sr-only">API Token Management</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <Heading variant="small" title="API Tokens" description="Manage API tokens for connecting your Android app" />

            <div class="flex items-end gap-3">
                <div class="grid flex-1 gap-1.5">
                    <Label for="token-name">Device name</Label>
                    <Input
                        id="token-name"
                        placeholder="e.g. Samsung Galaxy S25"
                        bind:value={tokenName}
                    />
                    {#if errors.name}
                        <InputError message={errors.name} />
                    {/if}
                </div>
                <Button onclick={createToken} disabled={processing}>
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                    Generate
                </Button>
            </div>

            {#if newToken}
                <div class="rounded-xl border border-primary/30 bg-primary/5 p-4">
                    <p class="mb-2 text-sm font-medium">New token created! Copy it now — it won't be shown again.</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 overflow-x-auto rounded bg-muted px-3 py-2 text-xs">{newToken}</code>
                        <Button variant="outline" size="sm" onclick={copyToken}>
                            <Copy class="mr-1.5 h-3.5 w-3.5" />
                            {copied ? 'Copied!' : 'Copy'}
                        </Button>
                    </div>
                    {#if qrDataUrl}
                        <div class="mt-4 flex flex-col items-center gap-2">
                            <p class="text-sm text-muted-foreground">Scan with Currere Android app:</p>
                            <img src={qrDataUrl} alt="QR Code" class="rounded-lg" width="256" height="256" />
                        </div>
                    {/if}
                </div>
            {/if}

            {#if tokens.length > 0}
                <div class="rounded-xl border border-border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Name</th>
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Created</th>
                                <th class="px-4 py-2 text-left font-medium text-muted-foreground">Last Used</th>
                                <th class="px-4 py-2 text-right font-medium text-muted-foreground"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each tokens as token (token.id)}
                                <tr class="border-b border-border last:border-0">
                                    <td class="px-4 py-2 font-medium">{token.name}</td>
                                    <td class="px-4 py-2 text-muted-foreground">{formatDate(token.created_at)}</td>
                                    <td class="px-4 py-2 text-muted-foreground">{token.last_used_at ? formatDate(token.last_used_at) : 'Never'}</td>
                                    <td class="px-4 py-2 text-right">
                                        <Dialog>
                                            <DialogTrigger>
                                                {#snippet children(props)}
                                                    <Button {...props} variant="ghost" size="sm" class="text-destructive">
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </Button>
                                                {/snippet}
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogTitle>Revoke Token</DialogTitle>
                                                <DialogDescription>
                                                    Revoke "{token.name}"? The Android app will no longer be able to sync with this token.
                                                </DialogDescription>
                                                <DialogFooter>
                                                    <DialogClose>
                                                        {#snippet children(props)}
                                                            <Button {...props} variant="outline">Cancel</Button>
                                                        {/snippet}
                                                    </DialogClose>
                                                    <Button variant="destructive" onclick={() => revokeToken(token.id)}>Revoke</Button>
                                                </DialogFooter>
                                            </DialogContent>
                                        </Dialog>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {:else}
                <p class="text-sm text-muted-foreground">No active API tokens. Generate one to connect your Android app.</p>
            {/if}
        </div>
    </SettingsLayout>
</AppLayout>
