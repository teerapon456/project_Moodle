// Cohort Service
// Moodle API functions: core_cohort_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface MoodleCohort {
  id: number;
  name: string;
  idnumber: string;
  description: string;
  descriptionformat: number;
  visible: boolean;
  theme: string;
  contextid: number;
}

export interface CohortMember {
  cohortid: number;
  userid: number;
}

export interface CreateCohortParams {
  categorytype: {
    type: 'id' | 'idnumber' | 'system';
    value: string;
  };
  name: string;
  idnumber?: string;
  description?: string;
  descriptionformat?: number;
  visible?: boolean;
  theme?: string;
}

export class CohortService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get cohorts
   * Uses: core_cohort_get_cohorts
   */
  async getCohorts(cohortIds: number[]): Promise<MoodleCohort[]> {
    const response = await this.callWebService<MoodleCohort[]>(
      'core_cohort_get_cohorts',
      { cohortids: cohortIds }
    );
    return response.data;
  }

  /**
   * Get cohort by ID
   */
  async getCohortById(cohortId: number): Promise<MoodleCohort | null> {
    const cohorts = await this.getCohorts([cohortId]);
    return cohorts.length > 0 ? cohorts[0] : null;
  }

  /**
   * Search cohorts
   * Uses: core_cohort_search_cohorts
   */
  async searchCohorts(
    query: string,
    context: { contextid?: number; contextlevel?: string; instanceid?: number },
    includes: 'parents' | 'self' | 'all' = 'all',
    limitFrom: number = 0,
    limitNum: number = 25
  ): Promise<{ cohorts: MoodleCohort[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ cohorts: MoodleCohort[]; warnings?: unknown[] }>(
      'core_cohort_search_cohorts',
      { query, context, includes, limitfrom: limitFrom, limitnum: limitNum }
    );
    return response.data;
  }

  /**
   * Create cohorts
   * Uses: core_cohort_create_cohorts
   */
  async createCohorts(cohorts: CreateCohortParams[]): Promise<MoodleCohort[]> {
    const response = await this.callWebService<MoodleCohort[]>(
      'core_cohort_create_cohorts',
      { cohorts }
    );
    return response.data;
  }

  /**
   * Create a single cohort
   */
  async createCohort(cohort: CreateCohortParams): Promise<MoodleCohort> {
    const result = await this.createCohorts([cohort]);
    return result[0];
  }

  /**
   * Update cohorts
   * Uses: core_cohort_update_cohorts
   */
  async updateCohorts(
    cohorts: Array<{
      id: number;
      categorytype?: { type: 'id' | 'idnumber' | 'system'; value: string };
      name?: string;
      idnumber?: string;
      description?: string;
      descriptionformat?: number;
      visible?: boolean;
      theme?: string;
    }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_cohort_update_cohorts',
      { cohorts }
    );
    return response.data;
  }

  /**
   * Delete cohorts
   * Uses: core_cohort_delete_cohorts
   */
  async deleteCohorts(cohortIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_cohort_delete_cohorts',
      { cohortids: cohortIds }
    );
    return response.data;
  }

  /**
   * Get cohort members
   * Uses: core_cohort_get_cohort_members
   */
  async getCohortMembers(cohortIds: number[]): Promise<Array<{ cohortid: number; userids: number[] }>> {
    const response = await this.callWebService<Array<{ cohortid: number; userids: number[] }>>(
      'core_cohort_get_cohort_members',
      { cohortids: cohortIds }
    );
    return response.data;
  }

  /**
   * Add members to cohorts
   * Uses: core_cohort_add_cohort_members
   */
  async addCohortMembers(members: CohortMember[]): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'core_cohort_add_cohort_members',
      { members }
    );
    return response.data;
  }

  /**
   * Add a single member to a cohort
   */
  async addCohortMember(cohortId: number, userId: number): Promise<{ warnings?: unknown[] }> {
    return this.addCohortMembers([{ cohortid: cohortId, userid: userId }]);
  }

  /**
   * Delete members from cohorts
   * Uses: core_cohort_delete_cohort_members
   */
  async deleteCohortMembers(members: CohortMember[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_cohort_delete_cohort_members',
      { members }
    );
    return response.data;
  }

  /**
   * Remove a single member from a cohort
   */
  async removeCohortMember(cohortId: number, userId: number): Promise<null> {
    return this.deleteCohortMembers([{ cohortid: cohortId, userid: userId }]);
  }
}

// Singleton instance
export const cohortService = new CohortService();

export default cohortService;
