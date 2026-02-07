// User Management Service
// Moodle API functions: core_user_*

import MoodleApiClient from '@/lib/moodle-api';
import { moodleConfig } from '../config';
import type {
  MoodleUser,
  CreateUserParams,
  UpdateUserParams,
  UserCriteria,
  UserPreference
} from '../types';

export class UserService {
  private client: MoodleApiClient;

  constructor(token: string = moodleConfig.adminToken) {
    this.client = new MoodleApiClient(moodleConfig.publicUrl, token);
  }

  /**
   * Get users by search criteria
   * Uses: core_user_get_users
   */
  async getUsers(criteria: UserCriteria[] = []): Promise<{ users: MoodleUser[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ users: MoodleUser[]; warnings?: unknown[] }>(
      'core_user_get_users',
      { criteria }
    );
    return response;
  }

  /**
   * Get users by field
   * Uses: core_user_get_users_by_field
   */
  async getUsersByField(
    field: 'id' | 'idnumber' | 'username' | 'email',
    values: string[]
  ): Promise<MoodleUser[]> {
    const response = await this.client.callFunction<MoodleUser[]>(
      'core_user_get_users_by_field',
      { field, values }
    );
    return response;
  }

  /**
   * Get user by ID
   */
  async getUserById(userId: number): Promise<MoodleUser | null> {
    const users = await this.getUsersByField('id', [userId.toString()]);
    return users.length > 0 ? users[0] : null;
  }

  /**
   * Get user by username
   */
  async getUserByUsername(username: string): Promise<MoodleUser | null> {
    const users = await this.getUsersByField('username', [username]);
    return users.length > 0 ? users[0] : null;
  }

  /**
   * Get user by email
   */
  async getUserByEmail(email: string): Promise<MoodleUser | null> {
    const users = await this.getUsersByField('email', [email]);
    return users.length > 0 ? users[0] : null;
  }

  /**
   * Create new users
   * Uses: core_user_create_users
   */
  async createUsers(users: CreateUserParams[]): Promise<Array<{ id: number; username: string }>> {
    const response = await this.client.callFunction<Array<{ id: number; username: string }>>(
      'core_user_create_users',
      { users }
    );
    return response;
  }

  /**
   * Create a single user
   */
  async createUser(user: CreateUserParams): Promise<{ id: number; username: string }> {
    const result = await this.createUsers([user]);
    return result[0];
  }

  /**
   * Update users
   * Uses: core_user_update_users
   */
  async updateUsers(users: UpdateUserParams[]): Promise<{ warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ warnings?: unknown[] }>(
      'core_user_update_users',
      { users }
    );
    return response;
  }

  /**
   * Update a single user
   */
  async updateUser(user: UpdateUserParams): Promise<{ warnings?: unknown[] }> {
    return this.updateUsers([user]);
  }

  /**
   * Delete users
   * Uses: core_user_delete_users
   */
  async deleteUsers(userIds: number[]): Promise<null> {
    const response = await this.client.callFunction<null>(
      'core_user_delete_users',
      { userids: userIds }
    );
    return response;
  }

  /**
   * Delete a single user
   */
  async deleteUser(userId: number): Promise<null> {
    return this.deleteUsers([userId]);
  }

  /**
   * Get course user profiles
   * Uses: core_user_get_course_user_profiles
   */
  async getCourseUserProfiles(
    userList: Array<{ userid: number; courseid: number }>
  ): Promise<MoodleUser[]> {
    const response = await this.client.callFunction<MoodleUser[]>(
      'core_user_get_course_user_profiles',
      { userlist: userList }
    );
    return response;
  }

  /**
   * Get user preferences
   * Uses: core_user_get_user_preferences
   */
  async getUserPreferences(
    name?: string,
    userId?: number
  ): Promise<{ preferences: UserPreference[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ preferences: UserPreference[]; warnings?: unknown[] }>(
      'core_user_get_user_preferences',
      { name, userid: userId }
    );
    return response;
  }

  /**
   * Set user preferences
   * Uses: core_user_set_user_preferences
   */
  async setUserPreferences(
    preferences: Array<{ name: string; value: string; userid: number }>
  ): Promise<{ saved: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ saved: unknown[]; warnings?: unknown[] }>(
      'core_user_set_user_preferences',
      { preferences }
    );
    return response;
  }

  /**
   * Update user preferences
   * Uses: core_user_update_user_preferences
   */
  async updateUserPreferences(
    userId: number,
    preferences: Array<{ type: string; name: string; value: string }>,
    emailstop?: number
  ): Promise<null> {
    const response = await this.client.callFunction<null>(
      'core_user_update_user_preferences',
      { userid: userId, preferences, emailstop }
    );
    return response;
  }

  /**
   * Update user picture
   * Uses: core_user_update_picture
   */
  async updatePicture(
    draftItemId: number,
    userId?: number,
    deletePicture?: boolean
  ): Promise<{ success: boolean; profileimageurl?: string; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ success: boolean; profileimageurl?: string; warnings?: unknown[] }>(
      'core_user_update_picture',
      { draftitemid: draftItemId, userid: userId, delete: deletePicture }
    );
    return response;
  }

  /**
   * View user profile
   * Uses: core_user_view_user_profile
   */
  async viewUserProfile(userId: number, courseId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'core_user_view_user_profile',
      { userid: userId, courseid: courseId }
    );
    return response;
  }

  /**
   * Agree to site policy
   * Uses: core_user_agree_site_policy
   */
  async agreeSitePolicy(): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'core_user_agree_site_policy'
    );
    return response;
  }

  /**
   * Get private files info
   * Uses: core_user_get_private_files_info
   */
  async getPrivateFilesInfo(userId?: number): Promise<{
    filecount: number;
    foldercount: number;
    filesize: number;
    filesizewithoutreferences: number;
    warnings?: unknown[];
  }> {
    const response = await this.client.callFunction<{
      filecount: number;
      foldercount: number;
      filesize: number;
      filesizewithoutreferences: number;
      warnings?: unknown[];
    }>('core_user_get_private_files_info', { userid: userId });
    return response;
  }
}

// Singleton instance
export const userService = new UserService();

export default userService;
