// Site/Webservice Service
// Moodle API functions: core_webservice_*

import MoodleApiClient, { type MoodleSiteInfo } from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface WebserviceFunction {
  name: string;
  version: string;
}

export class SiteService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get site information
   * Uses: core_webservice_get_site_info
   */
  async getSiteInfo(): Promise<MoodleSiteInfo> {
    const response = await this.callWebService<MoodleSiteInfo>(
      'core_webservice_get_site_info'
    );
    return response.data;
  }

  /**
   * Get available webservice functions
   * Uses: core_webservice_get_site_info
   */
  async getAvailableFunctions(): Promise<WebserviceFunction[]> {
    const siteInfo = await this.getSiteInfo();
    return siteInfo.functions || [];
  }

  /**
   * Get Moodle version info
   */
  async getVersionInfo(): Promise<{ version: string; release: string }> {
    const siteInfo = await this.getSiteInfo();
    return {
      version: siteInfo.version || 'Unknown',
      release: siteInfo.release || 'Unknown',
    };
  }

  /**
   * Get current user info from site info
   */
  async getCurrentUserInfo(): Promise<{
    userid: number;
    username: string;
    firstname: string;
    lastname: string;
    fullname: string;
    userpictureurl: string;
  }> {
    const siteInfo = await this.getSiteInfo();
    return {
      userid: siteInfo.userid,
      username: siteInfo.username,
      firstname: siteInfo.firstname,
      lastname: siteInfo.lastname, 
      fullname: siteInfo.fullname || 'Unknown',
      userpictureurl: siteInfo.userpictureurl || 'Unknown',
    };
  }
}

// Singleton instance
export const siteService = new SiteService();

export default siteService;
