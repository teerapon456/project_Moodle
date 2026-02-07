// Content Bank Service
// Moodle API functions: core_contentbank_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface ContentBankContent {
  id: number;
  name: string;
  contextid: number;
  contenttype: string;
  instanceid: number;
  configdata: string;
  usercreated: number;
  usermodified: number;
  timecreated: number;
  timemodified: number;
}

export interface ContentType {
  typename: string;
  typeeditor: string;
  typeicon: string;
  canupload: boolean;
  canedit: boolean;
}

export class ContentBankService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Delete content
   * Uses: core_contentbank_delete_content
   */
  async deleteContent(contentIds: number[]): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_contentbank_delete_content',
      { contentids: contentIds }
    );
    return response.data;
  }

  /**
   * Rename content
   * Uses: core_contentbank_rename_content
   */
  async renameContent(contentId: number, name: string): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_contentbank_rename_content',
      { contentid: contentId, name }
    );
    return response.data;
  }

  /**
   * Set content visibility
   * Uses: core_contentbank_set_content_visibility
   */
  async setContentVisibility(contentId: number, visibility: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_contentbank_set_content_visibility',
      { contentid: contentId, visibility }
    );
    return response.data;
  }

  /**
   * Copy content to course
   * Uses: core_contentbank_copy_content
   */
  async copyContent(contentId: number, name: string): Promise<{ id: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ id: number; warnings?: unknown[] }>(
      'core_contentbank_copy_content',
      { contentid: contentId, name }
    );
    return response.data;
  }
}

// Singleton instance
export const contentBankService = new ContentBankService();

export default contentBankService;
