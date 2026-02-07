// Database Service
// Moodle API functions: mod_data_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Database {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  comments: boolean;
  timeavailablefrom: number;
  timeavailableto: number;
  timeviewfrom: number;
  timeviewto: number;
  requiredentries: number;
  requiredentriestoview: number;
  maxentries: number;
  rssarticles: number;
  singletemplate: string;
  listtemplate: string;
  listtemplateheader: string;
  listtemplatefooter: string;
  addtemplate: string;
  rsstemplate: string;
  rsstitletemplate: string;
  csstemplate: string;
  jstemplate: string;
  asearchtemplate: string;
  approval: boolean;
  manageapproved: boolean;
  scale: number;
  assessed: number;
  assesstimestart: number;
  assesstimefinish: number;
  defaultsort: number;
  defaultsortdir: number;
  editany: boolean;
  notification: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface DatabaseField {
  id: number;
  dataid: number;
  type: string;
  name: string;
  description: string;
  required: boolean;
  param1: string;
  param2: string;
  param3: string;
  param4: string;
  param5: string;
  param6: string;
  param7: string;
  param8: string;
  param9: string;
  param10: string;
}

export interface DatabaseEntry {
  id: number;
  userid: number;
  groupid: number;
  dataid: number;
  timecreated: number;
  timemodified: number;
  approved: boolean;
  canmanageentry: boolean;
  fullname?: string;
  contents?: unknown[];
  tags?: unknown[];
}

export class DatabaseService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get databases by courses
   * Uses: mod_data_get_databases_by_courses
   */
  async getDatabasesByCourses(courseIds?: number[]): Promise<{ databases: Database[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ databases: Database[]; warnings?: unknown[] }>(
      'mod_data_get_databases_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Get database access information
   * Uses: mod_data_get_data_access_information
   */
  async getAccessInformation(databaseId: number, groupId?: number): Promise<unknown> {
    const response = await this.callWebService<unknown>(
      'mod_data_get_data_access_information',
      { databaseid: databaseId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * View database (log event)
   * Uses: mod_data_view_database
   */
  async viewDatabase(databaseId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_data_view_database',
      { databaseid: databaseId }
    );
    return response.data;
  }

  /**
   * Get fields
   * Uses: mod_data_get_fields
   */
  async getFields(databaseId: number): Promise<{ fields: DatabaseField[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ fields: DatabaseField[]; warnings?: unknown[] }>(
      'mod_data_get_fields',
      { databaseid: databaseId }
    );
    return response.data;
  }

  /**
   * Get entries
   * Uses: mod_data_get_entries
   */
  async getEntries(
    databaseId: number,
    groupId?: number,
    returnContents?: boolean,
    sort?: number,
    order?: string,
    page?: number,
    perPage?: number
  ): Promise<{ entries: DatabaseEntry[]; totalcount: number; totalfilesize: number; listviewcontents?: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      entries: DatabaseEntry[];
      totalcount: number;
      totalfilesize: number;
      listviewcontents?: string;
      warnings?: unknown[];
    }>('mod_data_get_entries', {
      databaseid: databaseId,
      groupid: groupId,
      returncontents: returnContents,
      sort,
      order,
      page,
      perpage: perPage
    });
    return response.data;
  }

  /**
   * Get entry
   * Uses: mod_data_get_entry
   */
  async getEntry(entryId: number, returnContents?: boolean): Promise<{ entry: DatabaseEntry; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ entry: DatabaseEntry; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_data_get_entry',
      { entryid: entryId, returncontents: returnContents }
    );
    return response.data;
  }

  /**
   * Search entries
   * Uses: mod_data_search_entries
   */
  async searchEntries(
    databaseId: number,
    groupId?: number,
    returnContents?: boolean,
    search?: string,
    advSearch?: Array<{ name: string; value: string }>,
    sort?: number,
    order?: string,
    page?: number,
    perPage?: number
  ): Promise<{ entries: DatabaseEntry[]; totalcount: number; maxcount?: number; listviewcontents?: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      entries: DatabaseEntry[];
      totalcount: number;
      maxcount?: number;
      listviewcontents?: string;
      warnings?: unknown[];
    }>('mod_data_search_entries', {
      databaseid: databaseId,
      groupid: groupId,
      returncontents: returnContents,
      search,
      advsearch: advSearch,
      sort,
      order,
      page,
      perpage: perPage
    });
    return response.data;
  }

  /**
   * Add entry
   * Uses: mod_data_add_entry
   */
  async addEntry(databaseId: number, groupId: number, data: Array<{ fieldid: number; subfield?: string; value: string }>): Promise<{ newentryid: number; generalnotifications: string[]; fieldnotifications: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      newentryid: number;
      generalnotifications: string[];
      fieldnotifications: unknown[];
      warnings?: unknown[];
    }>('mod_data_add_entry', { databaseid: databaseId, groupid: groupId, data });
    return response.data;
  }

  /**
   * Update entry
   * Uses: mod_data_update_entry
   */
  async updateEntry(entryId: number, data: Array<{ fieldid: number; subfield?: string; value: string }>): Promise<{ updated: boolean; generalnotifications: string[]; fieldnotifications: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      updated: boolean;
      generalnotifications: string[];
      fieldnotifications: unknown[];
      warnings?: unknown[];
    }>('mod_data_update_entry', { entryid: entryId, data });
    return response.data;
  }

  /**
   * Delete entry
   * Uses: mod_data_delete_entry
   */
  async deleteEntry(entryId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_data_delete_entry',
      { entryid: entryId }
    );
    return response.data;
  }

  /**
   * Approve entry
   * Uses: mod_data_approve_entry
   */
  async approveEntry(entryId: number, approve?: boolean): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_data_approve_entry',
      { entryid: entryId, approve }
    );
    return response.data;
  }

  /**
   * Delete saved preset
   * Uses: mod_data_delete_saved_preset
   */
  async deleteSavedPreset(databaseId: number, presetNames: string[]): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'mod_data_delete_saved_preset',
      { databaseid: databaseId, presetnames: presetNames }
    );
    return response.data;
  }

  /**
   * Get mapping information
   * Uses: mod_data_get_mapping_information
   */
  async getMappingInformation(cmId: number, importedPreset: string): Promise<{ data: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ data: unknown; warnings?: unknown[] }>(
      'mod_data_get_mapping_information',
      { cmid: cmId, importedpreset: importedPreset }
    );
    return response.data;
  }
}

// Singleton instance
export const databaseService = new DatabaseService();

export default databaseService;
