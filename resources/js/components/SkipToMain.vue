<template>
  <a
    href="#main-content"
    class="skip-to-main"
    @click="skipToMain"
  >
    Skip to main content
  </a>
</template>

<script setup lang="ts">
const skipToMain = (e: Event) => {
  e.preventDefault();
  const main = document.getElementById('main-content');
  if (main) {
    main.focus();
    main.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};
</script>

