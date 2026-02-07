// Tag Service
// Moodle API functions: core_tag_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Tag {
  id: number;
  name: string;
  rawname: string;
  isstandard: boolean;
  tagcollid: number;
  taginstanceid: number;
  taginstancecontextid: number;
  itemid: number;
  ordering: number;
  flag: boolean;
}

export interface TagIndex {
  id: number;
  tag: string;
  tagid: number;
  tc: number;
  ti: number;
  itemtype: string;
  itemcomponent: string;
  tagcollid: number;
  title: string;
  content: string;
  hascontent: number;
  anchor: string;
}

export interface TagArea {
  id: number;
  component: string;
  itemtype: string;
  enabled: boolean;
  tagcollid: number;
  callback: string;
  callbackfile: string;
  showstandard: number;
  multiplecontexts: boolean;
}

export interface TagCollection {
  id: number;
  name: string;
  isdefault: boolean;
  component: string;
  sortorder: number;
  searchable: boolean;
  customurl: string;
}

export class TagService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  /**
   * Get tag index
   * Uses: core_tag_get_tagindex
   */
  async getTagIndex(tagIndex: { tag?: string; tc?: number; ti?: number; itemtype?: string; itemcomponent?: string; ta?: number; excl?: boolean; from?: number; ctx?: number; rec?: boolean; page?: number }): Promise<TagIndex> {
    const response = await this.client.callFunction<TagIndex>(
      'core_tag_get_tagindex',
      { tagindex: tagIndex }
    );
    return response;
  }

  /**
   * Get tags
   * Uses: core_tag_get_tags
   */
  async getTags(tags: Array<{ id: number }>): Promise<{ tags: Tag[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ tags: Tag[]; warnings?: unknown[] }>(
      'core_tag_get_tags',
      { tags }
    );
    return response;
  }

  /**
   * Get tag areas
   * Uses: core_tag_get_tag_areas
   */
  async getTagAreas(): Promise<{ areas: TagArea[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ areas: TagArea[]; warnings?: unknown[] }>(
      'core_tag_get_tag_areas',
      {}
    );
    return response;
  }

  /**
   * Get tag collections
   * Uses: core_tag_get_tag_collections
   */
  async getTagCollections(): Promise<{ collections: TagCollection[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ collections: TagCollection[]; warnings?: unknown[] }>(
      'core_tag_get_tag_collections',
      {}
    );
    return response;
  }

  /**
   * Get tag cloud
   * Uses: core_tag_get_tag_cloud
   */
  async getTagCloud(tagCollid?: number, isStandard?: boolean, limit?: number, sort?: string, search?: string, fromContextId?: number, contextId?: number, rec?: boolean): Promise<{ tags: Array<{ name: string; viewurl: string; flag: boolean; isstandard: boolean; count: number; size: number }>; tagscount: number; totalcount: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      tags: Array<{ name: string; viewurl: string; flag: boolean; isstandard: boolean; count: number; size: number }>;
      tagscount: number;
      totalcount: number;
      warnings?: unknown[];
    }>('core_tag_get_tag_cloud', {
      tagcollid: tagCollid,
      isstandard: isStandard,
      limit,
      sort,
      search,
      fromctx: fromContextId,
      ctx: contextId,
      rec
    });
    return response;
  }

  /**
   * Update tags
   * Uses: core_tag_update_tags
   */
  async updateTags(tags: Array<{ id: number; rawname?: string; description?: string; descriptionformat?: number; flag?: number; official?: boolean; isstandard?: boolean }>): Promise<{ warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ warnings?: unknown[] }>(
      'core_tag_update_tags',
      { tags }
    );
    return response;
  }

  /**
   * Get tagindex per area
   * Uses: core_tag_get_tagindex_per_area
   */
  async getTagIndexPerArea(tagIndex: { tag?: string; tc?: number; ta?: number; excl?: boolean; from?: number; ctx?: number; rec?: boolean; page?: number }): Promise<{ tagindexes: TagIndex[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ tagindexes: TagIndex[]; warnings?: unknown[] }>(
      'core_tag_get_tagindex_per_area',
      { tagindex: tagIndex }
    );
    return response;
  }
}

// Singleton instance
export const tagService = new TagService();

export default tagService;
