import { createClient } from '@supabase/supabase-js';

// Prefer build-time Vite env; fallback to runtime window vars injected by Blade
const runtimeUrl = (globalThis as any)?.VITE_SUPABASE_URL as string | undefined;
const runtimeAnon = (globalThis as any)?.VITE_SUPABASE_ANON_KEY as string | undefined;

const supabaseUrl = (import.meta as any)?.env?.VITE_SUPABASE_URL || runtimeUrl;
const supabaseAnonKey = (import.meta as any)?.env?.VITE_SUPABASE_ANON_KEY || runtimeAnon;

export const supabase = createClient(supabaseUrl, supabaseAnonKey);

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


