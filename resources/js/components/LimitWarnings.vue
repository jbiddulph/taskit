<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { X, AlertTriangle, Users, FolderOpen } from 'lucide-vue-next';

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    current_member_count: number;
    member_limit: number;
    current_project_count: number;
    project_limit: number;
}

interface Props {
    company: Company;
}

const props = defineProps<Props>();

// Track dismissed warnings in sessionStorage (reappear on new session)
const dismissedMemberWarning = ref(
    sessionStorage.getItem(`member-warning-dismissed-${props.company.id}`) === 'true'
);
const dismissedProjectWarning = ref(
    sessionStorage.getItem(`project-warning-dismissed-${props.company.id}`) === 'true'
);

// Check if we should show member warning (at limit - 1)
const shouldShowMemberWarning = computed(() => {
    if (dismissedMemberWarning.value) return false;
    if (props.company.subscription_type === 'MAXI' || props.company.subscription_type === 'BUSINESS' || props.company.subscription_type === 'LTD_BUSINESS') return false; // Unlimited
    
    const threshold = props.company.member_limit - 1;
    return props.company.current_member_count >= threshold;
});

// Check if we should show project warning (at 80% of limit)
const shouldShowProjectWarning = computed(() => {
    if (dismissedProjectWarning.value) return false;
    if (props.company.subscription_type === 'MAXI' || props.company.subscription_type === 'BUSINESS' || props.company.subscription_type === 'LTD_BUSINESS') return false; // Unlimited
    
    const threshold = Math.floor(props.company.project_limit * 0.8);
    return props.company.current_project_count >= threshold;
});

const dismissMemberWarning = () => {
    dismissedMemberWarning.value = true;
    sessionStorage.setItem(`member-warning-dismissed-${props.company.id}`, 'true');
};

const dismissProjectWarning = () => {
    dismissedProjectWarning.value = true;
    sessionStorage.setItem(`project-warning-dismissed-${props.company.id}`, 'true');
};

const getMemberUpgradeText = () => {
    if (props.company.subscription_type === 'FREE') {
        return 'Upgrade to MIDI (£6/month) or MAXI (£12/month) to add more members.';
    }
    return 'Upgrade to MAXI (£12/month) to add more members.';
};

const getProjectUpgradeText = () => {
    if (props.company.subscription_type === 'FREE') {
        return {
            before: 'to create more projects.',
            hasLinks: true
        };
    }
    return {
        before: 'to create more projects.',
        hasLinks: true,
        singlePlan: 'MAXI'
    };
};
</script>

<template>
    <div class="space-y-2">
        <!-- Member Limit Warning -->
        <div 
            v-if="shouldShowMemberWarning"
            class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mx-4 mt-4"
            role="alert"
        >
            <div class="flex items-center gap-3">
                <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                <div>
                    <div class="flex items-center gap-2">
                        <Users class="w-4 h-4 text-red-600 dark:text-red-400" />
                        <span class="text-sm font-medium text-red-800 dark:text-red-200">
                            Member Limit Warning
                        </span>
                    </div>
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ company.current_member_count }}/{{ company.member_limit }} members used. 
                        {{ getMemberUpgradeText() }}
                    </p>
                </div>
            </div>
            <button 
                @click="dismissMemberWarning"
                class="p-1 hover:bg-red-100 dark:hover:bg-red-800/30 rounded transition-colors"
                aria-label="Dismiss warning"
            >
                <X class="w-4 h-4 text-red-600 dark:text-red-400" />
            </button>
        </div>

        <!-- Project Limit Warning -->
        <div 
            v-if="shouldShowProjectWarning"
            class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mx-4"
            :class="{ 'mt-4': !shouldShowMemberWarning }"
            role="alert"
        >
            <div class="flex items-center gap-3">
                <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                <div>
                    <div class="flex items-center gap-2">
                        <FolderOpen class="w-4 h-4 text-red-600 dark:text-red-400" />
                        <span class="text-sm font-medium text-red-800 dark:text-red-200">
                            Project Limit Warning
                        </span>
                    </div>
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ company.current_project_count }}/{{ company.project_limit }} projects used. 
                        <template v-if="company.subscription_type === 'FREE'">
                            <Link href="/subscription" class="font-bold text-red-800 dark:text-red-200 hover:underline">
                                Upgrade to MIDI (£6/month)
                            </Link>
                            or 
                            <Link href="/subscription" class="font-bold text-red-800 dark:text-red-200 hover:underline">
                                MAXI (£12/month)
                            </Link>
                            {{ getProjectUpgradeText().before }}
                        </template>
                        <template v-else>
                            <Link href="/subscription" class="font-bold text-red-800 dark:text-red-200 hover:underline">
                                Upgrade to MAXI (£12/month)
                            </Link>
                            {{ getProjectUpgradeText().before }}
                        </template>
                    </p>
                </div>
            </div>
            <button 
                @click="dismissProjectWarning"
                class="p-1 hover:bg-red-100 dark:hover:bg-red-800/30 rounded transition-colors"
                aria-label="Dismiss warning"
            >
                <X class="w-4 h-4 text-red-600 dark:text-red-400" />
            </button>
        </div>
    </div>
</template>
