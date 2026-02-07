// Enrollment Service
// Moodle API functions: core_enrol_*, enrol_manual_*, enrol_self_*, enrol_guest_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  EnrolmentMethod,
  EnrolledUser,
  ManualEnrolParams,
  SelfEnrolParams,
  SelfEnrolResponse,
  GuestEnrolInfo,
  MoodleCourse
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class EnrollmentService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get enrolled users in a course
   * Uses: core_enrol_get_enrolled_users
   */
  async getEnrolledUsers(
    courseId: number,
    options?: Array<{ name: string; value: unknown }>
  ): Promise<EnrolledUser[]> {
    const response = await this.callWebService<EnrolledUser[]>(
      'core_enrol_get_enrolled_users',
      { courseid: courseId, options }
    );
    return response.data;
  }

  /**
   * Search for users to enrol
   * Uses: core_enrol_search_users
   */
  async searchUsers(
    courseId: number,
    search: string,
    searchAnywhere: boolean = true,
    page: number = 0,
    perPage: number = 25
  ): Promise<EnrolledUser[]> {
    const response = await this.callWebService<EnrolledUser[]>(
      'core_enrol_search_users',
      {
        courseid: courseId,
        search,
        searchanywhere: searchAnywhere,
        page,
        perpage: perPage
      }
    );
    return response.data;
  }

  /**
   * Get user's enrolled courses
   * Uses: core_enrol_get_users_courses
   */
  async getUserCourses(userId: number, returnUserCount: boolean = false): Promise<MoodleCourse[]> {
    const response = await this.callWebService<MoodleCourse[]>(
      'core_enrol_get_users_courses',
      { userid: userId, returnusercount: returnUserCount }
    );
    return response.data;
  }

  /**
   * Get course enrolment methods
   * Uses: core_enrol_get_course_enrolment_methods
   */
  async getEnrolmentMethods(courseId: number): Promise<EnrolmentMethod[]> {
    const response = await this.callWebService<EnrolmentMethod[]>(
      'core_enrol_get_course_enrolment_methods',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Manually enrol users
   * Uses: enrol_manual_enrol_users
   */
  async manualEnrolUsers(enrolments: ManualEnrolParams[]): Promise<null> {
    const response = await this.callWebService<null>(
      'enrol_manual_enrol_users',
      { enrolments }
    );
    return response.data;
  }

  /**
   * Enrol a single user
   */
  async enrolUser(
    userId: number,
    courseId: number,
    roleId: number = 5, // Default: student role
    timeStart?: number,
    timeEnd?: number
  ): Promise<null> {
    return this.manualEnrolUsers([{
      userid: userId,
      courseid: courseId,
      roleid: roleId,
      timestart: timeStart,
      timeend: timeEnd
    }]);
  }

  /**
   * Unenrol users manually
   * Uses: enrol_manual_unenrol_users
   */
  async manualUnenrolUsers(
    enrolments: Array<{ userid: number; courseid: number; roleid?: number }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'enrol_manual_unenrol_users',
      { enrolments }
    );
    return response.data;
  }

  /**
   * Unenrol a single user
   */
  async unenrolUser(userId: number, courseId: number, roleId?: number): Promise<null> {
    return this.manualUnenrolUsers([{ userid: userId, courseid: courseId, roleid: roleId }]);
  }

  /**
   * Self-enrol current user
   * Uses: enrol_self_enrol_user
   */
  async selfEnrol(params: SelfEnrolParams): Promise<SelfEnrolResponse> {
    const response = await this.callWebService<SelfEnrolResponse>(
      'enrol_self_enrol_user',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Get self-enrolment instance info
   * Uses: enrol_self_get_instance_info
   */
  async getSelfEnrolInfo(instanceId: number): Promise<{
    id: number;
    courseid: number;
    type: string;
    name: string;
    status: boolean;
    enrolpassword?: string;
  }> {
    const response = await this.callWebService<{
      id: number;
      courseid: number;
      type: string;
      name: string;
      status: boolean;
      enrolpassword?: string;
    }>('enrol_self_get_instance_info', { instanceid: instanceId });
    return response.data;
  }

  /**
   * Get guest enrolment instance info
   * Uses: enrol_guest_get_instance_info
   */
  async getGuestEnrolInfo(instanceId: number): Promise<GuestEnrolInfo> {
    const response = await this.callWebService<GuestEnrolInfo>(
      'enrol_guest_get_instance_info',
      { instanceid: instanceId }
    );
    return response.data;
  }

  /**
   * Validate guest enrolment password
   * Uses: enrol_guest_validate_password
   */
  async validateGuestPassword(
    instanceId: number,
    password: string
  ): Promise<{ validated: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ validated: boolean; warnings?: unknown[] }>(
      'enrol_guest_validate_password',
      { instanceid: instanceId, password }
    );
    return response.data;
  }

  /**
   * Get enrolled users with capabilities
   * Uses: core_enrol_get_enrolled_users_with_capability
   */
  async getEnrolledUsersWithCapability(
    coursecapabilities: Array<{ courseid: number; capabilities: string[] }>,
    options?: Array<{ name: string; value: unknown }>
  ): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'core_enrol_get_enrolled_users_with_capability',
      { coursecapabilities, options }
    );
    return response.data;
  }

  /**
   * Get potential users to enrol
   * Uses: core_enrol_get_potential_users
   */
  async getPotentialUsers(
    courseId: number,
    enrolId: number,
    search: string,
    searchAnywhere: boolean = true,
    page: number = 0,
    perPage: number = 25
  ): Promise<EnrolledUser[]> {
    const response = await this.callWebService<EnrolledUser[]>(
      'core_enrol_get_potential_users',
      {
        courseid: courseId,
        enrolid: enrolId,
        search,
        searchanywhere: searchAnywhere,
        page,
        perpage: perPage
      }
    );
    return response.data;
  }
}

// Singleton instance
export const enrollmentService = new EnrollmentService();

export default enrollmentService;
