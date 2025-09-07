<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle } from 'lucide-vue-next';

interface Props {
    open: boolean;
    targetPlan: string;
}

interface Emits {
    (e: 'close'): void;
    (e: 'success', companyData: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = useForm({
    company_name: '',
    target_plan: props.targetPlan,
});

const creating = ref(false);

const createCompanyAndProceed = () => {
    if (!form.company_name.trim()) {
        return;
    }
    
    creating.value = true;
    form.target_plan = props.targetPlan;
    
    form.post('/subscription/create-company-and-upgrade', {
        onSuccess: (page) => {
            // Check if we got a redirect URL (for Stripe checkout)
            const flash = page.props.flash as any;
            if (flash?.redirect_url) {
                window.location.href = flash.redirect_url;
            } else {
                emit('success', page.props.company);
                emit('close');
            }
        },
        onError: (errors) => {
            console.error('Company creation failed:', errors);
        },
        onFinish: () => {
            creating.value = false;
        }
    });
};

const closeModal = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertCircle class="w-5 h-5 text-amber-600" />
                    Company Required
                </DialogTitle>
                <DialogDescription>
                    To upgrade to a {{ targetPlan }} plan, you need to create a company. Please enter your company name below.
                </DialogDescription>
            </DialogHeader>
            
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="company-name">Company Name</Label>
                    <Input
                        id="company-name"
                        v-model="form.company_name"
                        placeholder="Enter your company name"
                        :disabled="creating"
                        @keydown.enter="createCompanyAndProceed"
                    />
                    <div v-if="form.errors.company_name" class="text-sm text-red-600">
                        {{ form.errors.company_name }}
                    </div>
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>What happens next:</strong>
                    </p>
                    <ul class="text-sm text-blue-600 dark:text-blue-400 mt-1 list-disc list-inside">
                        <li>We'll create your company profile</li>
                        <li>You'll be redirected to Stripe for payment</li>
                        <li>Your account will be upgraded to {{ targetPlan }} plan</li>
                    </ul>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    @click="closeModal"
                    :disabled="creating"
                    class="flex-1"
                >
                    Cancel
                </Button>
                <Button
                    @click="createCompanyAndProceed"
                    :disabled="!form.company_name.trim() || creating"
                    class="flex-1"
                >
                    {{ creating ? 'Creating...' : `Create Company & Upgrade to ${targetPlan}` }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
