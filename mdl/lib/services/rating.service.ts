// Rating Service
// Moodle API functions: core_rating_*

import MoodleApiClient from '@/lib/moodle-api';
const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Rating {
  itemid: number;
  scaleid: number;
  userid: number;
  rating: number;
  aggregation: number;
  aggregate: number;
  count: number;
  canrate: boolean;
  canviewaggregate: boolean;
}

export interface RatingItem {
  itemid: number;
  scaleid?: number;
  userid?: number;
  aggregate?: number;
  aggregatestr?: string;
  aggregatelabel?: string;
  count?: number;
  rating?: number;
  canrate?: boolean;
  canviewaggregate?: boolean;
}

export class RatingService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get item ratings
   * Uses: core_rating_get_item_ratings
   */
  async getItemRatings(contextLevel: string, instanceId: number, component: string, ratingArea: string, itemId: number, scaleId: number, sort: string): Promise<{ ratings: RatingItem[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ ratings: RatingItem[]; warnings?: unknown[] }>(
      'core_rating_get_item_ratings',
      {
        contextlevel: contextLevel,
        instanceid: instanceId,
        component,
        ratingarea: ratingArea,
        itemid: itemId,
        scaleid: scaleId,
        sort
      }
    );
    return response.data;
  }

  /**
   * Add rating
   * Uses: core_rating_add_rating
   */
  async addRating(contextLevel: string, instanceId: number, component: string, ratingArea: string, itemId: number, scaleId: number, rating: number, ratedUserId: number, aggregation: number): Promise<{ success: boolean; aggregate?: string; count?: number; itemid?: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      success: boolean;
      aggregate?: string;
      count?: number;
      itemid?: number;
      warnings?: unknown[];
    }>('core_rating_add_rating', {
      contextlevel: contextLevel,
      instanceid: instanceId,
      component,
      ratingarea: ratingArea,
      itemid: itemId,
      scaleid: scaleId,
      rating,
      rateduserid: ratedUserId,
      aggregation
    });
    return response.data;
  }
}

// Singleton instance
export const ratingService = new RatingService();

export default ratingService;
