import { ref, onMounted } from 'vue';

export interface CompanyUser {
  id: number;
  name: string;
  email: string;
}

export function useCompanyUsers() {
  const users = ref<CompanyUser[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const loadUsers = async () => {
    try {
      loading.value = true;
      error.value = null;
      
      const response = await fetch('/api/users', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      
      if (response.ok) {
        const userData = await response.json();
        users.value = userData;
      } else {
        error.value = 'Failed to load company users';
      }
    } catch (err) {
      error.value = 'Failed to load company users';
      console.error('Failed to load company users:', err);
    } finally {
      loading.value = false;
    }
  };

  const getUserById = (id: number): CompanyUser | undefined => {
    return users.value.find(user => user.id === id);
  };

  const getUserByName = (name: string): CompanyUser | undefined => {
    return users.value.find(user => 
      user.name.toLowerCase().includes(name.toLowerCase())
    );
  };

  const searchUsers = (query: string): CompanyUser[] => {
    if (!query) return users.value;
    
    const lowercaseQuery = query.toLowerCase();
    return users.value.filter(user => 
      user.name.toLowerCase().includes(lowercaseQuery) ||
      user.email.toLowerCase().includes(lowercaseQuery)
    );
  };

  // Auto-load users on mount
  onMounted(() => {
    loadUsers();
  });

  return {
    users,
    loading,
    error,
    loadUsers,
    getUserById,
    getUserByName,
    searchUsers
  };
}
