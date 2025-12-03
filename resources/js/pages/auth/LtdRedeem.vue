<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Form, Head, usePage } from '@inertiajs/vue3';

const page = usePage<{
    flash?: { success?: string };
}>();
</script>

<template>
    <AuthBase
        title="Redeem your Lifetime Deal"
        description="Enter your LTD redemption code to activate your lifetime plan."
    >
        <Head title="Redeem Lifetime Deal" />

        <div v-if="page.props.flash?.success" class="mb-4 text-sm font-medium text-green-600">
            {{ page.props.flash.success }}
        </div>

        <Form
            action="/ltd/redeem"
            method="post"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="code">Redemption code</Label>
                    <Input
                        id="code"
                        name="code"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="Enter your LTD code"
                    />
                    <InputError :message="errors.code" />
                </div>

                <div class="grid gap-2">
                    <Label for="company_name">Company name (required for Team, Agency or Business)</Label>
                    <Input
                        id="company_name"
                        name="company_name"
                        type="text"
                        autocomplete="organization"
                        placeholder="Your company name"
                    />
                    <p class="text-xs text-muted-foreground">
                        If you’re redeeming a Team, Agency or Business LTD and you don’t already have a
                        company, we’ll create one using this name.
                    </p>
                    <InputError :message="errors.company_name" />
                </div>
            </div>

            <Button type="submit" class="w-full" :disabled="processing">
                <span v-if="!processing">Redeem Lifetime Deal</span>
                <span v-else>Redeeming…</span>
            </Button>
        </Form>
    </AuthBase>
</template>


