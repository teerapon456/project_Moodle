// Forum Service
// Moodle API functions: mod_forum_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  MoodleForum,
  ForumDiscussion,
  ForumPost,
  AddDiscussionParams,
  AddPostParams
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class ForumService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get forums by courses
   * Uses: mod_forum_get_forums_by_courses
   */
  async getForumsByCourses(courseIds?: number[]): Promise<MoodleForum[]> {
    const response = await this.callWebService<MoodleForum[]>(
      'mod_forum_get_forums_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Get forum discussions
   * Uses: mod_forum_get_forum_discussions
   */
  async getForumDiscussions(
    forumId: number,
    sortOrder: number = 0,
    page: number = -1,
    perPage: number = 0,
    groupId?: number
  ): Promise<{ discussions: ForumDiscussion[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ discussions: ForumDiscussion[]; warnings?: unknown[] }>(
      'mod_forum_get_forum_discussions',
      { forumid: forumId, sortorder: sortOrder, page, perpage: perPage, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get discussion posts
   * Uses: mod_forum_get_discussion_posts
   */
  async getDiscussionPosts(
    discussionId: number,
    sortBy: string = 'created',
    sortDirection: string = 'DESC',
    includeInlineAttachments: boolean = false
  ): Promise<{ posts: ForumPost[]; forumid: number; courseid: number; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      posts: ForumPost[];
      forumid: number;
      courseid: number;
      ratinginfo?: unknown;
      warnings?: unknown[];
    }>('mod_forum_get_discussion_posts', {
      discussionid: discussionId,
      sortby: sortBy,
      sortdirection: sortDirection,
      includeinlineattachments: includeInlineAttachments
    });
    return response.data;
  }

  /**
   * Get a single discussion post
   * Uses: mod_forum_get_discussion_post
   */
  async getDiscussionPost(postId: number): Promise<{ post: ForumPost; warnings?: unknown[] }> {
    const response = await this.callWebService<{ post: ForumPost; warnings?: unknown[] }>(
      'mod_forum_get_discussion_post',
      { postid: postId }
    );
    return response.data;
  }

  /**
   * Add a new discussion
   * Uses: mod_forum_add_discussion
   */
  async addDiscussion(params: AddDiscussionParams): Promise<{ discussionid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ discussionid: number; warnings?: unknown[] }>(
      'mod_forum_add_discussion',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Add a reply post
   * Uses: mod_forum_add_discussion_post
   */
  async addDiscussionPost(params: AddPostParams): Promise<{ postid: number; warnings?: unknown[]; post?: ForumPost; messages?: unknown[] }> {
    const response = await this.callWebService<{ postid: number; warnings?: unknown[]; post?: ForumPost; messages?: unknown[] }>(
      'mod_forum_add_discussion_post',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Update a discussion post
   * Uses: mod_forum_update_discussion_post
   */
  async updateDiscussionPost(
    postId: number,
    subject?: string,
    message?: string,
    messageFormat?: number,
    options?: Array<{ name: string; value: unknown }>
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_forum_update_discussion_post',
      { postid: postId, subject, message, messageformat: messageFormat, options }
    );
    return response.data;
  }

  /**
   * Delete a post
   * Uses: mod_forum_delete_post
   */
  async deletePost(postId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_forum_delete_post',
      { postid: postId }
    );
    return response.data;
  }

  /**
   * View forum (log view event)
   * Uses: mod_forum_view_forum
   */
  async viewForum(forumId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_forum_view_forum',
      { forumid: forumId }
    );
    return response.data;
  }

  /**
   * View forum discussion (log view event)
   * Uses: mod_forum_view_forum_discussion
   */
  async viewForumDiscussion(discussionId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_forum_view_forum_discussion',
      { discussionid: discussionId }
    );
    return response.data;
  }

  /**
   * Check if user can add discussion
   * Uses: mod_forum_can_add_discussion
   */
  async canAddDiscussion(forumId: number, groupId?: number): Promise<{ status: boolean; canpindiscussions?: boolean; cancreateattachment?: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      status: boolean;
      canpindiscussions?: boolean;
      cancreateattachment?: boolean;
      warnings?: unknown[];
    }>('mod_forum_can_add_discussion', { forumid: forumId, groupid: groupId });
    return response.data;
  }

  /**
   * Get forum access information
   * Uses: mod_forum_get_forum_access_information
   */
  async getForumAccessInformation(forumId: number): Promise<{
    canaddinstance: boolean;
    canviewdiscussion: boolean;
    canviewhiddentimedposts: boolean;
    canstartdiscussion: boolean;
    canreplypost: boolean;
    canaddnews: boolean;
    canreplynews: boolean;
    canviewrating: boolean;
    canviewanyrating: boolean;
    canviewallratings: boolean;
    canmovefromanyforum: boolean;
    canmovetoanyforum: boolean;
    canviewqandawithoutposting: boolean;
    canviewsubscribers: boolean;
    canmanagesubscriptions: boolean;
    canpostwithoutthrottling: boolean;
    canexportdiscussion: boolean;
    canexportforum: boolean;
    canexportpost: boolean;
    canexportownpost: boolean;
    canaddquestion: boolean;
    canallowforcesubscribe: boolean;
    cancanposttomygroups: boolean;
    cancanoverridediscussionlock: boolean;
    cancanoverridecutoff: boolean;
    candeleteanypost: boolean;
    candeleteownpost: boolean;
    caneditanypost: boolean;
    canpindiscussions: boolean;
    cansplitdiscussions: boolean;
    warnings?: unknown[];
  }> {
    const response = await this.callWebService<{
      canaddinstance: boolean;
      canviewdiscussion: boolean;
      canviewhiddentimedposts: boolean;
      canstartdiscussion: boolean;
      canreplypost: boolean;
      canaddnews: boolean;
      canreplynews: boolean;
      canviewrating: boolean;
      canviewanyrating: boolean;
      canviewallratings: boolean;
      canmovefromanyforum: boolean;
      canmovetoanyforum: boolean;
      canviewqandawithoutposting: boolean;
      canviewsubscribers: boolean;
      canmanagesubscriptions: boolean;
      canpostwithoutthrottling: boolean;
      canexportdiscussion: boolean;
      canexportforum: boolean;
      canexportpost: boolean;
      canexportownpost: boolean;
      canaddquestion: boolean;
      canallowforcesubscribe: boolean;
      cancanposttomygroups: boolean;
      cancanoverridediscussionlock: boolean;
      cancanoverridecutoff: boolean;
      candeleteanypost: boolean;
      candeleteownpost: boolean;
      caneditanypost: boolean;
      canpindiscussions: boolean;
      cansplitdiscussions: boolean;
      warnings?: unknown[];
    }>(
      'mod_forum_get_forum_access_information',
      { forumid: forumId }
    );
    return response.data;
  }

  /**
   * Set subscription state
   * Uses: mod_forum_set_subscription_state
   */
  async setSubscriptionState(
    forumId: number,
    discussionId: number,
    targetState: boolean
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_forum_set_subscription_state',
      { forumid: forumId, discussionid: discussionId, targetstate: targetState }
    );
    return response.data;
  }

  /**
   * Toggle favourite state
   * Uses: mod_forum_toggle_favourite_state
   */
  async toggleFavouriteState(
    discussionId: number,
    targetState: boolean
  ): Promise<{ id: number; userstate: { starred: boolean }; warnings?: unknown[] }> {
    const response = await this.callWebService<{ id: number; userstate: { starred: boolean }; warnings?: unknown[] }>(
      'mod_forum_toggle_favourite_state',
      { discussionid: discussionId, targetstate: targetState }
    );
    return response.data;
  }

  /**
   * Set lock state for discussion
   * Uses: mod_forum_set_lock_state
   */
  async setLockState(
    forumId: number,
    discussionId: number,
    targetState: boolean
  ): Promise<{ id: number; locked: boolean; times: { locked: number }; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      id: number;
      locked: boolean;
      times: { locked: number };
      warnings?: unknown[];
    }>('mod_forum_set_lock_state', { forumid: forumId, discussionid: discussionId, targetstate: targetState });
    return response.data;
  }

  /**
   * Set pin state for discussion
   * Uses: mod_forum_set_pin_state
   */
  async setPinState(
    discussionId: number,
    targetState: boolean
  ): Promise<{ id: number; pinned: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ id: number; pinned: boolean; warnings?: unknown[] }>(
      'mod_forum_set_pin_state',
      { discussionid: discussionId, targetstate: targetState }
    );
    return response.data;
  }
}

// Singleton instance
export const forumService = new ForumService();

export default forumService;
