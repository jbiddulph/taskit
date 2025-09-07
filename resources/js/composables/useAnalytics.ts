import { ref, onMounted } from 'vue';

declare global {
    interface Window {
        gtag: (...args: any[]) => void;
        dataLayer: any[];
    }
}

interface AnalyticsEvent {
    action: string;
    category?: string;
    label?: string;
    value?: number;
    custom_parameters?: Record<string, any>;
}

export function useAnalytics() {
    const isInitialized = ref(false);

    onMounted(() => {
        // Check if gtag is available
        if (typeof window !== 'undefined' && window.gtag) {
            isInitialized.value = true;
        }
    });

    /**
     * Track a page view
     */
    const trackPageView = (page_title?: string, page_location?: string) => {
        if (!isInitialized.value || !window.gtag) return;

        const params: Record<string, any> = {};
        
        if (page_title) params.page_title = page_title;
        if (page_location) params.page_location = page_location;

        window.gtag('config', window.gtag, params);
    };

    /**
     * Track a custom event
     */
    const trackEvent = (eventName: string, parameters?: Record<string, any>) => {
        if (!isInitialized.value || !window.gtag) return;

        window.gtag('event', eventName, parameters || {});
    };

    /**
     * Track todo-related events
     */
    const trackTodoEvent = (action: string, todoData?: Record<string, any>) => {
        if (!isInitialized.value || !window.gtag) return;

        const eventParams: Record<string, any> = {
            event_category: 'Todo',
            event_label: action,
        };

        if (todoData) {
            Object.assign(eventParams, todoData);
        }

        window.gtag('event', `todo_${action}`, eventParams);
    };

    /**
     * Track project-related events
     */
    const trackProjectEvent = (action: string, projectData?: Record<string, any>) => {
        if (!isInitialized.value || !window.gtag) return;

        const eventParams: Record<string, any> = {
            event_category: 'Project',
            event_label: action,
        };

        if (projectData) {
            Object.assign(eventParams, projectData);
        }

        window.gtag('event', `project_${action}`, eventParams);
    };

    /**
     * Track user engagement events
     */
    const trackEngagement = (action: string, data?: Record<string, any>) => {
        if (!isInitialized.value || !window.gtag) return;

        const eventParams: Record<string, any> = {
            event_category: 'Engagement',
            event_label: action,
        };

        if (data) {
            Object.assign(eventParams, data);
        }

        window.gtag('event', `engagement_${action}`, eventParams);
    };

    /**
     * Set user properties
     */
    const setUserProperties = (properties: Record<string, any>) => {
        if (!isInitialized.value || !window.gtag) return;

        window.gtag('config', window.gtag, {
            user_properties: properties
        });
    };

    return {
        isInitialized,
        trackPageView,
        trackEvent,
        trackTodoEvent,
        trackProjectEvent,
        trackEngagement,
        setUserProperties,
    };
}