// Lesson Service
// Moodle API functions: mod_lesson_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Lesson {
  id: number;
  course: number;
  coursemodule: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  practice: boolean;
  modattempts: boolean;
  usepassword: boolean;
  password: string;
  dependency: number;
  conditions: string;
  grade: number;
  custom: boolean;
  ongoing: boolean;
  usemaxgrade: number;
  maxanswers: number;
  maxattempts: number;
  review: boolean;
  nextpagedefault: number;
  feedback: boolean;
  minquestions: number;
  maxpages: number;
  timelimit: number;
  retake: boolean;
  activitylink: number;
  mediafile: string;
  mediaheight: number;
  mediawidth: number;
  mediaclose: number;
  slideshow: boolean;
  width: number;
  height: number;
  bgcolor: string;
  displayleft: boolean;
  displayleftif: number;
  progressbar: boolean;
  available: number;
  deadline: number;
  timemodified: number;
  completionendreached: boolean;
  completiontimespent: number;
  allowofflineattempts: boolean;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
}

export interface LessonPage {
  id: number;
  lessonid: number;
  prevpageid: number;
  nextpageid: number;
  qtype: number;
  qoption: number;
  layout: number;
  display: number;
  timecreated: number;
  timemodified: number;
  title: string;
  contents: string;
  contentsformat: number;
  displayinmenublock: boolean;
  type: number;
  typeid: number;
  typestring: string;
}

export interface LessonAttempt {
  id: number;
  lessonid: number;
  pageid: number;
  userid: number;
  answerid: number;
  retry: number;
  correct: boolean;
  useranswer: string;
  timeseen: number;
}

