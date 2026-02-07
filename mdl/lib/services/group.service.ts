// Group Service
// Moodle API functions: core_group_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface MoodleGroup {
  id: number;
  courseid: number;
  name: string;
  description: string;
  descriptionformat: number;
  enrolmentkey: string;
  idnumber: string;
  timecreated: number;
  timemodified: number;
}

export interface MoodleGrouping {
  id: number;
  courseid: number;
  name: string;
  description: string;
  descriptionformat: number;
  idnumber: string;
  timecreated: number;
  timemodified: number;
}

export interface GroupMember {
  userid: number;
  groupid: number;
}

export interface CreateGroupParams {
  courseid: number;
  name: string;
  description?: string;
  descriptionformat?: number;
  enrolmentkey?: string;
  idnumber?: string;
}

export interface CreateGroupingParams {
  courseid: number;
  name: string;
  description?: string;
  descriptionformat?: number;
  idnumber?: string;
}

export class GroupService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get groups for a course
   * Uses: core_group_get_course_groups
   */
  async getCourseGroups(courseId: number): Promise<MoodleGroup[]> {
    const response = await this.callWebService<MoodleGroup[]>(
      'core_group_get_course_groups',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get a single group
   * Uses: core_group_get_groups
   */
  async getGroups(groupIds: number[]): Promise<MoodleGroup[]> {
    const response = await this.callWebService<MoodleGroup[]>(
      'core_group_get_groups',
      { groupids: groupIds }
    );
    return response.data;
  }

  /**
   * Get group by ID
   */
  async getGroupById(groupId: number): Promise<MoodleGroup | null> {
    const groups = await this.getGroups([groupId]);
    return groups.length > 0 ? groups[0] : null;
  }

  /**
   * Create groups
   * Uses: core_group_create_groups
   */
  async createGroups(groups: CreateGroupParams[]): Promise<MoodleGroup[]> {
    const response = await this.callWebService<MoodleGroup[]>(
      'core_group_create_groups',
      { groups }
    );
    return response.data;
  }

  /**
   * Create a single group
   */
  async createGroup(group: CreateGroupParams): Promise<MoodleGroup> {
    const result = await this.createGroups([group]);
    return result[0];
  }

  /**
   * Update groups
   * Uses: core_group_update_groups
   */
  async updateGroups(
    groups: Array<{ id: number; name?: string; description?: string; descriptionformat?: number; enrolmentkey?: string; idnumber?: string }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_update_groups',
      { groups }
    );
    return response.data;
  }

  /**
   * Delete groups
   * Uses: core_group_delete_groups
   */
  async deleteGroups(groupIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_delete_groups',
      { groupids: groupIds }
    );
    return response.data;
  }

  /**
   * Delete a single group
   */
  async deleteGroup(groupId: number): Promise<null> {
    return this.deleteGroups([groupId]);
  }

  /**
   * Get group members
   * Uses: core_group_get_group_members
   */
  async getGroupMembers(groupIds: number[]): Promise<Array<{ groupid: number; userids: number[] }>> {
    const response = await this.callWebService<Array<{ groupid: number; userids: number[] }>>(
      'core_group_get_group_members',
      { groupids: groupIds }
    );
    return response.data;
  }

  /**
   * Add members to groups
   * Uses: core_group_add_group_members
   */
  async addGroupMembers(members: GroupMember[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_add_group_members',
      { members }
    );
    return response.data;
  }

  /**
   * Add a single member to a group
   */
  async addGroupMember(groupId: number, userId: number): Promise<null> {
    return this.addGroupMembers([{ groupid: groupId, userid: userId }]);
  }

  /**
   * Delete members from groups
   * Uses: core_group_delete_group_members
   */
  async deleteGroupMembers(members: GroupMember[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_delete_group_members',
      { members }
    );
    return response.data;
  }

  /**
   * Remove a single member from a group
   */
  async removeGroupMember(groupId: number, userId: number): Promise<null> {
    return this.deleteGroupMembers([{ groupid: groupId, userid: userId }]);
  }

  /**
   * Get user's groups in a course
   * Uses: core_group_get_course_user_groups
   */
  async getCourseUserGroups(courseId: number, userId: number, groupingId?: number): Promise<{ groups: MoodleGroup[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ groups: MoodleGroup[]; warnings?: unknown[] }>(
      'core_group_get_course_user_groups',
      { courseid: courseId, userid: userId, groupingid: groupingId }
    );
    return response.data;
  }

  /**
   * Get user's groups in multiple courses
   * Uses: core_group_get_activity_allowed_groups
   */
  async getActivityAllowedGroups(cmId: number, userId?: number): Promise<{ groups: MoodleGroup[]; canaccessallgroups: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ groups: MoodleGroup[]; canaccessallgroups: boolean; warnings?: unknown[] }>(
      'core_group_get_activity_allowed_groups',
      { cmid: cmId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get groupings for a course
   * Uses: core_group_get_course_groupings
   */
  async getCourseGroupings(courseId: number): Promise<MoodleGrouping[]> {
    const response = await this.callWebService<MoodleGrouping[]>(
      'core_group_get_course_groupings',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get groupings
   * Uses: core_group_get_groupings
   */
  async getGroupings(groupingIds: number[], returnGroups: boolean = false): Promise<MoodleGrouping[]> {
    const response = await this.callWebService<MoodleGrouping[]>(
      'core_group_get_groupings',
      { groupingids: groupingIds, returngroups: returnGroups }
    );
    return response.data;
  }

  /**
   * Create groupings
   * Uses: core_group_create_groupings
   */
  async createGroupings(groupings: CreateGroupingParams[]): Promise<MoodleGrouping[]> {
    const response = await this.callWebService<MoodleGrouping[]>(
      'core_group_create_groupings',
      { groupings }
    );
    return response.data;
  }

  /**
   * Update groupings
   * Uses: core_group_update_groupings
   */
  async updateGroupings(
    groupings: Array<{ id: number; name?: string; description?: string; descriptionformat?: number; idnumber?: string }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_update_groupings',
      { groupings }
    );
    return response.data;
  }

  /**
   * Delete groupings
   * Uses: core_group_delete_groupings
   */
  async deleteGroupings(groupingIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_delete_groupings',
      { groupingids: groupingIds }
    );
    return response.data;
  }

  /**
   * Assign groups to grouping
   * Uses: core_group_assign_grouping
   */
  async assignGrouping(groupingId: number, groupId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_assign_grouping',
      { groupingid: groupingId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Unassign groups from grouping
   * Uses: core_group_unassign_grouping
   */
  async unassignGrouping(groupingId: number, groupId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_group_unassign_grouping',
      { groupingid: groupingId, groupid: groupId }
    );
    return response.data;
  }
}

// Singleton instance
export const groupService = new GroupService();

export default groupService;
