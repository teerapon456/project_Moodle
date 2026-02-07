// Comment Service
// Moodle API functions: core_comment_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Comment {
  id: number;
  content: string;
  format: number;
  timecreated: number;
  strftimeformat: string;
  profileurl: string;
  fullname: string;
  time: string;
  avatar: string;
  userid: number;
  delete?: boolean;
}

export class CommentService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get comments
   * Uses: core_comment_get_comments
   */
  async getComments(contextLevel: string, instanceId: number, component: string, itemId: number, area?: string, page?: number, sortDirection?: string): Promise<{ comments: Comment[]; count?: number; perpage?: number; canpost?: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      comments: Comment[];
      count?: number;
      perpage?: number;
      canpost?: boolean;
      warnings?: unknown[];
    }>('core_comment_get_comments', {
      contextlevel: contextLevel,
      instanceid: instanceId,
      component,
      itemid: itemId,
      area,
      page,
      sortdirection: sortDirection
    });
    return response.data;
  }

  /**
   * Add comments
   * Uses: core_comment_add_comments
   */
  async addComments(comments: Array<{ contextlevel: string; instanceid: number; component: string; content: string; itemid: number; area?: string }>): Promise<Comment[]> {
    const response = await this.callWebService<Comment[]>(
      'core_comment_add_comments',
      { comments }
    );
    return response.data;
  }

  /**
   * Delete comments
   * Uses: core_comment_delete_comments
   */
  async deleteComments(comments: Array<{ id: number }>): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'core_comment_delete_comments',
      { comments }
    );
    return response.data;
  }
}

// Singleton instance
export const commentService = new CommentService();

export default commentService;
