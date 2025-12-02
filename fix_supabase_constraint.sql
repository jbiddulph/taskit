-- Run this in Supabase SQL Editor to fix the subscription_type constraint
-- This will allow all plan types including LTD plans

-- Step 1: Drop the old constraint
ALTER TABLE taskit_companies 
DROP CONSTRAINT IF EXISTS taskit_companies_subscription_type_check;

-- Step 2: Add new constraint with all plan types
ALTER TABLE taskit_companies 
ADD CONSTRAINT taskit_companies_subscription_type_check 
CHECK (subscription_type IN (
    'FREE', 
    'MIDI', 
    'MAXI', 
    'BUSINESS', 
    'LTD_SOLO', 
    'LTD_TEAM', 
    'LTD_AGENCY', 
    'LTD_BUSINESS'
));

-- Step 3: Verify the constraint was updated
SELECT 
    conname, 
    pg_get_constraintdef(oid) as definition
FROM pg_constraint 
WHERE conrelid = 'taskit_companies'::regclass 
AND conname = 'taskit_companies_subscription_type_check';

-- Step 4: Now update company ID 27 to LTD_AGENCY
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

-- Step 5: Verify the update
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

