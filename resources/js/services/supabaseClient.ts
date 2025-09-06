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
  const fileName = `${companyName.replace(/\s+/g, '_')}_${companyCode}.${fileExt}`;
  const filePath = `logos/${fileName}`;

  const { error } = await supabase.storage.from('taskit').upload(filePath, file, {
    cacheControl: '3600',
    upsert: true, // Allow overwriting existing logo
    contentType: file.type,
  });

  if (error) throw error;

  const { data } = supabase.storage.from('taskit').getPublicUrl(filePath);
  return data.publicUrl;
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


