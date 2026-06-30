export function useFormFieldClasses() {
    const label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    const input =
        'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm';
    const select = input;
    const textarea = input;
    const error = 'text-red-500 text-sm mt-1';
    const sectionTitle = 'text-lg font-semibold text-gray-900 dark:text-gray-100';
    const sectionSubtitle = 'text-sm text-gray-600 dark:text-gray-400';
    const card = 'rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-5';
    const btnPrimary =
        'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 disabled:opacity-50';
    const btnSecondary =
        'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors';
    const btnDanger =
        'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium border border-red-300 dark:border-red-800 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors disabled:opacity-50';
    const btnDangerSm =
        'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium border border-red-200 dark:border-red-900 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30';

    return { label, input, select, textarea, error, sectionTitle, sectionSubtitle, card, btnPrimary, btnSecondary, btnDanger, btnDangerSm };
}
