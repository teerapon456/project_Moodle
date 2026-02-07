// Report Service
// Moodle API functions: report_*, gradereport_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Insight {
  id: number;
  name: string;
  description: string;
  contextid: number;
  contextname: string;
  action: string;
  actionvisible: boolean;
  actionurl: string;
  timecreated: number;
  timestart: number;
  timeend: number;
  predictions: unknown[];
}

export interface CompetencyReport {
  userid: number;
  userfullname: string;
  userprofileurl: string;
  userpicture: string;
  competency: unknown;
  usercompetency: unknown;
  usercompetencycourse: unknown;
  evidence: unknown[];
}

export class ReportService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  // ========== INSIGHTS ==========

  /**
   * Get insights action
   * Uses: report_insights_action_executed
   */
  async insightsActionExecuted(actionName: string, predictionIds: number[]): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'report_insights_action_executed',
      { actionname: actionName, predictionids: predictionIds }
    );
    return response.data;
  }

  /**
   * Set notuseful prediction
   * Uses: report_insights_set_notuseful_prediction
   */
  async setNotUsefulPrediction(predictionId: number): Promise<{ success: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ success: boolean; warnings?: unknown[] }>(
      'report_insights_set_notuseful_prediction',
      { predictionid: predictionId }
    );
    return response.data;
  }

  /**
   * Set fixed prediction
   * Uses: report_insights_set_fixed_prediction
   */
  async setFixedPrediction(predictionId: number): Promise<{ success: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ success: boolean; warnings?: unknown[] }>(
      'report_insights_set_fixed_prediction',
      { predictionid: predictionId }
    );
    return response.data;
  }

  // ========== COMPETENCY REPORT ==========

  /**
   * Get competency report
   * Uses: report_competency_data_for_report
   */
  async getCompetencyReport(courseId: number, userId: number, competencyId: number, moduleId?: number): Promise<CompetencyReport> {
    const response = await this.callWebService<CompetencyReport>(
      'report_competency_data_for_report',
      { courseid: courseId, userid: userId, competencyid: competencyId, moduleid: moduleId }
    );
    return response.data;
  }

  // ========== GRADE REPORTS ==========

  /**
   * Get grade overview
   * Uses: gradereport_overview_get_course_grades
   */
  async getGradeOverview(userId?: number): Promise<{ grades: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ grades: unknown[]; warnings?: unknown[] }>(
      'gradereport_overview_get_course_grades',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * View grade report
   * Uses: gradereport_overview_view_grade_report
   */
  async viewGradeOverviewReport(courseId: number, userId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'gradereport_overview_view_grade_report',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get user grades table
   * Uses: gradereport_user_get_grades_table
   */
  async getUserGradesTable(courseId: number, userId?: number, groupId?: number): Promise<{ tables: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ tables: unknown[]; warnings?: unknown[] }>(
      'gradereport_user_get_grades_table',
      { courseid: courseId, userid: userId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get user grade items
   * Uses: gradereport_user_get_grade_items
   */
  async getUserGradeItems(courseId: number, userId?: number, groupId?: number): Promise<{ usergrades: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ usergrades: unknown[]; warnings?: unknown[] }>(
      'gradereport_user_get_grade_items',
      { courseid: courseId, userid: userId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * View user grade report
   * Uses: gradereport_user_view_grade_report
   */
  async viewUserGradeReport(courseId: number, userId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'gradereport_user_view_grade_report',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get grader access information
   * Uses: gradereport_grader_get_users_in_report
   */
  async getGraderUsersInReport(courseId: number, groupId?: number, filters?: string, page?: number, perPage?: number): Promise<{ users: unknown[]; userscount: number; showonlyactiveenrol: boolean; showrank: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      users: unknown[];
      userscount: number;
      showonlyactiveenrol: boolean;
      showrank: boolean;
      warnings?: unknown[];
    }>('gradereport_grader_get_users_in_report', {
      courseid: courseId,
      groupid: groupId,
      filters,
      page,
      perpage: perPage
    });
    return response.data;
  }

  // ========== SINGLE VIEW REPORT ==========

  /**
   * Get single view grade items
   * Uses: gradereport_singleview_get_grade_items_for_search_widget
   */
  async getSingleViewGradeItems(courseId: number): Promise<{ gradeitems: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ gradeitems: unknown[]; warnings?: unknown[] }>(
      'gradereport_singleview_get_grade_items_for_search_widget',
      { courseid: courseId }
    );
    return response.data;
  }
}

// Singleton instance
export const reportService = new ReportService();

export default reportService;
