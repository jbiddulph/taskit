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


