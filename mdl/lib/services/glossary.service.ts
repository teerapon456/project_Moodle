// Glossary Service
// Moodle API functions: mod_glossary_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Glossary {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  allowduplicatedentries: number;
  displayformat: string;
  mainglossary: boolean;
  showspecial: boolean;
  showalphabet: boolean;
  showall: boolean;
  allowcomments: boolean;
  allowprintview: boolean;
  usedynalink: boolean;
  defaultapproval: boolean;
  approvaldisplayformat: string;
  globalglossary: boolean;
  entbypage: number;
  editalways: boolean;
  rsstype: number;
  rssarticles: number;
  assessed: number;
  assesstimestart: number;
  assesstimefinish: number;
  scale: number;
  timecreated: number;
  timemodified: number;
  completionentries: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
  browsemodes: string[];
  canaddentry: boolean;
}

export interface GlossaryEntry {
  id: number;
  glossaryid: number;
  userid: number;
  userfullname: string;
  userpictureurl: string;
  concept: string;
  definition: string;
  definitionformat: number;
  definitiontrust: boolean;
  definitioninlinefiles: unknown[];
  attachment: boolean;
  attachments: unknown[];
  timecreated: number;
  timemodified: number;
  teacherentry: boolean;
  sourceglossaryid: number;
  usedynalink: boolean;
  casesensitive: boolean;
  fullmatch: boolean;
  approved: boolean;
  tags: unknown[];
}

export interface GlossaryCategory {
  id: number;
  glossaryid: number;
  name: string;
  usedynalink: boolean;
}

export class GlossaryService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get glossaries by courses
   * Uses: mod_glossary_get_glossaries_by_courses
   */
  async getGlossariesByCourses(courseIds?: number[]): Promise<{ glossaries: Glossary[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ glossaries: Glossary[]; warnings?: unknown[] }>(
      'mod_glossary_get_glossaries_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View glossary (log event)
   * Uses: mod_glossary_view_glossary
   */
  async viewGlossary(id: number, mode: string): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_glossary_view_glossary',
      { id, mode }
    );
    return response.data;
  }

  /**
   * View entry (log event)
   * Uses: mod_glossary_view_entry
   */
  async viewEntry(id: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_glossary_view_entry',
      { id }
    );
    return response.data;
  }

  /**
   * Get entries by letter
   * Uses: mod_glossary_get_entries_by_letter
   */
  async getEntriesByLetter(id: number, letter: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_letter',
      { id, letter, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by date
   * Uses: mod_glossary_get_entries_by_date
   */
  async getEntriesByDate(id: number, order?: string, sort?: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_date',
      { id, order, sort, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by category
   * Uses: mod_glossary_get_entries_by_category
   */
  async getEntriesByCategory(id: number, categoryId: number, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_category',
      { id, categoryid: categoryId, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by author
   * Uses: mod_glossary_get_entries_by_author
   */
  async getEntriesByAuthor(id: number, letter: string, field?: string, sort?: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_author',
      { id, letter, field, sort, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by author ID
   * Uses: mod_glossary_get_entries_by_author_id
   */
  async getEntriesByAuthorId(id: number, authorId: number, order?: string, sort?: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_author_id',
      { id, authorid: authorId, order, sort, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by search
   * Uses: mod_glossary_get_entries_by_search
   */
  async getEntriesBySearch(id: number, query: string, fullSearch?: boolean, order?: string, sort?: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_search',
      { id, query, fullsearch: fullSearch, order, sort, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entries by term
   * Uses: mod_glossary_get_entries_by_term
   */
  async getEntriesByTerm(id: number, term: string, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; entries: GlossaryEntry[]; ratinginfo?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entries_by_term',
      { id, term, from, limit, options }
    );
    return response.data;
  }

  /**
   * Get entry by ID
   * Uses: mod_glossary_get_entry_by_id
   */
  async getEntryById(id: number): Promise<{ entry: GlossaryEntry; ratinginfo?: unknown; permissions?: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ entry: GlossaryEntry; ratinginfo?: unknown; permissions?: unknown; warnings?: unknown[] }>(
      'mod_glossary_get_entry_by_id',
      { id }
    );
    return response.data;
  }

  /**
   * Get categories
   * Uses: mod_glossary_get_categories
   */
  async getCategories(id: number, from?: number, limit?: number): Promise<{ count: number; categories: GlossaryCategory[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; categories: GlossaryCategory[]; warnings?: unknown[] }>(
      'mod_glossary_get_categories',
      { id, from, limit }
    );
    return response.data;
  }

  /**
   * Get authors
   * Uses: mod_glossary_get_authors
   */
  async getAuthors(id: number, from?: number, limit?: number, options?: { includenotapproved?: boolean }): Promise<{ count: number; authors: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ count: number; authors: unknown[]; warnings?: unknown[] }>(
      'mod_glossary_get_authors',
      { id, from, limit, options }
    );
    return response.data;
  }

  /**
   * Add entry
   * Uses: mod_glossary_add_entry
   */
  async addEntry(glossaryId: number, concept: string, definition: string, definitionFormat: number, options?: unknown): Promise<{ entryid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ entryid: number; warnings?: unknown[] }>(
      'mod_glossary_add_entry',
      { glossaryid: glossaryId, concept, definition, definitionformat: definitionFormat, options }
    );
    return response.data;
  }

  /**
   * Update entry
   * Uses: mod_glossary_update_entry
   */
  async updateEntry(entryId: number, concept: string, definition: string, definitionFormat: number, options?: unknown): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'mod_glossary_update_entry',
      { entryid: entryId, concept, definition, definitionformat: definitionFormat, options }
    );
    return response.data;
  }

  /**
   * Delete entry
   * Uses: mod_glossary_delete_entry
   */
  async deleteEntry(entryId: number): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'mod_glossary_delete_entry',
      { entryid: entryId }
    );
    return response.data;
  }

  /**
   * Prepare entry for edition
   * Uses: mod_glossary_prepare_entry_for_edition
   */
  async prepareEntryForEdition(entryId: number): Promise<{ inlineattachmentsid: number; attachmentsid: number; areas: unknown[]; aliases: string[]; categories: number[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      inlineattachmentsid: number;
      attachmentsid: number;
      areas: unknown[];
      aliases: string[];
      categories: number[];
      warnings?: unknown[];
    }>('mod_glossary_prepare_entry_for_edition', { entryid: entryId });
    return response.data;
  }
}

// Singleton instance
export const glossaryService = new GlossaryService();

export default glossaryService;
