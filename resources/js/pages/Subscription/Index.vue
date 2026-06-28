<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Check, CreditCard, AlertCircle, Crown } from 'lucide-vue-next';
import CompanyCreationModal from '@/components/CompanyCreationModal.vue';
import SeoHead from '@/components/SeoHead.vue';

interface User {
    id: number;
    name: string;
    email: string;
    company_id?: number | null;
}

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    effective_subscription_type?: string;
    subscription_status: string;
    subscription_ends_at?: string;
    stripe_subscription_id?: string;
    pending_change?: {
        current_plan: string;
        scheduled_plan: string;
        change_date: string;
        is_downgrade: boolean;
    };
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
    BUSINESS?: Plan;
    LTD_SOLO?: Plan;
    LTD_TEAM?: Plan;
    LTD_AGENCY?: Plan;
    LTD_BUSINESS?: Plan;
}

interface Props {
    user: User;
    company: Company;
    plans: Plans;
    stripePublicKey: string;
}

const props = defineProps<Props>();
const page = usePage();

const loading = ref(false);
const redeemLoading = ref(false);
const redemptionCode = ref('');
const redemptionError = ref<string | null>(null);
const redemptionSuccess = ref<string | null>(null);
const currentPlan = computed(() => props.company?.effective_subscription_type || props.company?.subscription_type || 'FREE');
const isActive = computed(() => props.company?.subscription_status === 'active');
const hasPendingChange = computed(() => !!props.company?.pending_change);
const billingInterval = ref<'month' | 'year'>('month');

// Company creation modal state
const showCompanyModal = ref(false);
const pendingPlan = ref('');

// Get error messages from the page
const errors = computed(() => page.props.errors as Record<string, string> || {});

// Show redemption code box only if user has a company_id
const canShowRedemptionBox = computed(() => {
    return props.user && props.user.company_id !== null && props.user.company_id !== undefined;
});

// Filter out FREE / BUSINESS appropriately, and hide BUSINESS entirely from UI
const availablePlans = computed(() => {
    const current = currentPlan.value;

    // Start from all plans but never show BUSINESS in the UI
    const filtered: Partial<Plans> = { ...props.plans };
    delete filtered.BUSINESS;

    // If user is on FREE plan, show all non-BUSINESS plans
    if (current === 'FREE') {
        return filtered;
    }

    // If user is on a paid plan, hide FREE (they can only upgrade/downgrade or cancel)
    delete filtered.FREE;
    return filtered;
});

// Debug logging

const formatPrice = (price: number): string => {
    return `£${(price / 100).toFixed(0)}`;
};

// const getPlanColor = (planType: string): string => {
//     switch (planType) {
//         case 'FREE': return 'gray';
//         case 'MIDI': return 'blue';
//         case 'MAXI': return 'yellow'; // Using yellow for gold effect
//         default: return 'gray';
//     }
// };

const getPlanClasses = (planType: string): string => {
    const isCurrentPlan = planType === currentPlan.value;
    
    if (isCurrentPlan) {
        if (planType === 'MAXI') {
            // Gold styling for MAXI plan
            return 'border-2 border-yellow-500 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20';
        } else if (planType === 'MIDI') {
            // Blue styling for MIDI plan
            return 'border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/20';
        } else {
            // Gray styling for FREE plan
            return 'border-2 border-gray-500 bg-gray-50 dark:bg-gray-900/20';
        }
    }
    
    return 'border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600';
};

const getButtonText = (planType: string): string => {
    const current = currentPlan.value;
    const plan = props.plans[planType as keyof Plans];
    
    // Define plan hierarchy for comparison
    const planHierarchy: Record<string, number> = { 
        'FREE': 0, 
        'MIDI': 1, 
        'MAXI': 2, 
        'BUSINESS': 3,
        'LTD_SOLO': 1.5, // Lifetime deals are between FREE and MIDI
        'LTD_TEAM': 2.5,
        'LTD_AGENCY': 3.5,
        'LTD_BUSINESS': 4.5
    };
    const currentLevel = planHierarchy[current] ?? 0;
    const targetLevel = planHierarchy[planType] ?? 0;
    
    if (targetLevel > currentLevel) {
        return `Upgrade to ${plan.name}`;
    } else if (targetLevel < currentLevel) {
        return `Downgrade to ${plan.name}`;
    } else {
        return `Switch to ${plan.name}`;
    }
};

