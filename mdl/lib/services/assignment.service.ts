// Assignment Service
// Moodle API functions: mod_assign_*

import MoodleApiClient from '@/lib/moodle-api';
import { moodleConfig } from '../config';
import type {
  MoodleAssignment,
  AssignmentCourse,
  MoodleSubmission,
  SubmissionStatus,
  AssignmentGrade,
  AssignmentParticipant,
  SaveSubmissionParams,
  SaveGradeParams
} from '../types';

export class AssignmentService {
  private client: MoodleApiClient;
  
  constructor(token: string = moodleConfig.adminToken) {
    this.client = new MoodleApiClient(moodleConfig.publicUrl, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get assignments for courses
   * Uses: mod_assign_get_assignments
   */
  async getAssignments(
    courseIds?: number[],
    capabilities?: string[],
    includeNoUsers?: boolean
  ): Promise<{ courses: AssignmentCourse[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ courses: AssignmentCourse[]; warnings?: unknown[] }>(
      'mod_assign_get_assignments',
      {
        courseids: courseIds,
        capabilities,
        includenousersflag: includeNoUsers
      }
    );
    return response.data;
  }

  /**
   * Get assignment by course ID
   */
  async getAssignmentsByCourse(courseId: number): Promise<MoodleAssignment[]> {
    const result = await this.getAssignments([courseId]);
    const course = result.courses.find(c => c.id === courseId);
    return course?.assignments || [];
  }

  /**
   * Get submissions for assignments
   * Uses: mod_assign_get_submissions
   */
  async getSubmissions(
    assignmentIds: number[],
    status?: string,
    since?: number,
    before?: number
  ): Promise<{ assignments: Array<{ assignmentid: number; submissions: MoodleSubmission[] }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      assignments: Array<{ assignmentid: number; submissions: MoodleSubmission[] }>;
      warnings?: unknown[];
    }>('mod_assign_get_submissions', {
      assignmentids: assignmentIds,
      status,
      since,
      before
    });
    return response.data;
  }

  /**
   * Get submission status for a user
   * Uses: mod_assign_get_submission_status
   */
  async getSubmissionStatus(
    assignmentId: number,
    userId?: number,
    groupId?: number
  ): Promise<SubmissionStatus> {
    const response = await this.callWebService<SubmissionStatus>(
      'mod_assign_get_submission_status',
      { assignid: assignmentId, userid: userId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Save submission
   * Uses: mod_assign_save_submission
   */
  async saveSubmission(params: SaveSubmissionParams): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_save_submission',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Submit for grading
   * Uses: mod_assign_submit_for_grading
   */
  async submitForGrading(
    assignmentId: number,
    acceptSubmissionStatement: boolean = true
  ): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_submit_for_grading',
      { assignmentid: assignmentId, acceptsubmissionstatement: acceptSubmissionStatement }
    );
    return response.data;
  }

  /**
   * Start a new submission
   * Uses: mod_assign_start_submission
   */
  async startSubmission(assignmentId: number): Promise<{ submissionid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ submissionid: number; warnings?: unknown[] }>(
      'mod_assign_start_submission',
      { assignid: assignmentId }
    );
    return response.data;
  }

  /**
   * Remove submission
   * Uses: mod_assign_remove_submission
   */
  async removeSubmission(userId: number, assignmentId: number): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'mod_assign_remove_submission',
      { userid: userId, assignid: assignmentId }
    );
    return response.data;
  }

  /**
   * Get grades for assignments
   * Uses: mod_assign_get_grades
   */
  async getGrades(
    assignmentIds: number[],
    since?: number
  ): Promise<{ assignments: Array<{ assignmentid: number; grades: AssignmentGrade[] }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      assignments: Array<{ assignmentid: number; grades: AssignmentGrade[] }>;
      warnings?: unknown[];
    }>('mod_assign_get_grades', { assignmentids: assignmentIds, since });
    return response.data;
  }

  /**
   * Save a single grade
   * Uses: mod_assign_save_grade
   */
  async saveGrade(params: SaveGradeParams): Promise<null> {
    const response = await this.callWebService<null>(
      'mod_assign_save_grade',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Save multiple grades
   * Uses: mod_assign_save_grades
   */
  async saveGrades(
    assignmentId: number,
    applyToAll: boolean,
    grades: Array<{
      userid: number;
      grade: number;
      attemptnumber: number;
      addattempt: number;
      workflowstate: string;
      plugindata?: unknown;
    }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'mod_assign_save_grades',
      { assignmentid: assignmentId, applytoall: applyToAll, grades }
    );
    return response.data;
  }

  /**
   * List participants
   * Uses: mod_assign_list_participants
   */
  async listParticipants(
    assignmentId: number,
    groupId: number = 0,
    filter: string = '',
    skip: number = 0,
    limit: number = 0,
    onlyIds: boolean = false,
    includeEnrolments: boolean = true,
    tablesort: boolean = false
  ): Promise<AssignmentParticipant[]> {
    const response = await this.callWebService<AssignmentParticipant[]>(
      'mod_assign_list_participants',
      {
        assignid: assignmentId,
        groupid: groupId,
        filter,
        skip,
        limit,
        onlyids: onlyIds,
        includeenrolments: includeEnrolments,
        tablesort
      }
    );
    return response.data;
  }

  /**
   * Get participant details
   * Uses: mod_assign_get_participant
   */
  async getParticipant(
    assignmentId: number,
    userId: number,
    embedUser: boolean = false
  ): Promise<AssignmentParticipant> {
    const response = await this.callWebService<AssignmentParticipant>(
      'mod_assign_get_participant',
      { assignid: assignmentId, userid: userId, embeduser: embedUser }
    );
    return response.data;
  }

  /**
   * View assignment
   * Uses: mod_assign_view_assign
   */
  async viewAssignment(assignmentId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_assign_view_assign',
      { assignid: assignmentId }
    );
    return response.data;
  }

  /**
   * Lock submissions
   * Uses: mod_assign_lock_submissions
   */
  async lockSubmissions(assignmentId: number, userIds: number[]): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_lock_submissions',
      { assignmentid: assignmentId, userids: userIds }
    );
    return response.data;
  }

  /**
   * Unlock submissions
   * Uses: mod_assign_unlock_submissions
   */
  async unlockSubmissions(assignmentId: number, userIds: number[]): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_unlock_submissions',
      { assignmentid: assignmentId, userids: userIds }
    );
    return response.data;
  }

  /**
   * Revert submissions to draft
   * Uses: mod_assign_revert_submissions_to_draft
   */
  async revertToDraft(assignmentId: number, userIds: number[]): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_revert_submissions_to_draft',
      { assignmentid: assignmentId, userids: userIds }
    );
    return response.data;
  }

  /**
   * Save user extensions
   * Uses: mod_assign_save_user_extensions
   */
  async saveUserExtensions(
    assignmentId: number,
    userIds: number[],
    dates: number[]
  ): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'mod_assign_save_user_extensions',
      { assignmentid: assignmentId, userids: userIds, dates }
    );
    return response.data;
  }
}

// Singleton instance
export const assignmentService = new AssignmentService();

export default assignmentService;
