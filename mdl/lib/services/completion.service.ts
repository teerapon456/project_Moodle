// Completion Service
// Moodle API functions: core_completion_*

import MoodleApiClient from '@/lib/moodle-api';
import type { ActivitiesCompletionStatus, CourseCompletionStatus } from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class CompletionService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get activities completion status
   * Uses: core_completion_get_activities_completion_status
   */
  async getActivitiesCompletionStatus(
    courseId: number,
    userId: number
  ): Promise<ActivitiesCompletionStatus> {
    const response = await this.callWebService<ActivitiesCompletionStatus>(
      'core_completion_get_activities_completion_status',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get course completion status
   * Uses: core_completion_get_course_completion_status
   */
  async getCourseCompletionStatus(
    courseId: number,
    userId: number
  ): Promise<{ completionstatus: CourseCompletionStatus; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      completionstatus: CourseCompletionStatus;
      warnings?: unknown[];
    }>('core_completion_get_course_completion_status', { courseid: courseId, userid: userId });
    return response.data;
  }

  /**
   * Mark course as self-completed
   * Uses: core_completion_mark_course_self_completed
   */
  async markCourseSelfCompleted(courseId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_completion_mark_course_self_completed',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Update activity completion status manually
   * Uses: core_completion_update_activity_completion_status_manually
   */
  async updateActivityCompletion(
    cmId: number,
    completed: boolean
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_completion_update_activity_completion_status_manually',
      { cmid: cmId, completed }
    );
    return response.data;
  }

  /**
   * Override activity completion status
   * Uses: core_completion_override_activity_completion_status
   */
  async overrideActivityCompletion(
    userId: number,
    cmId: number,
    newState: 0 | 1 | 2 | 3
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_completion_override_activity_completion_status',
      { userid: userId, cmid: cmId, newstate: newState }
    );
    return response.data;
  }
}

// Singleton instance
export const completionService = new CompletionService();

export default completionService;
