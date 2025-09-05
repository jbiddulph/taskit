<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Check, CreditCard, AlertCircle } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    subscription_status: string;
    subscription_ends_at?: string;
    stripe_subscription_id?: string;
}

interface Plan {
    name: string;
    price: number;
    currency?: string;
    stripe_price_id?: string;
    features: string[];
}

interface Plans {
    FREE: Plan;
    MIDI: Plan;
    MAXI: Plan;
}

interface Props {
    user: User;
    company: Company;
    plans: Plans;
    stripePublicKey: string;
}

const props = defineProps<Props>();

const loading = ref(false);
const currentPlan = computed(() => props.company?.subscription_type || 'FREE');
const isActive = computed(() => props.company?.subscription_status === 'active');

// Debug logging
console.log('Subscription page loaded');
console.log('Props:', props);
console.log('Current plan computed:', currentPlan.value);

const formatPrice = (price: number): string => {
    return `£${(price / 100).toFixed(0)}`;
};

const getPlanColor = (planType: string): string => {
    switch (planType) {
        case 'FREE': return 'gray';
        case 'MIDI': return 'blue';
        case 'MAXI': return 'purple';
        default: return 'gray';
    }
};

const getPlanClasses = (planType: string): string => {
    const color = getPlanColor(planType);
    const isCurrentPlan = planType === currentPlan.value;
    
    if (isCurrentPlan) {
        return `border-2 border-${color}-500 bg-${color}-50 dark:bg-${color}-900/20`;
    }
    
    return 'border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600';
};

const changePlan = async (planType: string) => {
    console.log('changePlan called with:', planType);
    console.log('loading.value:', loading.value);
    console.log('currentPlan.value:', currentPlan.value);
    
    if (loading.value || planType === currentPlan.value) {
        console.log('Exiting early - either loading or same plan');
        return;
    }
    
    loading.value = true;
    console.log('Setting loading to true, starting request...');
    
    try {
        console.log('Making fetch request to /subscription/change-plan');
        const response = await fetch('/subscription/change-plan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ plan: planType }),
        });

        console.log('Response received:', response.status, response.statusText);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));

        if (response.ok) {
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.redirect_url) {
                console.log('Redirecting to Stripe checkout:', data.redirect_url);
                window.location.href = data.redirect_url;
            } else {
                console.log('Plan change successful, reloading page');
                window.location.reload();
            }
        } else {
            console.log('Response not ok, getting error data...');
            const errorData = await response.json().catch(() => ({}));
            console.log('Error data:', errorData);
            const errorMessage = errorData.message || errorData.errors?.plan?.[0] || 'Failed to change plan';
            throw new Error(errorMessage);
        }
    } catch (error) {
        console.error('Error changing plan:', error);
        alert('An error occurred while changing your plan. Please try again.');
    } finally {
        console.log('Setting loading to false');
        loading.value = false;
    }
};

const cancelSubscription = async () => {
    if (!confirm('Are you sure you want to cancel your subscription? Your account will be downgraded to the FREE plan.')) {
        return;
    }
    
    loading.value = true;
    
    router.post('/subscription/cancel-subscription', {}, {
        onSuccess: () => {
            // Page will automatically reload with updated data
        },
        onError: (errors) => {
            console.error('Error cancelling subscription:', errors);
            alert('An error occurred while cancelling your subscription. Please try again.');
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};
</script>

<template>
    <Head title="Subscription Management" />
    
    <AppLayout>
        <div class="max-w-6xl mx-auto p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Subscription Management
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Manage your TaskIT subscription and billing settings
                </p>
            </div>

            <!-- Current Subscription Status -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CreditCard class="w-5 h-5" />
                        Current Subscription
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold">{{ plans[currentPlan].name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ currentPlan === 'FREE' ? 'Free forever' : `${formatPrice(plans[currentPlan].price)} per month` }}
                            </p>
                            <div v-if="company?.subscription_ends_at" class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ isActive ? 'Renews on' : 'Expires on' }}: 
                                    {{ new Date(company.subscription_ends_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div 
                                class="px-3 py-1 rounded-full text-sm font-medium"
                                :class="{
                                    'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300': isActive,
                                    'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300': company?.subscription_status === 'past_due',
                                    'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300': company?.subscription_status === 'canceled',
                                    'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': currentPlan === 'FREE'
                                }"
                            >
                                {{ isActive ? 'Active' : company?.subscription_status || 'Free' }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Available Plans
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card 
                        v-for="(plan, planType) in plans" 
                        :key="planType"
                        :class="getPlanClasses(planType)"
                    >
                        <CardHeader class="text-center">
                            <CardTitle class="flex items-center justify-center gap-2">
                                {{ plan.name }}
                                <Check 
                                    v-if="planType === currentPlan" 
                                    class="w-5 h-5 text-green-500" 
                                />
                            </CardTitle>
                            <CardDescription>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ planType === 'FREE' ? 'Free' : formatPrice(plan.price) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ planType === 'FREE' ? 'Forever' : 'per month' }}
                                </div>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2 mb-6">
                                <li 
                                    v-for="feature in plan.features" 
                                    :key="feature"
                                    class="flex items-start gap-2 text-sm"
                                >
                                    <Check class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                            
                            <Button
                                v-if="planType !== currentPlan"
                                @click="() => { console.log('Button clicked for plan:', planType); changePlan(planType); }"
                                :disabled="loading"
                                :variant="planType === 'MIDI' ? 'default' : 'outline'"
                                class="w-full"
                            >
                                {{ planType === 'FREE' ? 'Downgrade to Free' : `Upgrade to ${plan.name}` }}
                            </Button>
                            
                            <div v-else class="text-center">
                                <span class="text-sm text-gray-500">Current Plan</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Cancel Subscription -->
            <Card v-if="currentPlan !== 'FREE' && company?.stripe_subscription_id" class="border-red-200 dark:border-red-800">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <AlertCircle class="w-5 h-5" />
                        Danger Zone
                    </CardTitle>
                    <CardDescription>
                        Cancel your subscription and downgrade to the FREE plan
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        @click="cancelSubscription"
                        :disabled="loading"
                        variant="destructive"
                    >
                        Cancel Subscription
                    </Button>
                    <p class="text-xs text-gray-500 mt-2">
                        Your subscription will remain active until the end of your current billing period.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
