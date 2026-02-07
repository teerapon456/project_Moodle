// SCORM Service
// Moodle API functions: mod_scorm_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Scorm {
  id: number;
  coursemodule: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  packagesize: number;
  packageurl: string;
  version: string;
  maxgrade: number;
  grademethod: number;
  whatgrade: number;
  maxattempt: number;
  forcecompleted: boolean;
  forcenewattempt: number;
  lastattemptlock: boolean;
  displayattemptstatus: number;
  displaycoursestructure: boolean;
  updatefreq: number;
  skipview: number;
  nav: number;
  navpositionleft: number;
  navpositiontop: number;
  auto: boolean;
  popup: number;
  timeopen: number;
  timeclose: number;
  displayactivityname: boolean;
  autocommit: boolean;
  allowofflineattempts: boolean;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
}

export interface ScormAttemptCount {
  userid: number;
  attemptscount: number;
}

export interface ScormSco {
  id: number;
  scorm: number;
  manifest: string;
  organization: string;
  parent: string;
  identifier: string;
  launch: string;
  scormtype: string;
  title: string;
  sortorder: number;
}

export interface ScormUserData {
  element: string;
  value: string;
}

export class ScormService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get SCORM by courses
   * Uses: mod_scorm_get_scorms_by_courses
   */
  async getScormsByCourses(courseIds?: number[]): Promise<{ scorms: Scorm[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ scorms: Scorm[]; warnings?: unknown[] }>(
      'mod_scorm_get_scorms_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Get SCORM access information
   * Uses: mod_scorm_get_scorm_access_information
   */
  async getAccessInformation(scormId: number): Promise<unknown> {
    const response = await this.callWebService<unknown>(
      'mod_scorm_get_scorm_access_information',
      { scormid: scormId }
    );
    return response.data;
  }

  /**
   * View SCORM (log event)
   * Uses: mod_scorm_view_scorm
   */
  async viewScorm(scormId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_scorm_view_scorm',
      { scormid: scormId }
    );
    return response.data;
  }

  /**
   * Get SCORM attempt count
   * Uses: mod_scorm_get_scorm_attempt_count
   */
  async getAttemptCount(scormId: number, userId: number, ignoreMissingCompletion?: boolean): Promise<{ attemptscount: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ attemptscount: number; warnings?: unknown[] }>(
      'mod_scorm_get_scorm_attempt_count',
      { scormid: scormId, userid: userId, ignoremissingcompletion: ignoreMissingCompletion }
    );
    return response.data;
  }

  /**
   * Get SCORM SCOs
   * Uses: mod_scorm_get_scorm_scos
   */
  async getScormScos(scormId: number, organization?: string): Promise<{ scos: ScormSco[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ scos: ScormSco[]; warnings?: unknown[] }>(
      'mod_scorm_get_scorm_scos',
      { scormid: scormId, organization }
    );
    return response.data;
  }

  /**
   * Get SCORM user data
   * Uses: mod_scorm_get_scorm_user_data
   */
  async getUserData(scormId: number, attempt: number): Promise<{ data: Array<{ scoid: number; userdata: ScormUserData[]; defaultdata: ScormUserData[] }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      data: Array<{
        scoid: number;
        userdata: ScormUserData[];
        defaultdata: ScormUserData[];
      }>;
      warnings?: unknown[];
    }>('mod_scorm_get_scorm_user_data', { scormid: scormId, attempt });
    return response.data;
  }

  /**
   * Insert SCORM tracks
   * Uses: mod_scorm_insert_scorm_tracks
   */
  async insertTracks(
    scoId: number,
    attempt: number,
    tracks: Array<{ element: string; value: string }>
  ): Promise<{ trackids: number[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ trackids: number[]; warnings?: unknown[] }>(
      'mod_scorm_insert_scorm_tracks',
      { scoid: scoId, attempt, tracks }
    );
    return response.data;
  }

  /**
   * Launch SCO
   * Uses: mod_scorm_launch_sco
   */
  async launchSco(scormId: number, scoId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_scorm_launch_sco',
      { scormid: scormId, scoid: scoId }
    );
    return response.data;
  }

  /**
   * Get SCO tracks
   * Uses: mod_scorm_get_scorm_sco_tracks
   */
  async getScoTracks(scoId: number, userId: number, attempt?: number): Promise<{ data: { attempt: number; tracks: ScormUserData[] }; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      data: {
        attempt: number;
        tracks: ScormUserData[];
      };
      warnings?: unknown[];
    }>('mod_scorm_get_scorm_sco_tracks', { scoid: scoId, userid: userId, attempt });
    return response.data;
  }
}

// Singleton instance
export const scormService = new ScormService();

export default scormService;
