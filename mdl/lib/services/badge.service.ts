// Badge Service
// Moodle API functions: core_badges_*

import MoodleApiClient from '@/lib/moodle-api';
import type { MoodleBadge, UserBadge } from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class BadgeService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get user badges
   * Uses: core_badges_get_user_badges
   */
  async getUserBadges(
    userId?: number,
    courseId?: number,
    page: number = 0,
    perPage: number = 0,
    search?: string,
    onlyPublic: boolean = false
  ): Promise<{ badges: UserBadge[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ badges: UserBadge[]; warnings?: unknown[] }>(
      'core_badges_get_user_badges',
      {
        userid: userId,
        courseid: courseId,
        page,
        perpage: perPage,
        search,
        onlypublic: onlyPublic
      }
    );
    return response.data;
  }

  /**
   * Get badge details
   * Uses: core_badges_get_badge
   */
  async getBadge(badgeId: number): Promise<{ badge: MoodleBadge; warnings?: unknown[] }> {
    const response = await this.callWebService<{ badge: MoodleBadge; warnings?: unknown[] }>(
      'core_badges_get_badge',
      { id: badgeId }
    );
    return response.data;
  }

  /**
   * Get user badge by hash
   * Uses: core_badges_get_user_badge_by_hash
   */
  async getUserBadgeByHash(hash: string): Promise<{ badge: UserBadge; warnings?: unknown[] }> {
    const response = await this.callWebService<{ badge: UserBadge; warnings?: unknown[] }>(
      'core_badges_get_user_badge_by_hash',
      { hash }
    );
    return response.data;
  }

  /**
   * Enable badges
   * Uses: core_badges_enable_badges
   */
  async enableBadges(badgeIds: number[]): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_badges_enable_badges',
      { badgeids: badgeIds }
    );
    return response.data;
  }

  /**
   * Disable badges
   * Uses: core_badges_disable_badges
   */
  async disableBadges(badgeIds: number[]): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_badges_disable_badges',
      { badgeids: badgeIds }
    );
    return response.data;
  }
}

// Singleton instance
export const badgeService = new BadgeService();

export default badgeService;
