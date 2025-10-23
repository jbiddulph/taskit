import { ref } from 'vue';

export interface GuestUser {
  id: number;
  email: string;
  name: string;
  company_id: number;
  invited_by: number;
  invited_at: string;
  expires_at?: string;
  is_active: boolean;
  permissions: GuestPermission[];
  projects: number[]; // Project IDs they have access to
}

export interface GuestPermission {
  id: number;
  name: string;
  description: string;
  actions: string[]; // e.g., ['view', 'comment', 'edit']
}

export interface GuestInvitation {
  id: number;
  email: string;
  name?: string;
  company_id: number;
  invited_by: number;
  invited_at: string;
  expires_at?: string;
  token: string;
  status: 'pending' | 'accepted' | 'expired' | 'revoked';
  permissions: GuestPermission[];
  projects: number[];
  message?: string;
}

class GuestAccessService {
  private guests = ref<GuestUser[]>([]);
  private invitations = ref<GuestInvitation[]>([]);
  private permissions = ref<GuestPermission[]>([]);

  // Get all guests for a company
  getCompanyGuests(companyId: number): GuestUser[] {
    return this.guests.value.filter(guest => guest.company_id === companyId);
  }

  // Get active guests
  getActiveGuests(companyId: number): GuestUser[] {
    return this.guests.value.filter(guest => 
      guest.company_id === companyId && 
      guest.is_active &&
      (!guest.expires_at || new Date(guest.expires_at) > new Date())
    );
  }

  // Get guest by ID
  getGuest(guestId: number): GuestUser | null {
    return this.guests.value.find(guest => guest.id === guestId) || null;
  }

  // Check if user is a guest
  isGuest(userId: number): boolean {
    return this.guests.value.some(guest => guest.id === userId);
  }

  // Check if guest has access to project
  hasProjectAccess(guestId: number, projectId: number): boolean {
    const guest = this.getGuest(guestId);
    return guest ? guest.projects.includes(projectId) : false;
  }

  // Check if guest has permission
  hasPermission(guestId: number, permission: string): boolean {
    const guest = this.getGuest(guestId);
    if (!guest) return false;

    return guest.permissions.some(p => p.actions.includes(permission));
  }

  // Create guest invitation
  async createInvitation(invitation: Omit<GuestInvitation, 'id' | 'invited_at' | 'token' | 'status'>): Promise<GuestInvitation> {
    const newInvitation: GuestInvitation = {
      ...invitation,
      id: Date.now(), // In real app, this would come from API
      invited_at: new Date().toISOString(),
      token: this.generateToken(),
      status: 'pending'
    };

    this.invitations.value.push(newInvitation);
    return newInvitation;
  }

  // Accept invitation
  async acceptInvitation(token: string, userData: { name: string; email: string }): Promise<GuestUser | null> {
    const invitation = this.invitations.value.find(inv => inv.token === token);
    if (!invitation || invitation.status !== 'pending') return null;

    // Check if invitation is expired
    if (invitation.expires_at && new Date(invitation.expires_at) < new Date()) {
      invitation.status = 'expired';
      return null;
    }

    const guest: GuestUser = {
      id: Date.now(), // In real app, this would come from API
      email: userData.email,
      name: userData.name,
      company_id: invitation.company_id,
      invited_by: invitation.invited_by,
      invited_at: invitation.invited_at,
      expires_at: invitation.expires_at,
      is_active: true,
      permissions: invitation.permissions,
      projects: invitation.projects
    };

    this.guests.value.push(guest);
    invitation.status = 'accepted';
    return guest;
  }

  // Revoke guest access
  async revokeGuestAccess(guestId: number): Promise<boolean> {
    const guest = this.getGuest(guestId);
    if (!guest) return false;

    guest.is_active = false;
    return true;
  }

  // Update guest permissions
  async updateGuestPermissions(guestId: number, permissions: GuestPermission[]): Promise<boolean> {
    const guest = this.getGuest(guestId);
    if (!guest) return false;

    guest.permissions = permissions;
    return true;
  }

  // Update guest projects
  async updateGuestProjects(guestId: number, projects: number[]): Promise<boolean> {
    const guest = this.getGuest(guestId);
    if (!guest) return false;

    guest.projects = projects;
    return true;
  }

  // Get available permissions
  getAvailablePermissions(): GuestPermission[] {
    return this.permissions.value;
  }

  // Get pending invitations
  getPendingInvitations(companyId: number): GuestInvitation[] {
    return this.invitations.value.filter(inv => 
      inv.company_id === companyId && inv.status === 'pending'
    );
  }

  // Resend invitation
  async resendInvitation(invitationId: number): Promise<boolean> {
    const invitation = this.invitations.value.find(inv => inv.id === invitationId);
    if (!invitation) return false;

    // In real app, this would send an email
    console.log('Resending invitation to:', invitation.email);
    return true;
  }

  // Cancel invitation
  async cancelInvitation(invitationId: number): Promise<boolean> {
    const invitation = this.invitations.value.find(inv => inv.id === invitationId);
    if (!invitation) return false;

    invitation.status = 'revoked';
    return true;
  }

  // Generate secure token
  private generateToken(): string {
    return Math.random().toString(36).substring(2) + Date.now().toString(36);
  }

  // Set guests (for loading from API)
  setGuests(guests: GuestUser[]) {
    this.guests.value = guests;
  }

  // Set invitations (for loading from API)
  setInvitations(invitations: GuestInvitation[]) {
    this.invitations.value = invitations;
  }

  // Set permissions (for loading from API)
  setPermissions(permissions: GuestPermission[]) {
    this.permissions.value = permissions;
  }

  // Get guest statistics
  getGuestStats(companyId: number): {
    total: number;
    active: number;
    pending: number;
    expired: number;
  } {
    const companyGuests = this.getCompanyGuests(companyId);
    const companyInvitations = this.invitations.value.filter(inv => inv.company_id === companyId);

    return {
      total: companyGuests.length,
      active: companyGuests.filter(g => g.is_active).length,
      pending: companyInvitations.filter(inv => inv.status === 'pending').length,
      expired: companyInvitations.filter(inv => inv.status === 'expired').length
    };
  }
}

export const guestAccessService = new GuestAccessService();
