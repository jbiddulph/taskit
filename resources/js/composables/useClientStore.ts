import { ref, readonly } from 'vue';

// Global state
const selectedClientId = ref<number | null>(null);

export function useClientStore() {
  const setClientId = (id: number | null) => {
    selectedClientId.value = id;
  };

  return {
    selectedClientId: readonly(selectedClientId),
    setClientId
  };
}
