# 🗂️ Supabase Storage Setup for Company Logos

## 📋 Required Environment Variables

Add these environment variables to your Heroku app and local `.env` file:

```bash
# Supabase Storage Configuration
SUPABASE_ACCESS_KEY_ID=your_supabase_project_id
SUPABASE_SECRET_ACCESS_KEY=your_supabase_anon_key
SUPABASE_DEFAULT_REGION=us-east-1
SUPABASE_BUCKET=taskit
SUPABASE_URL=https://your-project-id.supabase.co/storage/v1/object/public/taskit
SUPABASE_ENDPOINT=https://your-project-id.supabase.co/storage/v1/s3
```

## 🔧 Supabase Setup Steps

### 1. Create Storage Bucket
1. Go to your Supabase project dashboard
2. Navigate to **Storage** → **Buckets**
3. Create a new bucket named `taskit`
4. Set the bucket to **Public** (for logo access)

### 2. Create Logos Folder
1. In the `taskit` bucket, create a folder named `logos`
2. This is where company logos will be uploaded

### 3. Set Bucket Policies
Add this RLS policy to allow uploads:

```sql
-- Allow authenticated users to upload logos
CREATE POLICY "Allow logo uploads for authenticated users" ON storage.objects
FOR INSERT
TO authenticated
WITH CHECK (bucket_id = 'taskit' AND (storage.foldername(name))[1] = 'logos');

-- Allow public read access to logos
CREATE POLICY "Allow public logo access" ON storage.objects
FOR SELECT
TO public
USING (bucket_id = 'taskit' AND (storage.foldername(name))[1] = 'logos');

-- Allow users to delete their own logos
CREATE POLICY "Allow logo deletion for authenticated users" ON storage.objects
FOR DELETE
TO authenticated
USING (bucket_id = 'taskit' AND (storage.foldername(name))[1] = 'logos');
```

### 4. Get API Keys
1. Go to **Settings** → **API**
2. Copy your:
   - **Project URL** (for `SUPABASE_ACCESS_KEY_ID` - use just the project ID part)
   - **anon public key** (for `SUPABASE_SECRET_ACCESS_KEY`)

### 5. Update Heroku Config
```bash
heroku config:set SUPABASE_ACCESS_KEY_ID=your_project_id -a task-it
heroku config:set SUPABASE_SECRET_ACCESS_KEY=your_anon_key -a task-it
heroku config:set SUPABASE_BUCKET=taskit -a task-it
heroku config:set SUPABASE_URL=https://your-project-id.supabase.co/storage/v1/object/public/taskit -a task-it
heroku config:set SUPABASE_ENDPOINT=https://your-project-id.supabase.co/storage/v1/s3 -a task-it
```

## 🎯 Feature Overview

### ✅ What's Implemented:
- **Company Logo Upload** for MIDI/MAXI plans only
- **File Validation**: PNG, JPG, JPEG, SVG (2MB max)
- **Auto Filename**: `CompanyName_CompanyCode.ext`
- **Logo Display**: Replaces TaskIT branding when custom logo exists
- **Logo Management**: Upload, preview, and remove functionality
- **Settings Integration**: Company Logo section in user settings

### 🔐 Access Control:
- Only **paid subscribers** (MIDI/MAXI) can upload logos
- FREE plan users see upgrade prompt
- Logo height restricted to 46px max (32px ideal)

### 📁 File Structure:
- Logos stored at: `supabase://taskit/logos/CompanyName_CompanyCode.ext`
- Public access for logo display
- Secure upload with authentication

## 🚀 Usage

1. **Upgrade to MIDI/MAXI** plan
2. **Go to Settings** → **Company Logo**
3. **Upload logo** (PNG, JPG, JPEG, SVG)
4. **Logo appears** in sidebar and header
5. **Switch back** to default TaskIT logo anytime

## 🐛 Troubleshooting

If logo uploads fail:
1. Check Supabase bucket exists and is public
2. Verify environment variables are set correctly
3. Check storage policies allow uploads
4. Ensure file meets size/type requirements
