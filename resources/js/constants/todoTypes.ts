export type TodoType =
    | 'Bug'
    | 'Feature'
    | 'Task'
    | 'Story'
    | 'Epic'
    | 'Property'
    | 'Lead'
    | 'Viewing'
    | 'Offer'
    | 'Maintenance'
    | 'Compliance'
    | 'Other';

export interface TodoTypeOption {
    value: TodoType;
    icon: string;
    labelKey: string;
}

export const TODO_TYPE_OPTIONS: TodoTypeOption[] = [
    { value: 'Bug', icon: 'Bug', labelKey: 'todos.type_bug' },
    { value: 'Feature', icon: 'Zap', labelKey: 'todos.type_feature' },
    { value: 'Task', icon: 'CheckSquare', labelKey: 'todos.type_task' },
    { value: 'Story', icon: 'BookOpen', labelKey: 'todos.type_story' },
    { value: 'Epic', icon: 'Layers', labelKey: 'todos.type_epic' },
    { value: 'Property', icon: 'Home', labelKey: 'todos.type_property' },
    { value: 'Lead', icon: 'UserPlus', labelKey: 'todos.type_lead' },
    { value: 'Viewing', icon: 'Eye', labelKey: 'todos.type_viewing' },
    { value: 'Offer', icon: 'HandCoins', labelKey: 'todos.type_offer' },
    { value: 'Maintenance', icon: 'Wrench', labelKey: 'todos.type_maintenance' },
    { value: 'Compliance', icon: 'ShieldCheck', labelKey: 'todos.type_compliance' },
    { value: 'Other', icon: 'CircleHelp', labelKey: 'todos.type_other' },
];

export const TODO_CARD_ICON_OPTIONS = [
    'CheckSquare',
    'Bug',
    'Zap',
    'BookOpen',
    'Layers',
    'Home',
    'Building2',
    'Key',
    'DoorOpen',
    'MapPin',
    'Eye',
    'HandCoins',
    'Hammer',
    'Wrench',
    'ShieldCheck',
    'CircleHelp',
    'Star',
    'Flag',
    'Calendar',
    'Phone',
    'Mail',
    'Users',
    'User',
    'Car',
    'Camera',
    'ClipboardList',
    'FileText',
    'Tag',
    'Sparkles',
    'AlertTriangle',
    'TreePine',
    'Briefcase',
    'ClipboardCheck',
] as const;

export type TodoCardIcon = (typeof TODO_CARD_ICON_OPTIONS)[number];

const TYPE_ICON_MAP = Object.fromEntries(
    TODO_TYPE_OPTIONS.map((option) => [option.value, option.icon]),
) as Record<TodoType, string>;

export function getTypeIcon(type: string): string {
    return TYPE_ICON_MAP[type as TodoType] || 'Circle';
}

export function getTodoDisplayIcon(todo: { type?: string | null; card_icon?: string | null }): string {
    if (todo.card_icon) {
        return todo.card_icon;
    }

    if (todo.type) {
        return getTypeIcon(todo.type);
    }

    return 'Circle';
}
