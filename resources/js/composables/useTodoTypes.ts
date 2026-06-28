import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface TodoTypeOption {
    value: string;
    icon: string;
    label: string;
}

const FALLBACK_OPTIONS: TodoTypeOption[] = [
    { value: 'Task', icon: 'CheckSquare', label: 'Task' },
    { value: 'Project', icon: 'FolderKanban', label: 'Project' },
    { value: 'Meeting', icon: 'Users', label: 'Meeting' },
    { value: 'Follow-up', icon: 'Phone', label: 'Follow-up' },
    { value: 'Other', icon: 'CircleHelp', label: 'Other' },
];

const FALLBACK_ICONS: Record<string, string> = Object.fromEntries(
    FALLBACK_OPTIONS.map((option) => [option.value, option.icon]),
);

export function useTodoTypes() {
    const page = usePage();

    const typeOptions = computed<TodoTypeOption[]>(() => {
        const options = page.props.todoTypeOptions as TodoTypeOption[] | undefined;
        return options?.length ? options : FALLBACK_OPTIONS;
    });

    const typeIconMap = computed<Record<string, string>>(() => {
        const icons = page.props.todoTypeIcons as Record<string, string> | undefined;
        return icons && Object.keys(icons).length > 0 ? icons : FALLBACK_ICONS;
    });

    const companyIndustry = computed(() => (page.props.companyIndustry as string) ?? 'general');

    const industries = computed(() => (page.props.industries as Array<{ value: string; label: string }>) ?? []);

    const getTypeLabel = (type: string): string => {
        const option = typeOptions.value.find((item) => item.value === type);
        return option?.label ?? type;
    };

    const getTypeIcon = (type: string): string => {
        return typeIconMap.value[type] ?? 'Circle';
    };

    return {
        typeOptions,
        typeIconMap,
        companyIndustry,
        industries,
        getTypeLabel,
        getTypeIcon,
    };
}
