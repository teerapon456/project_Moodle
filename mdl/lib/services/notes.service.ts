// Notes Service
// Moodle API functions: core_notes_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Note {
  id: number;
  courseid: number;
  userid: number;
  content: string;
  format: number;
  created: number;
  lastmodified: number;
  usermodified: number;
  publishstate: string;
}

export interface NoteInput {
  userid: number;
  publishstate: 'personal' | 'course' | 'site';
  courseid: number;
  text: string;
  format?: number;
  clientnoteid?: string;
}

export class NotesService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get course notes
   * Uses: core_notes_get_course_notes
   */
  async getCourseNotes(courseId: number, userId?: number): Promise<{ sitenotes: Note[]; coursenotes: Note[]; personalnotes: Note[]; canmanagesystemnotes?: boolean; canmanagecoursenotes?: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      sitenotes: Note[];
      coursenotes: Note[];
      personalnotes: Note[];
      canmanagesystemnotes?: boolean;
      canmanagecoursenotes?: boolean;
      warnings?: unknown[];
    }>('core_notes_get_course_notes', { courseid: courseId, userid: userId });
    return response.data;
  }

  /**
   * Create notes
   * Uses: core_notes_create_notes
   */
  async createNotes(notes: NoteInput[]): Promise<Array<{ clientnoteid?: string; noteid: number; warnings?: unknown[] }>> {
    const response = await this.callWebService<Array<{ clientnoteid?: string; noteid: number; warnings?: unknown[] }>>(
      'core_notes_create_notes',
      { notes }
    );
    return response.data;
  }

  /**
   * Delete notes
   * Uses: core_notes_delete_notes
   */
  async deleteNotes(notes: number[]): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'core_notes_delete_notes',
      { notes }
    );
    return response.data;
  }

  /**
   * Update notes
   * Uses: core_notes_update_notes
   */
  async updateNotes(notes: Array<{ id: number; publishstate: string; text: string; format?: number }>): Promise<unknown[]> {
    const response = await this.callWebService<unknown[]>(
      'core_notes_update_notes',
      { notes }
    );
    return response.data;
  }

  /**
   * View notes
   * Uses: core_notes_view_notes
   */
  async viewNotes(courseId: number, userId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_notes_view_notes',
      { courseid: courseId, userid: userId }
    );
    return response.data;
  }
}

// Singleton instance
export const notesService = new NotesService();

export default notesService;
