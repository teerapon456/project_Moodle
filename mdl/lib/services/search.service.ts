// Search Service
// Moodle API functions: core_search_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface SearchResult {
  itemid: number;
  componentname: string;
  areaname: string;
  courseurl: string;
  coursefullname: string;
  timemodified: number;
  title: string;
  docurl: string;
  iconurl: string;
  content: string;
  contextid: number;
  contexturl: string;
  description1: string;
  description2: string;
  multiplefiles: number;
  filenames: string;
  filename: string;
  userid: number;
  userurl: string;
  userfullname: string;
  textformat: number;
}

export interface SearchArea {
  id: string;
  categoryid: string;
  categoryname: string;
  name: string;
}

export class SearchService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get relevant users for search
   * Uses: core_search_get_relevant_users
   */
  async getRelevantUsers(query: string, courseid?: number): Promise<{ users: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ users: unknown[]; warnings?: unknown[] }>(
      'core_search_get_relevant_users',
      { query, courseid }
    );
    return response.data;
  }

  /**
   * View search results (log event)
   * Uses: core_search_view_results
   */
  async viewResults(query: string, filters?: Record<string, unknown>, page?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_search_view_results',
      { query, filters, page }
    );
    return response.data;
  }

  /**
   * Get search areas categories
   * Uses: core_search_get_search_areas_list
   */
  async getSearchAreasList(cat?: string): Promise<{ areas: SearchArea[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ areas: SearchArea[]; warnings?: unknown[] }>(
      'core_search_get_search_areas_list',
      { cat }
    );
    return response.data;
  }

  /**
   * Get top results
   * Uses: core_search_get_top_results (if available)
   */
  async getTopResults(query: string, filters?: Record<string, unknown>): Promise<{ results: SearchResult[]; totalcount: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ results: SearchResult[]; totalcount: number; warnings?: unknown[] }>(
      'core_search_get_top_results',
      { query, filters }
    );
    return response.data;
  }
}

// Singleton instance
export const searchService = new SearchService();

export default searchService;