const handlePlanChange = (planType: string) => {
    
    try {
        // Check if this is a lifetime deal
        const plan = props.plans[planType as keyof Plans];
        const isLifetime = (plan as any)?.is_lifetime || false;
        
        // For lifetime deals, always use 'month' (it's a one-time payment)
        // For recurring plans, use the selected billing interval
        const interval = isLifetime ? 'month' : billingInterval.value;
        
        changePlan(planType, interval);
    } catch {}
};

const getYearlyPrice = (planType: string): string | null => {
    const plan = props.plans[planType as keyof Plans];
    if (plan && 'price_yearly' in plan) {
        return formatPrice((plan as any).price_yearly);
    }
    return null;
};

const isLifetimePlan = (planType: string): boolean => {
    const plan = props.plans[planType as keyof Plans];
    return (plan as any)?.is_lifetime || false;
};

const redeemLifetimeCode = () => {
    if (!redemptionCode.value.trim() || redeemLoading.value) {
        return;
    }

    redeemLoading.value = true;
    redemptionError.value = null;
    redemptionSuccess.value = null;

    router.post(
        '/ltd/redeem',
        { code: redemptionCode.value.trim() },
        {
            onError: (formErrors) => {
                // Show first error for code if present
                if ((formErrors as any).code) {
                    redemptionError.value = (formErrors as any).code;
                } else if ((formErrors as any).message) {
                    redemptionError.value = (formErrors as any).message;
                } else {
                    redemptionError.value = 'There was a problem redeeming your code. Please try again.';
                }
            },
            onSuccess: () => {
                redemptionSuccess.value = 'Your redemption code was applied successfully.';
                redemptionCode.value = '';
                // Let server-side redirect or flash messages handle final state
            },
            onFinish: () => {
                redeemLoading.value = false;
            },
        },
    );
};

