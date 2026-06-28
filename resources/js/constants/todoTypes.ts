export interface TodoTypeOption {
    value: string;
    icon: string;
    label?: string;
    labelKey?: string;
}

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
    'Package',
    'HardHat',
    'Truck',
    'Pill',
    'GraduationCap',
    'ChefHat',
    'ConciergeBell',
    'LayoutGrid',
    'Megaphone',
    'Palette',
    'BarChart3',
    'Calculator',
    'Search',
    'CheckCircle',
    'Heart',
    'HeartHandshake',
    'Factory',
    'ShoppingCart',
    'FolderKanban',
    'PenTool',
] as const;

export type TodoCardIcon = (typeof TODO_CARD_ICON_OPTIONS)[number];

const FALLBACK_TYPE_ICONS: Record<string, string> = {
    Task: 'CheckSquare',
    Other: 'CircleHelp',
    Bug: 'Bug',
    Feature: 'Zap',
    Story: 'BookOpen',
    Epic: 'Layers',
    Property: 'Home',
    Lead: 'UserPlus',
    Viewing: 'Eye',
    Offer: 'HandCoins',
    Maintenance: 'Wrench',
    Compliance: 'ShieldCheck',
};

export function getTypeIcon(type: string, iconMap?: Record<string, string>): string {
    if (iconMap?.[type]) {
        return iconMap[type];
    }

    return FALLBACK_TYPE_ICONS[type] ?? 'Circle';
}

export function getTodoDisplayIcon(
    todo: { type?: string | null; card_icon?: string | null },
    iconMap?: Record<string, string>,
): string {
    if (todo.card_icon) {
        return todo.card_icon;
    }

    if (todo.type) {
        return getTypeIcon(todo.type, iconMap);
    }

    return 'Circle';
}
