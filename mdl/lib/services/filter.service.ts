// Filter Service
// Moodle API functions: core_filters_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Filter {
  contextlevel: string;
  instanceid: number;
  contextid: number;
  filter: string;
  localstate: number;
  inheritedstate: number;
}

export class FilterService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get available filters in context
   * Uses: core_filters_get_available_in_context
   */
  async getAvailableInContext(contexts: Array<{ contextlevel: string; instanceid: number }>): Promise<{ filters: Filter[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ filters: Filter[]; warnings?: unknown[] }>(
      'core_filters_get_available_in_context',
      { contexts }
    );
    return response.data;
  }
}

// Singleton instance
export const filterService = new FilterService();

export default filterService;
