// Block Service
// Moodle API functions: core_block_*

import MoodleApiClient from '@/lib/moodle-api';
import { moodleConfig } from '../config';

export interface Block {
  instanceid: number;
  name: string;
  region: string;
  positionid: number;
  collapsible: boolean;
  dockable: boolean;
  weight?: number;
  visible: boolean;
  contents?: {
    title: string;
    content: string;
    contentformat: number;
    footer: string;
    files: unknown[];
  };
  configs?: Array<{
    name: string;
    value: string;
    type: string;
  }>;
}


export class BlockService {
  private client: MoodleApiClient;

  constructor(token: string = moodleConfig.adminToken) {
    this.client = new MoodleApiClient(moodleConfig.publicUrl, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get course blocks
   * Uses: core_block_get_course_blocks
   */
  async getCourseBlocks(courseId: number, returnContents?: boolean): Promise<{ blocks: Block[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ blocks: Block[]; warnings?: unknown[] }>(
      'core_block_get_course_blocks',
      { courseid: courseId, returncontents: returnContents }
    );
    return response.data;
  }

  /**
   * Get dashboard blocks
   * Uses: core_block_get_dashboard_blocks
   */
  async getDashboardBlocks(userId?: number, returnContents?: boolean, myPage?: string): Promise<{ blocks: Block[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ blocks: Block[]; warnings?: unknown[] }>(
      'core_block_get_dashboard_blocks',
      { userid: userId, returncontents: returnContents, mypage: myPage }
    );
    return response.data;
  }

  /**
   * Fetch content from block
   * Uses: core_block_fetch_addable_blocks
   */
  async fetchAddableBlocks(pagecontextid: number, pagetype: string, pagelayout: string, subpage?: string): Promise<{ blocks: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ blocks: unknown[]; warnings?: unknown[] }>(
      'core_block_fetch_addable_blocks',
      { pagecontextid, pagetype, pagelayout, subpage }
    );
    return response.data;
  }
}

// Singleton instance
export const blockService = new BlockService();

export default blockService;
