-- SQL to update company ID 27 from MAXI to LTD_AGENCY in Supabase
-- Run this directly in Supabase SQL Editor

UPDATE taskit_companies 
SET 
    subscription_type = 'LTD_AGENCY',
    subscription_status = 'active',
    stripe_subscription_id = NULL,
    subscription_ends_at = NULL,
    scheduled_subscription_type = NULL,
    scheduled_change_date = NULL,
    updated_at = NOW()
WHERE id = 27;

-- Verify the update
SELECT 
    id, 
    name, 
    code, 
    subscription_type, 
    subscription_status,
    stripe_subscription_id,
    subscription_ends_at
FROM taskit_companies 
WHERE id = 27;

