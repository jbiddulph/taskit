export interface MeetingNotesTemplate {
    id: string;
    label: string;
    hint: string;
    script: string;
}

export const MEETING_NOTES_TEMPLATES: MeetingNotesTemplate[] = [
    {
        id: 'standup',
        label: 'Daily standup',
        hint: 'What I did / doing / blockers',
        script: `Daily standup update.
Yesterday I finished the API integration and reviewed the pull request.
Today I am working on the dashboard filters and map view.
Blockers: waiting on design feedback for the mobile layout.
Create 3 todos from this standup — one for each item above, medium priority, assign to me.`,
    },
    {
        id: 'general',
        label: 'General meeting',
        hint: 'Decisions and action items',
        script: `Meeting notes for the product sync.
Key decision: we launch the beta next Monday.
Action items: Sarah will update the landing page by Friday, high priority.
John will schedule user interviews for next week.
Create 2 tasks for marketing, put them in progress, due this Sunday.`,
    },
];
