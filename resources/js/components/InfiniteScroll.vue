<template>
  <div class="infinite-scroll-container" ref="container">
    <div class="infinite-scroll-content">
      <slot :items="items" :loading="loading" :hasMore="hasMore" />
    </div>
    
    <!-- Loading indicator -->
    <div v-if="loading" class="infinite-scroll-loading">
      <div class="flex items-center justify-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Loading...</span>
      </div>
    </div>
    
    <!-- End of results indicator -->
    <div v-if="!hasMore && !loading && items.length > 0" class="infinite-scroll-end">
      <div class="text-center py-4 text-gray-500 text-sm">
        <Icon name="CheckCircle" class="w-5 h-5 mx-auto mb-2" />
        <span>You've reached the end</span>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-if="!loading && items.length === 0" class="infinite-scroll-empty">
      <div class="text-center py-8 text-gray-500">
        <Icon name="Inbox" class="w-12 h-12 mx-auto mb-4 text-gray-300" />
        <p class="text-lg font-medium">No items found</p>
        <p class="text-sm">{{ emptyMessage }}</p>
      </div>
    </div>
    
    <!-- Intersection observer target -->
    <div ref="observerTarget" class="infinite-scroll-observer"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import Icon from '@/components/Icon.vue';

interface Props {
  loadMore: () => Promise<void>;
  items: any[];
  loading: boolean;
  hasMore: boolean;
  emptyMessage?: string;
  threshold?: number;
  rootMargin?: string;
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'No items to display',
  threshold: 0.1,
  rootMargin: '100px'
});

const emit = defineEmits<{
  loadMore: [];
}>();

const container = ref<HTMLElement>();
const observerTarget = ref<HTMLElement>();
const observer = ref<IntersectionObserver>();

// Initialize intersection observer
const initObserver = () => {
  if (!observerTarget.value) return;

  observer.value = new IntersectionObserver(
    (entries) => {
      const entry = entries[0];
      if (entry.isIntersecting && props.hasMore && !props.loading) {
        emit('loadMore');
      }
    },
    {
      root: container.value,
      rootMargin: props.rootMargin,
      threshold: props.threshold
    }
  );

  observer.value.observe(observerTarget.value);
};

// Clean up observer
const cleanupObserver = () => {
  if (observer.value) {
    observer.value.disconnect();
    observer.value = undefined;
  }
};

// Watch for changes in hasMore or loading state
watch([() => props.hasMore, () => props.loading], () => {
  nextTick(() => {
    if (observer.value && observerTarget.value) {
      observer.value.unobserve(observerTarget.value);
      observer.value.observe(observerTarget.value);
    }
  });
});

onMounted(() => {
  nextTick(() => {
    initObserver();
  });
});

onUnmounted(() => {
  cleanupObserver();
});
</script>

<style scoped>
.infinite-scroll-container {
  position: relative;
  min-height: 200px;
}

.infinite-scroll-content {
  min-height: 100px;
}

.infinite-scroll-loading {
  position: sticky;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
  border-top: 1px solid #e5e7eb;
}

.infinite-scroll-end {
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.infinite-scroll-empty {
  padding: 2rem;
}

.infinite-scroll-observer {
  height: 1px;
  width: 100%;
}
</style>
