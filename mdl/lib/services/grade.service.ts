// Grade Service
// Moodle API functions: core_grades_*, gradereport_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  GradeItem,
  UserGradesTable,
  OverviewGrades,
  GradeAccessInfo,
  UpdateGradesParams
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class GradeService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get grade items for a course
   * Uses: core_grades_get_gradeitems
   */
  async getGradeItems(courseId: number): Promise<{ gradeItems: GradeItem[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ gradeItems: GradeItem[]; warnings?: unknown[] }>(
      'core_grades_get_gradeitems',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get grades table for a user in a course
   * Uses: gradereport_user_get_grades_table
   */
  async getGradesTable(courseId: number, userId?: number, groupId?: number): Promise<{ tables: UserGradesTable[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ tables: UserGradesTable[]; warnings?: unknown[] }>(
      'gradereport_user_get_grades_table',
      { courseid: courseId, userid: userId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get grade items for a user in a course
   * Uses: gradereport_user_get_grade_items
   */
  async getUserGradeItems(courseId: number, userId?: number, groupId?: number): Promise<{ usergrades: Array<{ courseid: number; userid: number; userfullname: string; useridnumber: string; maxdepth: number; gradeitems: GradeItem[] }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      usergrades: Array<{
        courseid: number;
        userid: number;
        userfullname: string;
        useridnumber: string;
        maxdepth: number;
        gradeitems: GradeItem[];
      }>;
      warnings?: unknown[];
    }>('gradereport_user_get_grade_items', { courseid: courseId, userid: userId, groupid: groupId });
    return response.data;
  }

  /**
   * Get course grades overview for a user
   * Uses: gradereport_overview_get_course_grades
   */
  async getCourseGrades(userId?: number): Promise<OverviewGrades> {
    const response = await this.callWebService<OverviewGrades>(
      'gradereport_overview_get_course_grades',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * View grade report (log view event)
   * Uses: gradereport_user_view_grade_report
   */
  async viewGradeReport(courseId: number, userId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'gradereport_user_view_grade_report',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }

  /**
   * View overview report (log view event)
   * Uses: gradereport_overview_view_grade_report
   */
  async viewOverviewReport(courseId: number, userId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'gradereport_overview_view_grade_report',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get access information for grades
   * Uses: gradereport_user_get_access_information
   */
  async getAccessInformation(courseId: number): Promise<GradeAccessInfo> {
    const response = await this.callWebService<GradeAccessInfo>(
      'gradereport_user_get_access_information',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Update grades
   * Uses: core_grades_update_grades
   */
  async updateGrades(params: UpdateGradesParams): Promise<number> {
    const response = await this.callWebService<number>(
      'core_grades_update_grades',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Get enrolled users for grade selector
   * Uses: core_grades_get_enrolled_users_for_selector
   */
  async getEnrolledUsersForSelector(
    courseId: number,
    groupId?: number
  ): Promise<{ users: Array<{ id: number; fullname: string; profileimageurl?: string }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      users: Array<{ id: number; fullname: string; profileimageurl?: string }>;
      warnings?: unknown[];
    }>('core_grades_get_enrolled_users_for_selector', { courseid: courseId, groupid: groupId });
    return response.data;
  }

  /**
   * Get groups for grade selector
   * Uses: core_grades_get_groups_for_selector
   */
  async getGroupsForSelector(courseId: number): Promise<{ groups: Array<{ id: number; name: string }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{ groups: Array<{ id: number; name: string }>; warnings?: unknown[] }>(
      'core_grades_get_groups_for_selector',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get gradable users
   * Uses: core_grades_get_gradable_users
   */
  async getGradableUsers(
    courseId: number,
    groupId?: number,
    onlyActive: boolean = false
  ): Promise<{ users: Array<{ id: number; fullname: string; profileimageurl?: string }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      users: Array<{ id: number; fullname: string; profileimageurl?: string }>;
      warnings?: unknown[];
    }>('core_grades_get_gradable_users', { courseid: courseId, groupid: groupId, onlyactive: onlyActive });
    return response.data;
  }

  /**
   * Get users in grader report
   * Uses: gradereport_grader_get_users_in_report
   */
  async getUsersInGraderReport(
    courseId: number,
    searchValue?: string,
    page?: number,
    perPage: number = 50
  ): Promise<{ users: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ users: unknown[]; warnings?: unknown[] }>(
      'gradereport_grader_get_users_in_report',
      { courseid: courseId, searchvalue: searchValue, page, perpage: perPage }
    );
    return response.data;
  }
}

// Singleton instance
export const gradeService = new GradeService();

export default gradeService;
