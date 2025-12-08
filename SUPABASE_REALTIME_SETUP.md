# Supabase Realtime Setup Guide

This guide explains how to enable Supabase Realtime for the `taskit_notifications` table to ensure mention notifications work in real-time.

## Prerequisites

- Access to Supabase Dashboard
- Admin access to your Supabase project

## Steps to Enable Realtime for Notifications

### 1. Enable Replication for the Table

1. Go to your Supabase Dashboard
2. Navigate to **Database** → **Replication**
3. Find `taskit_notifications` in the list
4. Toggle the switch to enable replication for this table
5. Ensure the publication is set to `supabase_realtime`

### 2. Configure Row Level Security (RLS) Policies

1. Go to **Database** → **Tables** → `taskit_notifications`
2. Click on **RLS** tab
3. Ensure RLS is enabled for the table
4. Add the following policies if they don't exist:

```sql
-- Allow users to SELECT their own notifications
CREATE POLICY "Users can view their own notifications"
ON taskit_notifications
FOR SELECT
USING (auth.uid()::text = user_id::text);

-- Allow service role to INSERT notifications (for Laravel backend)
CREATE POLICY "Service role can insert notifications"
ON taskit_notifications
FOR INSERT
WITH CHECK (true);
```

### 3. Verify Publication Configuration

Run this SQL query in the Supabase SQL Editor to ensure the table is in the publication:

```sql
-- Check if table is in publication
SELECT * FROM pg_publication_tables 
WHERE pubname = 'supabase_realtime' 
AND tablename = 'taskit_notifications';

-- If not found, add it:
ALTER PUBLICATION supabase_realtime ADD TABLE taskit_notifications;
```

### 4. Test the Setup

1. Open your application in the browser
2. Open browser console (F12)
3. Look for: `📢 Notification subscription successful`
4. Have another user mention you in a comment
5. Check console for: `📢 Notification INSERT event received`

## Troubleshooting

### Notifications Not Appearing in Real-time

1. **Check Realtime Status:**
   - Verify replication is enabled in Supabase Dashboard
   - Check browser console for subscription status
   - Look for any error messages

2. **Check RLS Policies:**
   - Ensure policies allow users to see their notifications
   - Test with: `SELECT * FROM taskit_notifications WHERE user_id = YOUR_USER_ID;`

3. **Check Filter Matching:**
   - The subscription filters by `user_id=eq.{userId}`
   - Ensure `user_id` is stored as an integer in the database
   - Check console logs for filter matching

4. **Fallback Subscription:**
   - The app now includes a fallback subscription that listens to all notifications
   - This will show `🧪 FALLBACK:` logs if the filtered subscription fails
   - The fallback filters on the client side as a backup

### Common Issues

**Issue: "CHANNEL_ERROR" status**
- Solution: Check if replication is enabled for the table
- Verify RLS policies allow the operation

**Issue: Notifications appear but are delayed**
- Solution: This might be a network issue, not a Realtime issue
- Check Supabase dashboard for connection status

**Issue: Test subscription works but filtered doesn't**
- Solution: Check the user_id data type matches
- Ensure filter syntax is correct: `user_id=eq.123` (not `user_id='123'`)

## Verification Commands

Run these SQL queries to verify setup:

```sql
-- Check if replication is enabled
SELECT schemaname, tablename 
FROM pg_publication_tables 
WHERE pubname = 'supabase_realtime' 
AND tablename = 'taskit_notifications';

-- Check RLS policies
SELECT * FROM pg_policies 
WHERE tablename = 'taskit_notifications';

-- Test notification creation
INSERT INTO taskit_notifications (user_id, type, title, message, created_at, updated_at)
VALUES (1, 'mention', 'Test', 'Test notification', NOW(), NOW());

-- Verify notification was created
SELECT * FROM taskit_notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1;
```

## Notes

- The application now includes a fallback mechanism that listens to all notifications and filters client-side
- This ensures notifications work even if the filtered subscription has issues
- Both subscriptions run simultaneously for redundancy
- The fallback logs will show `🧪 FALLBACK:` prefix in console

## Support

If you continue to experience issues after following this guide:

1. Check Supabase status page
2. Review application logs
3. Check browser console for detailed error messages
4. Verify all environment variables are set correctly (`VITE_SUPABASE_URL`, `VITE_SUPABASE_ANON_KEY`)

