<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface TokenRow {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string | null;
}

const props = defineProps<{
    tokens: TokenRow[];
    plainTextToken?: string | null;
    apiBaseUrl: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Tokens',
        href: '/settings/api-tokens',
    },
];

const page = usePage();
const flashToken = computed(
    () => props.plainTextToken || (page.props.flash as { plainTextToken?: string } | undefined)?.plainTextToken || null,
);

const createForm = useForm({
    name: 'Chrome Extension',
});

const copied = ref(false);
const copiedBaseUrl = ref(false);

const createToken = () => {
    createForm.post('/settings/api-tokens', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.name = 'Chrome Extension';
        },
    });
};

const revokeToken = (tokenId: number) => {
    if (!confirm('Revoke this token? The Chrome extension will stop working until you create a new one.')) {
        return;
    }

    router.delete(`/settings/api-tokens/${tokenId}`, { preserveScroll: true });
};

const copyText = async (value: string, kind: 'token' | 'url') => {
    try {
        await navigator.clipboard.writeText(value);
        if (kind === 'token') {
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } else {
            copiedBaseUrl.value = true;
            setTimeout(() => {
                copiedBaseUrl.value = false;
            }, 2000);
        }
    } catch {
        // Clipboard may be blocked; user can still select manually
    }
};

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Never';
    }

    return new Date(value).toLocaleString();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <SeoHead
            title="API Tokens"
            description="Create personal access tokens for the ZapTask Chrome extension."
            image="/zap_icon.png"
        />

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall
                    title="API Tokens"
                    description="Connect the ZapTask Chrome extension without sharing your password"
                />

                <div class="rounded-lg border border-border bg-muted/40 p-4 text-sm space-y-3">
                    <p class="font-medium text-foreground">Setup</p>
                    <ol class="list-decimal list-inside space-y-1 text-muted-foreground">
                        <li>Create a token below and copy it immediately (shown once).</li>
                        <li>Open the ZapTask extension options in Chrome.</li>
                        <li>Paste the API base URL and token, then save.</li>
                    </ol>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <code class="rounded bg-background px-2 py-1 text-xs">{{ apiBaseUrl }}</code>
                        <Button type="button" variant="outline" size="sm" @click="copyText(apiBaseUrl, 'url')">
                            {{ copiedBaseUrl ? 'Copied' : 'Copy URL' }}
                        </Button>
                    </div>
                </div>

                <div
                    v-if="flashToken"
                    class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 space-y-3"
                >
                    <p class="text-sm font-medium text-foreground">
                        Copy your new token now — it will not be shown again.
                    </p>
                    <code class="block break-all rounded bg-background px-3 py-2 text-xs">{{ flashToken }}</code>
                    <Button type="button" size="sm" @click="copyText(flashToken, 'token')">
                        {{ copied ? 'Copied' : 'Copy token' }}
                    </Button>
                </div>

                <form class="space-y-4" @submit.prevent="createToken">
                    <div class="grid gap-2">
                        <Label for="token-name">Token name</Label>
                        <Input
                            id="token-name"
                            v-model="createForm.name"
                            class="mt-1 block w-full"
                            required
                            maxlength="255"
                            placeholder="Chrome Extension"
                        />
                        <InputError class="mt-2" :message="createForm.errors.name" />
                    </div>
                    <Button type="submit" :disabled="createForm.processing">Create token</Button>
                </form>

                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-foreground">Your tokens</h3>
                    <p v-if="!tokens.length" class="text-sm text-muted-foreground">No tokens yet.</p>
                    <ul v-else class="divide-y divide-border rounded-lg border border-border">
                        <li
                            v-for="token in tokens"
                            :key="token.id"
                            class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="space-y-1 text-sm">
                                <p class="font-medium text-foreground">{{ token.name }}</p>
                                <p class="text-muted-foreground">
                                    Created {{ formatDate(token.created_at) }} · Last used
                                    {{ formatDate(token.last_used_at) }}
                                </p>
                            </div>
                            <Button type="button" variant="outline" size="sm" @click="revokeToken(token.id)">
                                Revoke
                            </Button>
                        </li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
