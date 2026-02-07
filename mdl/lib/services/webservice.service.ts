// Webservice Service
// Moodle API functions: core_webservice_*

import MoodleApiClient, { type MoodleSiteInfo } from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class WebserviceService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  /**
   * Get site info
   * Uses: core_webservice_get_site_info
   */
  async getSiteInfo(): Promise<MoodleSiteInfo> {
    const response = await this.client.callFunction<MoodleSiteInfo>(
      'core_webservice_get_site_info',
      {}
    );
    return response;
  }
}

// Singleton instance
export const webserviceService = new WebserviceService();

export default webserviceService;
