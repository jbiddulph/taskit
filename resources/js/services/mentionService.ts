import { ref, computed } from 'vue';

export interface MentionUser {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

export interface Mention {
  id: string;
  userId: number;
  userName: string;
  position: number;
  length: number;
}

class MentionService {
  private users = ref<MentionUser[]>([]);
  private currentMentions = ref<Mention[]>([]);

  // Get all users for mentions
  getUsers() {
    return this.users.value;
  }

  // Set users (called when loading company users)
  setUsers(users: MentionUser[]) {
    this.users.value = users;
  }

  // Search users by query
  searchUsers(query: string): MentionUser[] {
    if (!query) return this.users.value;
    
    const lowercaseQuery = query.toLowerCase();
    return this.users.value.filter(user => 
      user.name.toLowerCase().includes(lowercaseQuery) ||
      user.email.toLowerCase().includes(lowercaseQuery)
    );
  }

  // Parse mentions from text
  parseMentions(text: string): Mention[] {
    const mentions: Mention[] = [];
    const mentionRegex = /@([^\s@]+)/gu;
    let match;

    while ((match = mentionRegex.exec(text)) !== null) {
      const userName = match[1];
      const user = this.users.value.find(u => 
        u.name.toLowerCase().replace(/\s+/g, '') === userName.toLowerCase()
      );

      if (user) {
        mentions.push({
          id: `${user.id}-${match.index}`,
          userId: user.id,
          userName: user.name,
          position: match.index,
          length: match[0].length
        });
      }
    }

    return mentions;
  }

  // Get mention suggestions based on cursor position
  getSuggestions(text: string, cursorPosition: number): MentionUser[] {
    const beforeCursor = text.substring(0, cursorPosition);
    const mentionMatch = beforeCursor.match(/@([^\s@]*)$/u);
    
    if (!mentionMatch) return [];

    const query = mentionMatch[1];
    return this.searchUsers(query);
  }

  // Check if cursor is in a mention
  isInMention(text: string, cursorPosition: number): boolean {
    const beforeCursor = text.substring(0, cursorPosition);
    return /@[^\s@]*$/u.test(beforeCursor);
  }

  // Replace mention with formatted text
  formatMentions(text: string): string {
    return text.replace(/@([^\s@]+)/gu, (match, userName) => {
      const user = this.users.value.find(u => 
        u.name.toLowerCase().replace(/\s+/g, '') === userName.toLowerCase()
      );
      
      if (user) {
        return `<span class="mention" data-user-id="${user.id}">@${user.name}</span>`;
      }
      
      return match;
    });
  }

  // Extract plain text mentions for notifications
  extractMentions(text: string): { userId: number; userName: string }[] {
    const mentions: { userId: number; userName: string }[] = [];
    const mentionRegex = /@([^\s@]+)/gu;
    let match;

    while ((match = mentionRegex.exec(text)) !== null) {
      const userName = match[1];
      const user = this.users.value.find(u => 
        u.name.toLowerCase().replace(/\s+/g, '') === userName.toLowerCase()
      );

      if (user) {
        mentions.push({
          userId: user.id,
          userName: user.name
        });
      }
    }

    return mentions;
  }
}

export const mentionService = new MentionService();
