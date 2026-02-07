// H5P Service
// Moodle API functions: mod_h5pactivity_*, core_h5p_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface H5PActivity {
  id: number;
  course: number;
  name: string;
  timecreated: number;
  timemodified: number;
  intro: string;
  introformat: number;
  grade: number;
  displayoptions: number;
  enabletracking: boolean;
  grademethod: number;
  contenthash: string;
  coursemodule: number;
  context: number;
  introfiles: unknown[];
  package: unknown[];
  deployedfile: {
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    timemodified: number;
    mimetype: string;
  };
}

export interface H5PAttempt {
  id: number;
  h5pactivityid: number;
  userid: number;
  timecreated: number;
  timemodified: number;
  attempt: number;
  rawscore: number;
  maxscore: number;
  duration: number;
  completion: number;
  success: number;
  scaled: number;
}

export interface H5PAttemptResult {
  id: number;
  attemptid: number;
  subcontent: string;
  timecreated: number;
  interactiontype: string;
  description: string;
  correctpattern: string;
  response: string;
  additionals: string;
  rawscore: number;
  maxscore: number;
  duration: number;
  completion: number;
  success: number;
}

export class H5PService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get H5P activities by courses
   * Uses: mod_h5pactivity_get_h5pactivities_by_courses
   */
  async getH5PActivitiesByCourses(courseIds?: number[]): Promise<{ h5pactivities: H5PActivity[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ h5pactivities: H5PActivity[]; warnings?: unknown[] }>(
      'mod_h5pactivity_get_h5pactivities_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Get H5P activity access information
   * Uses: mod_h5pactivity_get_h5pactivity_access_information
   */
  async getAccessInformation(h5pActivityId: number): Promise<unknown> {
    const response = await this.callWebService<unknown>(
      'mod_h5pactivity_get_h5pactivity_access_information',
      { h5pactivityid: h5pActivityId }
    );
    return response.data;
  }

  /**
   * View H5P activity (log event)
   * Uses: mod_h5pactivity_view_h5pactivity
   */
  async viewH5PActivity(h5pActivityId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_h5pactivity_view_h5pactivity',
      { h5pactivityid: h5pActivityId }
    );
    return response.data;
  }

  /**
   * Get attempts
   * Uses: mod_h5pactivity_get_attempts
   */
  async getAttempts(h5pActivityId: number, userIds?: number[]): Promise<{ activityid: number; usersattempts: Array<{ userid: number; attempts: H5PAttempt[]; scored?: { title: string; grademethod: string; attempts: H5PAttempt[] } }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      activityid: number;
      usersattempts: Array<{
        userid: number;
        attempts: H5PAttempt[];
        scored?: {
          title: string;
          grademethod: string;
          attempts: H5PAttempt[];
        };
      }>;
      warnings?: unknown[];
    }>('mod_h5pactivity_get_attempts', { h5pactivityid: h5pActivityId, userids: userIds });
    return response.data;
  }

  /**
   * Get results
   * Uses: mod_h5pactivity_get_results
   */
  async getResults(h5pActivityId: number, attemptIds?: number[]): Promise<{ activityid: number; attempts: Array<{ id: number; h5pactivityid: number; userid: number; timecreated: number; timemodified: number; attempt: number; rawscore: number; maxscore: number; duration: number; completion: number; success: number; scaled: number; results: H5PAttemptResult[] }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      activityid: number;
      attempts: Array<{
        id: number;
        h5pactivityid: number;
        userid: number;
        timecreated: number;
        timemodified: number;
        attempt: number;
        rawscore: number;
        maxscore: number;
        duration: number;
        completion: number;
        success: number;
        scaled: number;
        results: H5PAttemptResult[];
      }>;
      warnings?: unknown[];
    }>('mod_h5pactivity_get_results', { h5pactivityid: h5pActivityId, attemptids: attemptIds });
    return response.data;
  }

  /**
   * Log H5P report viewed
   * Uses: mod_h5pactivity_log_report_viewed
   */
  async logReportViewed(h5pActivityId: number, userId?: number, attemptId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_h5pactivity_log_report_viewed',
      { h5pactivityid: h5pActivityId, userid: userId, attemptid: attemptId }
    );
    return response.data;
  }

  /**
   * Get user attempts (for current user)
   * Uses: mod_h5pactivity_get_user_attempts
   */
  async getUserAttempts(h5pActivityId: number, sortOrder?: string, page?: number, perPage?: number): Promise<{ activityid: number; attempts: H5PAttempt[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ activityid: number; attempts: H5PAttempt[]; warnings?: unknown[] }>(
      'mod_h5pactivity_get_user_attempts',
      { h5pactivityid: h5pActivityId, sortorder: sortOrder, page, perpage: perPage }
    );
    return response.data;
  }

  /**
   * Get trusted H5P file (core_h5p)
   * Uses: core_h5p_get_trusted_h5p_file
   */
  async getTrustedH5PFile(url: string, frame?: number, export_?: number, embed?: number, copyright?: number): Promise<{ files: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ files: unknown[]; warnings?: unknown[] }>(
      'core_h5p_get_trusted_h5p_file',
      { url, frame, export: export_, embed, copyright }
    );
    return response.data;
  }
}

// Singleton instance
export const h5pService = new H5PService();

export default h5pService;
