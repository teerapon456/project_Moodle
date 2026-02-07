// Wiki Service
// Moodle API functions: mod_wiki_*
import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Wiki {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  timecreated: number;
  timemodified: number;
  firstpagetitle: string;
  wikimode: string;
  defaultformat: string;
  forceformat: number;
  editbegin: number;
  editend: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface WikiSubwiki {
  id: number;
  wikiid: number;
  groupid: number;
  userid: number;
  canedit: boolean;
}

export interface WikiPage {
  id: number;
  subwikiid: number;
  title: string;
  timecreated: number;
  timemodified: number;
  timerendered: number;
  userid: number;
  pageviews: number;
  readonly: number;
  caneditpage: boolean;
  firstpage: boolean;
  cachedcontent: string;
  contentformat: number;
  contentsize?: number;
  tags?: unknown[];
}

export interface WikiPageContent {
  cachekey: string;
  cachedcontent: string;
  contentformat: number;
}

export class WikiService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  /**
   * Get wikis by courses
   * Uses: mod_wiki_get_wikis_by_courses
   */
  async getWikisByCourses(courseIds?: number[]): Promise<{ wikis: Wiki[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ wikis: Wiki[]; warnings?: unknown[] }>(
      'mod_wiki_get_wikis_by_courses',
      { courseids: courseIds }
    );
    return response;
  }

  /**
   * View wiki (log event)
   * Uses: mod_wiki_view_wiki
   */
  async viewWiki(wikiId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_wiki_view_wiki',
      { wikiid: wikiId }
    );
    return response;
  }

  /**
   * View page (log event)
   * Uses: mod_wiki_view_page
   */
  async viewPage(pageId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_wiki_view_page',
      { pageid: pageId }
    );
    return response;
  }

  /**
   * Get subwikis
   * Uses: mod_wiki_get_subwikis
   */
  async getSubwikis(wikiId: number): Promise<{ subwikis: WikiSubwiki[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ subwikis: WikiSubwiki[]; warnings?: unknown[] }>(
      'mod_wiki_get_subwikis',
      { wikiid: wikiId }
    );
    return response;
  }

  /**
   * Get subwiki pages
   * Uses: mod_wiki_get_subwiki_pages
   */
  async getSubwikiPages(
    wikiId: number,
    groupId?: number,
    userId?: number,
    options?: { sortBy?: string; sortDirection?: string; includeContent?: number }
  ): Promise<{ pages: WikiPage[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ pages: WikiPage[]; warnings?: unknown[] }>(
      'mod_wiki_get_subwiki_pages',
      {
        wikiid: wikiId,
        groupid: groupId,
        userid: userId,
        options: options ? {
          sortby: options.sortBy,
          sortdirection: options.sortDirection,
          includecontent: options.includeContent
        } : undefined
      }
    );
    return response;
  }

  /**
   * Get subwiki files
   * Uses: mod_wiki_get_subwiki_files
   */
  async getSubwikiFiles(wikiId: number, groupId?: number, userId?: number): Promise<{ files: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ files: unknown[]; warnings?: unknown[] }>(
      'mod_wiki_get_subwiki_files',
      { wikiid: wikiId, groupid: groupId, userid: userId }
    );
    return response;
  }

  /**
   * Get page contents
   * Uses: mod_wiki_get_page_contents
   */
  async getPageContents(pageId: number): Promise<{ page: WikiPageContent; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ page: WikiPageContent; warnings?: unknown[] }>(
      'mod_wiki_get_page_contents',
      { pageid: pageId }
    );
    return response;
  }

  /**
   * Get page for editing
   * Uses: mod_wiki_get_page_for_editing
   */
  async getPageForEditing(pageId: number, section?: string, lockOnly?: boolean): Promise<{ pagesection: { content: string; contentformat: string; version: number; warnings?: unknown[] } }> {
    const response = await this.client.callFunction<{ pagesection: { content: string; contentformat: string; version: number; warnings?: unknown[] } }>(
      'mod_wiki_get_page_for_editing',
      { pageid: pageId, section, lockonly: lockOnly }
    );
    return response;
  }

  /**
   * Create new page
   * Uses: mod_wiki_new_page
   */
  async newPage(
    title: string,
    content: string,
    wikiId?: number,
    subwikiId?: number,
    groupId?: number,
    userId?: number,
    contentFormat?: string
  ): Promise<{ pageid: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ pageid: number; warnings?: unknown[] }>(
      'mod_wiki_new_page',
      {
        title,
        content,
        wikiid: wikiId,
        subwikiid: subwikiId,
        groupid: groupId,
        userid: userId,
        contentformat: contentFormat
      }
    );
    return response;
  }

  /**
   * Edit page
   * Uses: mod_wiki_edit_page
   */
  async editPage(pageId: number, content: string, section?: string): Promise<{ pageid: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ pageid: number; warnings?: unknown[] }>(
      'mod_wiki_edit_page',
      { pageid: pageId, content, section }
    );
    return response;
  }
}

// Singleton instance
export const wikiService = new WikiService();

export default wikiService;
