// Role Service
// Moodle API functions: core_role_*
import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface MoodleRole {
  id: number;
  name: string;
  shortname: string;
  description: string;
  sortorder: number;
  archetype: string;
}

export interface RoleAssignment {
  roleid: number;
  userid: number;
  contextid: number;
  contextlevel: number;
  component: string;
  itemid: number;
}

export class RoleService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Assign roles to users
   * Uses: core_role_assign_roles
   */
  async assignRoles(
    assignments: Array<{
      roleid: number;
      userid: number;
      contextid?: number;
      contextlevel?: string;
      instanceid?: number;
    }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_role_assign_roles',
      { assignments }
    );
    return response.data;
  }

  /**
   * Assign a single role
   */
  async assignRole(
    roleId: number,
    userId: number,
    contextId: number
  ): Promise<null> {
    return this.assignRoles([{ roleid: roleId, userid: userId, contextid: contextId }]);
  }

  /**
   * Unassign roles from users
   * Uses: core_role_unassign_roles
   */
  async unassignRoles(
    unassignments: Array<{
      roleid: number;
      userid: number;
      contextid?: number;
      contextlevel?: string;
      instanceid?: number;
    }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_role_unassign_roles',
      { unassignments }
    );
    return response.data;
  }

  /**
   * Unassign a single role
   */
  async unassignRole(
    roleId: number,
    userId: number,
    contextId: number
  ): Promise<null> {
    return this.unassignRoles([{ roleid: roleId, userid: userId, contextid: contextId }]);
  }
}

// Singleton instance
export const roleService = new RoleService();

export default roleService;
