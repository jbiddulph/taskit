<template>
  <div class="border border-gray-300 dark:border-gray-600 rounded-md">
    <div class="flex gap-2 p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
      <button class="px-2 py-1 text-sm" @click="toggle('bold')"><strong>B</strong></button>
      <button class="px-2 py-1 text-sm italic" @click="toggle('italic')">I</button>
      <button class="px-2 py-1 text-sm underline" @click="toggle('underline')">U</button>
      <button class="px-2 py-1 text-sm" @click="insertBullet">• List</button>
      <button class="px-2 py-1 text-sm" @click="onImage">Image</button>
      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="uploadAndInsert" />
    </div>
    <EditorContent :editor="editor" class="p-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 min-h-[180px]" />
  </div>
  
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { uploadImageToTaskitBucket } from '@/services/supabaseClient';

interface Props {
  modelValue: string;
}
const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [val: string] }>();

const fileInput = ref<HTMLInputElement | null>(null);

const editor = new Editor({
  extensions: [StarterKit, Image],
  content: props.modelValue || '',
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
});

watch(() => props.modelValue, (val) => {
  if (val !== editor.getHTML()) {
    editor.commands.setContent(val || '', false);
  }
});

onBeforeUnmount(() => editor.destroy());

function toggle(cmd: 'bold' | 'italic' | 'underline') {
  const map: any = { bold: 'toggleBold', italic: 'toggleItalic', underline: 'toggleUnderline' };
  (editor as any).chain().focus()[map[cmd]]().run();
}

function insertBullet() {
  (editor as any).chain().focus().toggleBulletList().run();
}

function onImage() {
  fileInput.value?.click();
}

async function uploadAndInsert(e: Event) {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;
  try {
    const url = await uploadImageToTaskitBucket(file);
    (editor as any).chain().focus().setImage({ src: url }).run();
  } catch (err) {
    console.error('Image upload failed', err);
  } finally {
    if (fileInput.value) fileInput.value.value = '';
  }
}
</script>

<style scoped>
.ProseMirror:focus {
  outline: none;
}
</style>


