// Book Service
// Moodle API functions: mod_book_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Book {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  numbering: number;
  navstyle: number;
  customtitles: boolean;
  revision: number;
  timecreated: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface BookChapter {
  id: number;
  bookid: number;
  pagenum: number;
  subchapter: boolean;
  title: string;
  content: string;
  contentformat: number;
  hidden: boolean;
  timecreated: number;
  timemodified: number;
}

export class BookService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get books by courses
   * Uses: mod_book_get_books_by_courses
   */
  async getBooksByCourses(courseIds?: number[]): Promise<{ books: Book[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ books: Book[]; warnings?: unknown[] }>(
      'mod_book_get_books_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View book (log event)
   * Uses: mod_book_view_book
   */
  async viewBook(bookId: number, chapterId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_book_view_book',
      { bookid: bookId, chapterid: chapterId }
    );
    return response.data;
  }
}

// Singleton instance
export const bookService = new BookService();

export default bookService;
