<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
// import { login } from '@/routes';
import { Form, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import SeoHead from '@/components/SeoHead.vue';

interface Props {
    subscriptionType?: string;
    billingInterval?: 'month' | 'year';
}

const props = withDefaults(defineProps<Props>(), {
    subscriptionType: 'FREE',
    billingInterval: 'month'
});

const companyType = ref('');
const subscriptionType = ref(props.subscriptionType);
const billingInterval = ref<Props['billingInterval']>(props.billingInterval);

// Combined select value so we can distinguish monthly vs yearly options in the dropdown
// Format: PLAN:INTERVAL, e.g. "MIDI:month", "MIDI:year"
const subscriptionChoice = ref<string>(`${props.subscriptionType}:${props.billingInterval}`);

// Watch for prop changes and update the reactive variable
watch(() => props.subscriptionType, (newValue) => {
    subscriptionType.value = newValue;
    subscriptionChoice.value = `${newValue}:${billingInterval.value}`;
}, { immediate: true });

watch(() => props.billingInterval, (newValue) => {
    billingInterval.value = newValue;
    subscriptionChoice.value = `${subscriptionType.value}:${newValue}`;
}, { immediate: true });

// When the user changes the dropdown, update both subscription type and billing interval
watch(subscriptionChoice, (newValue) => {
    const [type, interval] = newValue.split(':') as [string, 'month' | 'year'];

    // Fallbacks for safety
    subscriptionType.value = (['FREE', 'MIDI', 'MAXI'].includes(type) ? type : 'FREE') as typeof subscriptionType.value;
    billingInterval.value = (interval === 'year' ? 'year' : 'month');
}, { immediate: true });

// Hide individual option for paid plans
const showIndividualOption = computed(() => subscriptionType.value === 'FREE');
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <SeoHead
            title="Create an account"
            description="Sign up for ZapTask and start organizing your team's work."
            image="/zap_icon.png"
        />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name" placeholder="Full name" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email" placeholder="email@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" type="password" required :tabindex="3" autocomplete="new-password" name="password" placeholder="Password" />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <!-- Subscription Type Selection -->
                <div class="grid gap-2">
                    <Label for="subscription_type">Choose Your Plan</Label>
                    <!-- Hidden fields to pass clean values to backend -->
                    <input type="hidden" name="subscription_type" :value="subscriptionType" />
                    <input type="hidden" name="billing_interval" :value="billingInterval" />
                    <select
                        id="subscription_type"
                        name="subscription_choice"
                        v-model="subscriptionChoice"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        :tabindex="5"
                    >
                        <!-- FREE (no yearly variant) -->
                        <option value="FREE:month">
                            ⭐ FREE - £0/month (1 user, 3 projects, 200 todos)
                        </option>
                        <!-- MIDI monthly -->
                        <option value="MIDI:month">
                            ⭐ MIDI - £6/month (Up to 5 members, 10 clients, 20 projects per client, unlimited todos)
                        </option>
                        <!-- MIDI yearly -->
                        <option value="MIDI:year">
                            ⭐ MIDI - £60/year (Up to 5 members, 10 clients, 20 projects per client, unlimited todos)
                        </option>
                        <!-- MAXI monthly -->
                        <option value="MAXI:month">
                            ⭐ MAXI - £12/month (Up to 20 members, 30 clients, 40 projects per client, unlimited todos)
                        </option>
                        <!-- MAXI yearly -->
                        <option value="MAXI:year">
                            ⭐ MAXI - £120/year (Up to 20 members, 30 clients, 40 projects per client, unlimited todos)
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">
                        You can upgrade or change your plan anytime after registration.
                    </p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Have a lifetime deal?
                        <Link href="/ltd/redeem" class="text-blue-600 hover:underline">
                            Redeem your code after signup
                        </Link>.
                    </p>
                    <InputError :message="errors.subscription_type" />
                </div>

                <!-- Company Selection -->
                <div class="grid gap-4">
                    <Label>Company</Label>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <input
                                id="create_company"
                                type="radio"
                                name="company_type"
                                value="create"
                                v-model="companyType"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label for="create_company" class="text-sm font-medium">I am creating the account for my company</Label>
                        </div>
                        <div v-if="companyType === 'create'" class="ml-6">
                            <Input
                                id="company_name"
                                type="text"
                                name="company_name"
                                placeholder="Enter company name"
                                :tabindex="5"
                            />
                            <InputError :message="errors.company_name" />
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="join_company"
                                type="radio"
                                name="company_type"
                                value="join"
                                v-model="companyType"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label for="join_company" class="text-sm font-medium">I belong to a company and have a code</Label>
                        </div>
                        <div v-if="companyType === 'join'" class="ml-6">
                            <Input
                                id="company_code"
                                type="text"
                                name="company_code"
                                placeholder="Enter company code"
                                :tabindex="5"
                                maxlength="8"
                                style="text-transform: uppercase;"
                            />
                            <InputError :message="errors.company_code" />
                        </div>

                        <div v-if="showIndividualOption" class="flex items-center space-x-2">
                            <input
                                id="individual"
                                type="radio"
                                name="company_type"
                                value="individual"
                                v-model="companyType"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label for="individual" class="text-sm font-medium">I am not part of a company / Individual use</Label>
                        </div>
                    </div>
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink href="/login" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
