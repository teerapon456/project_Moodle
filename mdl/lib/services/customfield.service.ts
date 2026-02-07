// Custom Field Service
// Moodle API functions: core_customfield_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface CustomFieldCategory {
  id: number;
  component: string;
  area: string;
  itemid: number;
  contextid: number;
  name: string;
  description: string;
  descriptionformat: number;
  sortorder: number;
  timecreated: number;
  timemodified: number;
}

export interface CustomField {
  id: number;
  shortname: string;
  name: string;
  type: string;
  description: string;
  descriptionformat: number;
  sortorder: number;
  categoryid: number;
  configdata: string;
  timecreated: number;
  timemodified: number;
}

export interface CustomFieldData {
  id: number;
  fieldid: number;
  instanceid: number;
  intvalue: number;
  decvalue: number;
  shortcharvalue: string;
  charvalue: string;
  value: string;
  valueformat: number;
  timecreated: number;
  timemodified: number;
  contextid: number;
}

export class CustomFieldService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Delete category
   * Uses: core_customfield_delete_category
   */
  async deleteCategory(id: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_customfield_delete_category',
      { id }
    );
    return response.data;
  }

  /**
   * Delete field
   * Uses: core_customfield_delete_field
   */
  async deleteField(id: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_customfield_delete_field',
      { id }
    );
    return response.data;
  }

  /**
   * Move category
   * Uses: core_customfield_move_category
   */
  async moveCategory(id: number, beforeid?: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_customfield_move_category',
      { id, beforeid }
    );
    return response.data;
  }

  /**
   * Move field
   * Uses: core_customfield_move_field
   */
  async moveField(id: number, categoryid: number, beforeid?: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_customfield_move_field',
      { id, categoryid, beforeid }
    );
    return response.data;
  }

  /**
   * Create category
   * Uses: core_customfield_create_category
   */
  async createCategory(component: string, area: string, itemid: number): Promise<number> {
    const response = await this.callWebService<number>(
      'core_customfield_create_category',
      { component, area, itemid }
    );
    return response.data;
  }

  /**
   * Reload template
   * Uses: core_customfield_reload_template
   */
  async reloadTemplate(component: string, area: string, itemid: number): Promise<{ component: string; area: string; itemid: number; usescategories: boolean; categories: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      component: string;
      area: string;
      itemid: number;
      usescategories: boolean;
      categories: unknown[];
      warnings?: unknown[];
    }>('core_customfield_reload_template', { component, area, itemid });
    return response.data;
  }
}

// Singleton instance
export const customFieldService = new CustomFieldService();

export default customFieldService;