const changePlan = async (planType: string, interval: 'month' | 'year' = 'month') => {
    
    if (loading.value || planType === currentPlan.value) {
        return;
    }
    
    loading.value = true;
    
    // Use Inertia router which handles CSRF automatically
    router.post('/subscription/change-plan', { plan: planType, billing_interval: interval }, {
        onBefore: () => {
            return true;
        },
        onSuccess: (page) => {
            
            // Debug all props keys to see what's actually available
            
            // Check for redirect URL in flash data or response
            const flash = page.props.flash as any;
            
            // Try different ways to access the redirect URL
            let redirectUrl = null;
            
            if (flash?.redirect_url) {
                redirectUrl = flash.redirect_url;
            } else if (page.props.redirect_url) {
                redirectUrl = page.props.redirect_url;
            } else if (flash && typeof flash === 'object') {
                // Check all flash properties
                for (const [key, value] of Object.entries(flash)) {
                    if (key === 'redirect_url' || (typeof value === 'string' && value.includes('checkout.stripe.com'))) {
                        redirectUrl = value as string;
                        break;
                    }
                }
            }
            
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                
                // If downgrading (MAXI->MIDI or paid->FREE), reload projects to hide excess ones
                const isDowngrade = (currentPlan.value === 'MAXI' && planType === 'MIDI') || 
                                  (currentPlan.value !== 'FREE' && planType === 'FREE');
                
                if (isDowngrade) {
                    // Emit global event to reload projects
                    window.dispatchEvent(new CustomEvent('subscription-downgrade', { 
                        detail: { newPlan: planType, oldPlan: currentPlan.value }
                    }));
                }
                
                window.location.reload();
            }
        },
        onError: () => {
            
            // Check if the error is "No company found"
            if (errors.value.plan && errors.value.plan.includes('No company found')) {
                pendingPlan.value = planType;
                showCompanyModal.value = true;
                return;
            }
            
            let errorMessage = 'An error occurred while changing your plan. Please try again.';
            if (errors.value.plan) {
                errorMessage = errors.value.plan;
            } else if (errors.value.message) {
                errorMessage = errors.value.message;
            }
            
            alert(errorMessage);
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

// Get CSRF token from meta tag
const getCSRFToken = () => {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
};

// Test CSRF functionality
const testCSRF = async () => {
    const csrfToken = getCSRFToken();
    
    try {
        const response = await fetch('/subscription/test-csrf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        if (response.ok) {
            await response.json();
        } else {
        }
    } catch {}
};

// Test if component is mounting properly
onMounted(() => {
    
    // Run CSRF test automatically
    testCSRF();
});

const cancelSubscription = async () => {
    if (!confirm('Are you sure you want to cancel your subscription? You\'ll keep your current plan benefits until the end of your billing period, then be downgraded to the FREE plan.')) {
        return;
    }
    
    loading.value = true;
    
    router.post('/subscription/cancel-subscription', {}, {
        onSuccess: () => {
            // Page will automatically reload with updated data
        },
        onError: () => {
            alert('An error occurred while cancelling your subscription. Please try again.');
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

// Handle company creation modal events
const handleCompanyModalClose = () => {
    showCompanyModal.value = false;
    pendingPlan.value = '';
};

const handleCompanyCreated = () => {
    // The modal will handle the redirect to Stripe checkout
    showCompanyModal.value = false;
    pendingPlan.value = '';
};

const reactivateSubscription = async () => {
    if (!confirm('Are you sure you want to reactivate your subscription? This will cancel the scheduled downgrade.')) {
        return;
    }
    
    loading.value = true;
    
    router.post('/subscription/reactivate', {}, {
        onSuccess: () => {
            // Page will automatically reload with updated data
        },
        onError: () => {
            alert('An error occurred while reactivating your subscription. Please try again.');
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};
</script>

<template>
    <SeoHead
        title="Subscription management"
        description="Manage your ZapTask plan, billing details, and subscription status."
        image="/zap_icon.png"
    />
    
    <AppLayout>
        <div class="max-w-6xl mx-auto p-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Subscription Management
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Manage your ZapTask subscription and billing settings
                </p>
            </div>

            <!-- Error Message Display -->
            <div v-if="errors.subscription" class="mb-6">
                <Card class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                            <AlertCircle class="w-5 h-5 flex-shrink-0" />
                            <p class="text-sm font-medium">{{ errors.subscription }}</p>
                        </div>
                    </CardContent>
                </Card>
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
                                {{
                                    currentPlan === 'FREE'
                                        ? 'Free forever'
                                        : isLifetimePlan(currentPlan)
                                            ? `${formatPrice(plans[currentPlan].price)} one-time (lifetime access)`
                                            : `${formatPrice(plans[currentPlan].price)} per month`
                                }}
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

            <!-- LTD Redemption Code (only for users with a company) -->
            <div v-if="canShowRedemptionBox" class="mb-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Redeem Lifetime Deal</CardTitle>
                        <CardDescription>
                            Got a redemption code? Enter it here to apply your lifetime ZapTask plan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Redemption Code
                            </label>
                            <input
                                v-model="redemptionCode"
                                type="text"
                                placeholder="Enter your lifetime deal redemption code"
                                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                            />
                            <div v-if="redemptionError" class="text-xs text-red-600">
                                {{ redemptionError }}
                            </div>
                            <div v-if="redemptionSuccess" class="text-xs text-green-600">
                                {{ redemptionSuccess }}
                            </div>
                            <Button
                                class="mt-2"
                                :disabled="redeemLoading || !redemptionCode.trim()"
                                @click="redeemLifetimeCode"
                            >
                                {{ redeemLoading ? 'Redeeming...' : 'Apply Redemption Code' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pending Subscription Change -->
            <Card v-if="hasPendingChange" class="mb-8 border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-orange-700 dark:text-orange-300">
                        <AlertCircle class="w-5 h-5" />
                        Scheduled Plan Change
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-orange-700 dark:text-orange-300">
                        <p class="font-medium">
                            Your plan will change from {{ company?.pending_change?.current_plan }} to {{ company?.pending_change?.scheduled_plan }} 
                            on {{ company?.pending_change?.change_date ? new Date(company.pending_change.change_date).toLocaleDateString() : 'your next billing date' }}.
                        </p>
                        <p class="text-sm mt-2">
                            You'll continue to have access to your current {{ company?.pending_change?.current_plan }} plan features until then.
                        </p>
                        
                        <!-- Reactivation button for cancelled subscriptions -->
                        <div v-if="company?.pending_change?.scheduled_plan === 'FREE'" class="mt-4">
                            <Button
                                @click="reactivateSubscription"
                                :disabled="loading"
                                variant="outline"
                                class="bg-white text-orange-700 border-orange-300 hover:bg-orange-50 dark:bg-orange-900/50 dark:text-orange-300 dark:border-orange-600 dark:hover:bg-orange-800/50"
                            >
                                Reactivate Subscription
                            </Button>
                            <p class="text-xs mt-1">
                                Cancel the scheduled downgrade and continue your subscription.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Available Plans
                </h2>
                
                <!-- Billing Interval Toggle (only for recurring plans) -->
                <div v-if="availablePlans.MIDI || availablePlans.MAXI" class="mb-6 flex justify-center">
                    <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 p-1">
                        <button
                            @click="billingInterval = 'month'"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                billingInterval === 'month'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            Monthly
                        </button>
                        <button
                            @click="billingInterval = 'year'"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                billingInterval === 'year'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            Yearly
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card 
                        v-for="(plan, planType) in availablePlans" 
                        :key="planType"
                        :class="getPlanClasses(planType)"
                    >
                        <CardHeader class="text-center">
                            <CardTitle class="flex items-center justify-center gap-2">
                                <Crown 
                                    v-if="planType === 'MAXI'" 
                                    class="w-5 h-5 text-yellow-500" 
                                />
                                <span :class="{ 'text-yellow-600 dark:text-yellow-400': planType === 'MAXI' }">
                                    {{ plan.name }}
                                </span>
                                <Check 
                                    v-if="planType === currentPlan" 
                                    class="w-5 h-5 text-green-500" 
                                />
                            </CardTitle>
                            <CardDescription>
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                    <template v-if="planType === 'FREE'">Free</template>
                                    <template v-else-if="isLifetimePlan(planType)">{{ formatPrice(plan.price) }}</template>
                                    <template v-else-if="billingInterval === 'year' && getYearlyPrice(planType)">
                                        {{ getYearlyPrice(planType) }}
                                    </template>
                                    <template v-else>{{ formatPrice(plan.price) }}</template>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <template v-if="planType === 'FREE'">Forever</template>
                                    <template v-else-if="isLifetimePlan(planType)">One-time payment</template>
                                    <template v-else-if="billingInterval === 'year'">per year</template>
                                    <template v-else>per month</template>
                                </div>
                                <div v-if="!isLifetimePlan(planType) && planType !== 'FREE' && getYearlyPrice(planType)" class="text-xs text-green-600 dark:text-green-400 mt-1">
                                    <template v-if="billingInterval === 'year'">Save £{{ ((plan.price * 12) - (plan as any).price_yearly) / 100 }}</template>
                                    <template v-else>or {{ getYearlyPrice(planType) }}/year (save £{{ ((plan.price * 12) - (plan as any).price_yearly) / 100 }})</template>
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
                                @click="handlePlanChange(planType)"
                                :disabled="loading"
                                :variant="planType === 'MIDI' || planType === 'MAXI' || planType?.startsWith('LTD_') ? 'default' : 'outline'"
                                class="w-full"
                            >
                                {{ getButtonText(planType) }}
                            </Button>
                            
                            <div v-else class="text-center">
                                <span class="text-sm text-gray-500">Current Plan</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Cancel Subscription -->
            <Card v-if="currentPlan !== 'FREE' && company?.stripe_subscription_id && (!hasPendingChange || company?.pending_change?.scheduled_plan !== 'FREE')" class="border-red-200 dark:border-red-800">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <AlertCircle class="w-5 h-5" />
                        Danger Zone
                    </CardTitle>
                    <CardDescription>
                        Cancel your subscription. You'll keep your current plan benefits until the end of your billing period.
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
        
        <!-- Company Creation Modal -->
        <CompanyCreationModal
            :open="showCompanyModal"
            :target-plan="pendingPlan"
            @close="handleCompanyModalClose"
            @success="handleCompanyCreated"
        />
    </AppLayout>
</template>
