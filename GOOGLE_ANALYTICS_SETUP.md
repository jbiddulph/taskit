# Google Analytics Integration for ZapTasks

Google Analytics has been successfully integrated into your ZapTasks project! This document explains how to configure and use the analytics features.

## Quick Setup

1. **Set your Google Analytics Tracking ID in your environment file:**
   ```bash
   # Add this to your .env file
   GOOGLE_ANALYTICS_TRACKING_ID=G-Z1ZXQ6Z1M9
   ```

2. **Restart your development server:**
   ```bash
   npm run dev
   ```

That's it! Google Analytics is now tracking your ZapTasks application.

## What's Being Tracked

### Automatic Tracking
- **Page Views**: All navigation between pages using Inertia.js
- **User Sessions**: Standard Google Analytics session tracking
- **Device Information**: Browser, OS, screen resolution, etc.

### Custom Events
The following custom events are automatically tracked:

#### Todo Events
- **todo_created**: When a new todo is created
  - `project_id`, `project_name`, `priority`, `type`, `has_due_date`, `has_assignee`
  
- **todo_updated**: When a todo is edited
  - `todo_id`, `project_id`, `project_name`, `priority`, `type`, `has_due_date`, `has_assignee`
  
- **todo_deleted**: When a todo is deleted
  - `todo_id`, `project_id`, `project_name`, `priority`, `type`, `status`
  
- **todo_status_changed**: When a todo is moved between columns (drag & drop)
  - `todo_id`, `project_id`, `project_name`, `old_status`, `new_status`, `priority`, `type`

#### Project Events
- **project_created**: When a new project is created
  - `project_id`, `project_name`, `has_description`, `has_custom_key`, `color`

## Files Modified

### Backend Configuration
- `config/services.php` - Added Google Analytics configuration
- `.env.example` - Added environment variable example
- `resources/views/app.blade.php` - Added Google Analytics script tags

### Frontend Integration
- `resources/js/app.ts` - Added page view tracking for Inertia navigation
- `resources/js/composables/useAnalytics.ts` - Created analytics composable
- `resources/js/components/TodoForm.vue` - Added todo creation/update tracking
- `resources/js/components/TodoBoard.vue` - Added todo deletion, status change, and project creation tracking

## Usage in Custom Components

If you want to add analytics tracking to other components, import and use the analytics composable:

```vue
<script setup lang="ts">
import { useAnalytics } from '../composables/useAnalytics';

const { trackEvent, trackTodoEvent, trackProjectEvent, trackEngagement } = useAnalytics();

// Track a custom event
const handleButtonClick = () => {
  trackEvent('button_clicked', {
    button_name: 'export_data',
    page: 'dashboard'
  });
};

// Track todo-specific events
const handleTodoAction = () => {
  trackTodoEvent('completed', {
    todo_id: 123,
    project_id: 456,
    completion_time: Date.now()
  });
};

// Track engagement events
const handleFeatureUsage = () => {
  trackEngagement('feature_used', {
    feature_name: 'calendar_view',
    user_type: 'premium'
  });
};
</script>
```

## Available Analytics Methods

### `useAnalytics()` Composable

- `trackPageView(title?, location?)` - Track page views (automatically called)
- `trackEvent(eventName, parameters?)` - Track custom events
- `trackTodoEvent(action, todoData?)` - Track todo-related events
- `trackProjectEvent(action, projectData?)` - Track project-related events
- `trackEngagement(action, data?)` - Track user engagement events
- `setUserProperties(properties)` - Set user properties for analytics

## Viewing Analytics Data

1. Go to your [Google Analytics dashboard](https://analytics.google.com/)
2. Select your property (G-Z1ZXQ6Z1M9)
3. Navigate to **Reports** > **Events** to see custom events
4. Use **Realtime** reports to see live activity
5. Create custom reports and dashboards as needed

## Privacy Considerations

- This integration respects user privacy and follows Google Analytics best practices
- No personally identifiable information (PII) is tracked
- Users can disable analytics by blocking the Google Analytics script in their browser
- Consider adding a privacy policy and cookie notice if required by your jurisdiction

## Troubleshooting

### Analytics Not Working?
1. Check that `GOOGLE_ANALYTICS_TRACKING_ID` is set in your `.env` file
2. Verify the tracking ID format (should start with 'G-')
3. Check browser console for JavaScript errors
4. Ensure you're not using an ad blocker that blocks analytics
5. Check Google Analytics Real-time reports to see if data is coming through

### Custom Events Not Appearing?
- Custom events may take 24-48 hours to appear in standard reports
- Use Real-time reports to see events immediately
- Check browser console for any JavaScript errors

## Support

If you need help with Google Analytics integration:
1. Check the [Google Analytics documentation](https://developers.google.com/analytics/devguides/collection/ga4)
2. Review the analytics composable code in `resources/js/composables/useAnalytics.ts`
3. Test events using browser developer tools and the Real-time reports

Happy tracking! 📊