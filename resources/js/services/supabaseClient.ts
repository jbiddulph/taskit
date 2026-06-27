import { createClient } from '@supabase/supabase-js';

function readRuntimeConfig(key: 'VITE_SUPABASE_URL' | 'VITE_SUPABASE_ANON_KEY'): string | undefined {
  if (typeof window === 'undefined') {
    return undefined;
  }

  const value = (window as any)[key];
  return typeof value === 'string' && value.length > 0 ? value : undefined;
}

// Prefer runtime vars injected by Blade (production) over build-time Vite env (often local)
const supabaseUrl = readRuntimeConfig('VITE_SUPABASE_URL') ?? import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = readRuntimeConfig('VITE_SUPABASE_ANON_KEY') ?? import.meta.env.VITE_SUPABASE_ANON_KEY;

export function isSupabaseConfigured(): boolean {
  return Boolean(supabaseUrl && supabaseAnonKey);
}

export function isRealtimeAvailable(): boolean {
  if (!isSupabaseConfigured()) {
    return false;
  }

  const isLocalSupabase =
    supabaseUrl!.includes('127.0.0.1') || supabaseUrl!.includes('localhost');

  if (typeof window !== 'undefined' && isLocalSupabase) {
    const host = window.location.hostname;
    const onLocalApp = host === 'localhost' || host === '127.0.0.1';
    if (!onLocalApp) {
      return false;
    }
  }

  return true;
}

export const supabase = createClient(supabaseUrl ?? '', supabaseAnonKey ?? '');

export async function uploadImageToTaskitBucket(file: File): Promise<string> {
  const fileExt = file.name.split('.').pop();
  const fileName = `${Date.now()}-${Math.random().toString(36).slice(2)}.${fileExt}`;
  const filePath = `todos/${fileName}`;

  const { error } = await supabase.storage.from('taskit').upload(filePath, file, {
    cacheControl: '3600',
    upsert: false,
    contentType: file.type,
  });

  if (error) throw error;

  const { data } = supabase.storage.from('taskit').getPublicUrl(filePath);
  return data.publicUrl;
}

export async function uploadLogoToTaskitBucket(file: File, companyName: string, companyCode: string): Promise<string> {
  const fileExt = file.name.split('.').pop();
  const timestamp = Date.now(); // Add timestamp to make filename unique
  const fileName = `${companyName.replace(/\s+/g, '_')}_${companyCode}_${timestamp}.${fileExt}`;
  const filePath = `logos/${fileName}`;

  // Clean up old logo files before uploading new one
  await cleanupOldLogoFiles(companyName, companyCode);

  const { error } = await supabase.storage.from('taskit').upload(filePath, file, {
    cacheControl: '1', // Short cache to prevent browser caching issues
    upsert: false, // Each logo upload now has unique filename
    contentType: file.type,
  });

  if (error) throw error;

  const { data } = supabase.storage.from('taskit').getPublicUrl(filePath);
  return data.publicUrl;
}

async function cleanupOldLogoFiles(companyName: string, companyCode: string): Promise<void> {
  try {
    const filePattern = `${companyName.replace(/\s+/g, '_')}_${companyCode}`;
    
    // List all files in the logos folder
    const { data: files, error } = await supabase.storage.from('taskit').list('logos');
    
    if (error) {
      console.warn('Failed to list logo files for cleanup:', error);
      return;
    }
    
    if (!files) return;
    
    // Find files that match our company pattern
    const filesToDelete = files.filter(file => 
      file.name.startsWith(filePattern) && 
      /\.(png|jpg|jpeg|svg|gif|webp)$/i.test(file.name)
    );
    
    // Delete each matching file
    for (const file of filesToDelete) {
      const { error: deleteError } = await supabase.storage
        .from('taskit')
        .remove([`logos/${file.name}`]);
        
      if (deleteError) {
        console.warn(`Failed to delete old logo file ${file.name}:`, deleteError);
      } else {
        console.log(`Cleaned up old logo file: ${file.name}`);
      }
    }
  } catch (error) {
    console.warn('Error during logo cleanup:', error);
    // Don't throw - cleanup failures shouldn't prevent new uploads
  }
}

function extractTaskitPathFromPublicUrl(url: string): string | null {
  try {
    const marker = '/storage/v1/object/public/taskit/';
    const idx = url.indexOf(marker);
    if (idx === -1) return null;
    return url.substring(idx + marker.length);
  } catch {
    return null;
  }
}

export async function deletePublicImageUrl(url: string): Promise<void> {
  const path = extractTaskitPathFromPublicUrl(url);
  if (!path) return;
  await supabase.storage.from('taskit').remove([path]);
}

export async function deleteImagesInHtml(html?: string): Promise<void> {
  if (!html) return;
  const matches = html.match(/<img[^>]*src=["']([^"']+)["'][^>]*>/gi) || [];
  const urls = matches.map(tag => {
    const m = tag.match(/src=["']([^"']+)["']/i);
    return m ? m[1] : null;
  }).filter(Boolean) as string[];
  if (urls.length === 0) return;
  await Promise.all(urls.map(u => deletePublicImageUrl(u)));
}


