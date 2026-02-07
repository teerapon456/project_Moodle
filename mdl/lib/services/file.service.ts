// File Service
// Moodle API functions: core_files_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  MoodleFile,
  FilesResponse,
  UploadFileParams,
  UploadFileResponse,
  PrivateFilesInfo
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class FileService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get files
   * Uses: core_files_get_files
   */
  async getFiles(
    contextId: number,
    component: string,
    filearea: string,
    itemId: number,
    filepath: string = '/',
    filename: string = '',
    modified?: number,
    contextLevel?: string,
    instanceId?: number
  ): Promise<FilesResponse> {
    const response = await this.callWebService<FilesResponse>(
      'core_files_get_files',
      {
        contextid: contextId,
        component,
        filearea,
        itemid: itemId,
        filepath,
        filename,
        modified,
        contextlevel: contextLevel,
        instanceid: instanceId
      }
    );
    return response.data;
  }

  /**
   * Upload a file
   * Uses: core_files_upload
   */
  async uploadFile(params: UploadFileParams): Promise<UploadFileResponse> {
    const response = await this.callWebService<UploadFileResponse>(
      'core_files_upload',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Get unused draft item ID
   * Uses: core_files_get_unused_draft_itemid
   */
  async getUnusedDraftItemId(): Promise<{ itemid: number; component: string; contextid: number }> {
    const response = await this.callWebService<{
      itemid: number;
      component: string;
      contextid: number;
    }>('core_files_get_unused_draft_itemid');
    return response.data;
  }

  /**
   * Delete draft files
   * Uses: core_files_delete_draft_files
   */
  async deleteDraftFiles(
    draftItemId: number,
    files: Array<{ filepath: string; filename: string }>
  ): Promise<{ parentpaths: string[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ parentpaths: string[]; warnings?: unknown[] }>(
      'core_files_delete_draft_files',
      { draftitemid: draftItemId, files }
    );
    return response.data;
  }

  /**
   * Add user private files
   * Uses: core_user_add_user_private_files
   */
  async addUserPrivateFiles(draftId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_user_add_user_private_files',
      { draftid: draftId }
    );
    return response.data;
  }

  /**
   * Get private files info
   * Uses: core_user_get_private_files_info
   */
  async getPrivateFilesInfo(userId?: number): Promise<PrivateFilesInfo> {
    const response = await this.callWebService<PrivateFilesInfo>(
      'core_user_get_private_files_info',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * Prepare private files for edition
   * Uses: core_user_prepare_private_files_for_edition
   */
  async preparePrivateFilesForEdition(): Promise<{ draftareainfo: { itemid: number }; warnings?: unknown[] }> {
    const response = await this.callWebService<{ draftareainfo: { itemid: number }; warnings?: unknown[] }>(
      'core_user_prepare_private_files_for_edition'
    );
    return response.data;
  }

  /**
   * Update private files
   * Uses: core_user_update_private_files
   */
  async updatePrivateFiles(draftId: number): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'core_user_update_private_files',
      { draftid: draftId }
    );
    return response.data;
  }
}

// Singleton instance
export const fileService = new FileService();

export default fileService;
