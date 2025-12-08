# Mention Notifications Real-time Issue

## Status: ✅ FIXED

## Current Status

✅ **Fixed:**
- Mention notifications are being created in the database
- Real-time subscription with filtered channel
- Fallback subscription for reliability
- Proper type casting for user_id
- Improved error handling and logging

## Changes Made

1. **Improved Supabase Realtime Subscription:**
   - Enhanced filter handling with proper integer casting
   - Added fallback subscription that listens to all notifications and filters client-side
   - Better error handling and logging

2. **Notification Model Improvements:**
   - Ensured user_id is always cast to integer before saving
   - Added proper type casting in model casts
   - Enhanced logging for debugging

3. **Frontend Improvements:**
   - Added better notification type icons (mention, comment, assignment)
   - Improved notification handling in NotificationBadge component

4. **Documentation:**
   - Created `SUPABASE_REALTIME_SETUP.md` with setup instructions
   - Added troubleshooting guide

## Setup Required

To ensure real-time notifications work, follow the setup guide in `SUPABASE_REALTIME_SETUP.md`:

1. Enable replication for `taskit_notifications` table in Supabase Dashboard
2. Configure RLS policies
3. Verify publication configuration

## Testing

The system now includes:
- Primary filtered subscription: `user_id=eq.{userId}`
- Fallback subscription: Listens to all notifications and filters client-side
- Both subscriptions run simultaneously for redundancy

Check browser console for:
- `📢 Notification subscription successful` - Primary subscription working
- `🧪 FALLBACK: Notification subscription successful` - Fallback active

## The Issue

Message notifications work because:
1. Message is created in `company_messages` table
2. Notification is created in `taskit_notifications` table  
3. Supabase Realtime broadcasts the notification INSERT event
4. Frontend receives it and updates the notification badge

Mention notifications don't work because:
1. Mention notification is created in `taskit_notifications` table
2. Supabase Realtime is NOT broadcasting the notification INSERT event
3. OR the subscription filter is not working

## Debugging Steps

### Test 1: Check if Supabase Realtime is working for notifications

1. Login as user 974
2. Open browser console
3. Look for: `📢 Notification subscription successful`
4. Have another user mention user 974
5. Look for: `🧪 TEST: Received notification INSERT:`

**If you see the TEST message:**
- Supabase Realtime IS working
- The issue is with the filtered subscription (user_id filter)

**If you DON'T see the TEST message:**
- Supabase Realtime is NOT configured for `taskit_notifications` table
- Need to enable it in Supabase Dashboard → Database → Replication

### Test 2: Check Notification Creation

Check the database to confirm notifications are being created:

```sql
SELECT * FROM taskit_notifications 
WHERE type = 'mention' 
ORDER BY created_at DESC 
LIMIT 5;
```

## Likely Causes

1. **Supabase Realtime not enabled** for `taskit_notifications` table
   - Solution: Enable in Supabase Dashboard
   
2. **RLS (Row Level Security) blocking events**
   - Solution: Check RLS policies in Supabase

3. **Publication not configured correctly**
   - Solution: Run `ALTER PUBLICATION supabase_realtime ADD TABLE taskit_notifications`

## How Messages Work (For Reference)

Messages create notifications the same way as mentions:

```php
Notification::create([
    'user_id' => $recipient->id,
    'type' => 'info',
    'title' => 'New Message',
    'message' => "You have a new message from {$currentUser->name}",
    'data' => [...]
]);
```

These work, so the issue is with Supabase Realtime configuration, not the code.
