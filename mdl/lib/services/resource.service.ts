// Resource Service
// Moodle API functions: mod_resource_*, mod_folder_*, mod_url_*, mod_page_*, mod_label_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Resource {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  contentfiles: unknown[];
  tobemigrated: number;
  legacyfiles: number;
  legacyfileslast: number;
  display: number;
  displayoptions: string;
  filterfiles: number;
  revision: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface Folder {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  revision: number;
  timemodified: number;
  display: number;
  showexpanded: boolean;
  showdownloadfolder: boolean;
  forcedownload: boolean;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface Url {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  externalurl: string;
  display: number;
  displayoptions: string;
  parameters: string;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface Page {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  content: string;
  contentformat: number;
  contentfiles: unknown[];
  legacyfiles: number;
  legacyfileslast: number;
  display: number;
  displayoptions: string;
  revision: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface Label {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export class ResourceService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  // ========== RESOURCE (File) ==========

  /**
   * Get resources by courses
   * Uses: mod_resource_get_resources_by_courses
   */
  async getResourcesByCourses(courseIds?: number[]): Promise<{ resources: Resource[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ resources: Resource[]; warnings?: unknown[] }>(
      'mod_resource_get_resources_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View resource (log event)
   * Uses: mod_resource_view_resource
   */
  async viewResource(resourceId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_resource_view_resource',
      { resourceid: resourceId }
    );
    return response.data;
  }

  // ========== FOLDER ==========

  /**
   * Get folders by courses
   * Uses: mod_folder_get_folders_by_courses
   */
  async getFoldersByCourses(courseIds?: number[]): Promise<{ folders: Folder[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ folders: Folder[]; warnings?: unknown[] }>(
      'mod_folder_get_folders_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View folder (log event)
   * Uses: mod_folder_view_folder
   */
  async viewFolder(folderId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_folder_view_folder',
      { folderid: folderId }
    );
    return response.data;
  }

  // ========== URL ==========

  /**
   * Get URLs by courses
   * Uses: mod_url_get_urls_by_courses
   */
  async getUrlsByCourses(courseIds?: number[]): Promise<{ urls: Url[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ urls: Url[]; warnings?: unknown[] }>(
      'mod_url_get_urls_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View URL (log event)
   * Uses: mod_url_view_url
   */
  async viewUrl(urlId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_url_view_url',
      { urlid: urlId }
    );
    return response.data;
  }

  // ========== PAGE ==========

  /**
   * Get pages by courses
   * Uses: mod_page_get_pages_by_courses
   */
  async getPagesByCourses(courseIds?: number[]): Promise<{ pages: Page[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ pages: Page[]; warnings?: unknown[] }>(
      'mod_page_get_pages_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View page (log event)
   * Uses: mod_page_view_page
   */
  async viewPage(pageId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_page_view_page',
      { pageid: pageId }
    );
    return response.data;
  }

  // ========== LABEL ==========

  /**
   * Get labels by courses
   * Uses: mod_label_get_labels_by_courses
   */
  async getLabelsByCourses(courseIds?: number[]): Promise<{ labels: Label[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ labels: Label[]; warnings?: unknown[] }>(
      'mod_label_get_labels_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }
}

// Singleton instance
export const resourceService = new ResourceService();

export default resourceService;
