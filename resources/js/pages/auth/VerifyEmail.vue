<script setup lang="ts">
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { Form } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import SeoHead from '@/components/SeoHead.vue';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout title="Verify email" description="Please verify your email address by clicking on the link we just emailed to you.">
        <SeoHead
            title="Email verification"
            description="Verify your email to finish setting up your ZapTask account."
            image="/zap_icon.png"
        />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <Form v-bind="EmailVerificationNotificationController.store.form()" class="space-y-6 text-center" v-slot="{ processing }">
            <Button :disabled="processing" variant="secondary">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                Resend verification email
            </Button>

            <TextLink :href="logout()" as="button" class="mx-auto block text-sm"> Log out </TextLink>
        </Form>
    </AuthLayout>
</template>
