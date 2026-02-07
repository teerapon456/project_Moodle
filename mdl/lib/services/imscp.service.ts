// IMS Content Package Service
// Moodle API functions: mod_imscp_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface ImsCP {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  revision: number;
  keepold: number;
  structure: string;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export class ImscpService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get IMS content packages by courses
   * Uses: mod_imscp_get_imscps_by_courses
   */
  async getImscpsByCourses(courseIds?: number[]): Promise<{ imscps: ImsCP[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ imscps: ImsCP[]; warnings?: unknown[] }>(
      'mod_imscp_get_imscps_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View IMS content package (log event)
   * Uses: mod_imscp_view_imscp
   */
  async viewImscp(imscpId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_imscp_view_imscp',
      { imscpid: imscpId }
    );
    return response.data;
  }
}

// Singleton instance
export const imscpService = new ImscpService();

export default imscpService;