export class LessonService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get lessons by courses
   * Uses: mod_lesson_get_lessons_by_courses
   */
  async getLessonsByCourses(courseIds?: number[]): Promise<{ lessons: Lesson[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ lessons: Lesson[]; warnings?: unknown[] }>(
      'mod_lesson_get_lessons_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Get lesson access information
   * Uses: mod_lesson_get_lesson_access_information
   */
  async getAccessInformation(lessonId: number): Promise<unknown> {
    const response = await this.callWebService<unknown>(
      'mod_lesson_get_lesson_access_information',
      { lessonid: lessonId }
    );
    return response.data;
  }

  /**
   * View lesson (log event)
   * Uses: mod_lesson_view_lesson
   */
  async viewLesson(lessonId: number, password?: string): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_lesson_view_lesson',
      { lessonid: lessonId, password }
    );
    return response.data;
  }

  /**
   * Get questions attempts
   * Uses: mod_lesson_get_questions_attempts
   */
  async getQuestionsAttempts(lessonId: number, attempt: number, correct?: boolean, pageId?: number, userId?: number): Promise<{ attempts: LessonAttempt[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ attempts: LessonAttempt[]; warnings?: unknown[] }>(
      'mod_lesson_get_questions_attempts',
      { lessonid: lessonId, attempt, correct, pageid: pageId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get user grade
   * Uses: mod_lesson_get_user_grade
   */
  async getUserGrade(lessonId: number, userId?: number): Promise<{ grade: number; formattedgrade: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{ grade: number; formattedgrade: string; warnings?: unknown[] }>(
      'mod_lesson_get_user_grade',
      { lessonid: lessonId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get user attempt grade
   * Uses: mod_lesson_get_user_attempt_grade
   */
  async getUserAttemptGrade(lessonId: number, lessonAttempt: number, userId?: number): Promise<{ grade: { nquestions: number; attempts: number; total: number; earned: number; grade: number; nmanual: number; manualpoints: number }; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      grade: {
        nquestions: number;
        attempts: number;
        total: number;
        earned: number;
        grade: number;
        nmanual: number;
        manualpoints: number;
      };
      warnings?: unknown[];
    }>('mod_lesson_get_user_attempt_grade', { lessonid: lessonId, lessonattempt: lessonAttempt, userid: userId });
    return response.data;
  }

  /**
   * Get attempts overview
   * Uses: mod_lesson_get_attempts_overview
   */
  async getAttemptsOverview(lessonId: number, groupId?: number): Promise<{ data: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ data: unknown; warnings?: unknown[] }>(
      'mod_lesson_get_attempts_overview',
      { lessonid: lessonId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get user attempt
   * Uses: mod_lesson_get_user_attempt
   */
  async getUserAttempt(lessonId: number, attempt: number, userId?: number): Promise<{ answerpages: unknown[]; userstats: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ answerpages: unknown[]; userstats: unknown; warnings?: unknown[] }>(
      'mod_lesson_get_user_attempt',
      { lessonid: lessonId, lessonattempt: attempt, userid: userId }
    );
    return response.data;
  }

  /**
   * Get pages
   * Uses: mod_lesson_get_pages
   */
  async getPages(lessonId: number, password?: string): Promise<{ pages: LessonPage[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ pages: LessonPage[]; warnings?: unknown[] }>(
      'mod_lesson_get_pages',
      { lessonid: lessonId, password }
    );
    return response.data;
  }

  /**
   * Get page data
   * Uses: mod_lesson_get_page_data
   */
  async getPageData(lessonId: number, pageId: number, password?: string, review?: boolean, returncontents?: boolean): Promise<{ page: LessonPage; newpageid: number; ongoingscore: string; progress: number; contentfiles: unknown[]; answers: unknown[]; messages: unknown[]; displaymenu: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      page: LessonPage;
      newpageid: number;
      ongoingscore: string;
      progress: number;
      contentfiles: unknown[];
      answers: unknown[];
      messages: unknown[];
      displaymenu: boolean;
      warnings?: unknown[];
    }>('mod_lesson_get_page_data', {
      lessonid: lessonId,
      pageid: pageId,
      password,
      review,
      returncontents: returncontents
    });
    return response.data;
  }

  /**
   * Launch attempt
   * Uses: mod_lesson_launch_attempt
   */
  async launchAttempt(lessonId: number, password?: string, pageId?: number, review?: boolean): Promise<{ messages: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ messages: unknown[]; warnings?: unknown[] }>(
      'mod_lesson_launch_attempt',
      { lessonid: lessonId, password, pageid: pageId, review }
    );
    return response.data;
  }

  /**
   * Process page
   * Uses: mod_lesson_process_page
   */
  async processPage(lessonId: number, pageId: number, data: Record<string, unknown>, password?: string, review?: boolean): Promise<{ newpageid: number; inmediatejump: boolean; nodefaultresponse: boolean; feedback: string; attemptsremaining: number; correctanswer: boolean; noanswer: boolean; isessayquestion: boolean; maxattemptsreached: boolean; response: string; studentanswer: string; userresponse: string; reviewmode: boolean; ongoingscore: string; progress: number; displaymenu: boolean; messages: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      newpageid: number;
      inmediatejump: boolean;
      nodefaultresponse: boolean;
      feedback: string;
      attemptsremaining: number;
      correctanswer: boolean;
      noanswer: boolean;
      isessayquestion: boolean;
      maxattemptsreached: boolean;
      response: string;
      studentanswer: string;
      userresponse: string;
      reviewmode: boolean;
      ongoingscore: string;
      progress: number;
      displaymenu: boolean;
      messages: unknown[];
      warnings?: unknown[];
    }>('mod_lesson_process_page', { lessonid: lessonId, pageid: pageId, data, password, review });
    return response.data;
  }

  /**
   * Finish attempt
   * Uses: mod_lesson_finish_attempt
   */
  async finishAttempt(lessonId: number, password?: string, outOfTime?: boolean, review?: boolean): Promise<{ data: unknown[]; messages: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ data: unknown[]; messages: unknown[]; warnings?: unknown[] }>(
      'mod_lesson_finish_attempt',
      { lessonid: lessonId, password, outoftime: outOfTime, review }
    );
    return response.data;
  }

  /**
   * Get content pages viewed
   * Uses: mod_lesson_get_content_pages_viewed
   */
  async getContentPagesViewed(lessonId: number, attempt: number, userId?: number): Promise<{ pages: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ pages: unknown[]; warnings?: unknown[] }>(
      'mod_lesson_get_content_pages_viewed',
      { lessonid: lessonId, lessonattempt: attempt, userid: userId }
    );
    return response.data;
  }

  /**
   * Get user timers
   * Uses: mod_lesson_get_user_timers
   */
  async getUserTimers(lessonId: number, userId?: number): Promise<{ timers: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ timers: unknown[]; warnings?: unknown[] }>(
      'mod_lesson_get_user_timers',
      { lessonid: lessonId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get pages possible jumps
   * Uses: mod_lesson_get_pages_possible_jumps
   */
  async getPagesPossibleJumps(lessonId: number): Promise<{ jumps: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ jumps: unknown[]; warnings?: unknown[] }>(
      'mod_lesson_get_pages_possible_jumps',
      { lessonid: lessonId }
    );
    return response.data;
  }
}

// Singleton instance
export const lessonService = new LessonService();

export default lessonService;
