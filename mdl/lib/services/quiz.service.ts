// Quiz Service
// Moodle API functions: mod_quiz_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  MoodleQuiz,
  QuizAttempt,
  QuizAttemptData,
  QuizAttemptReview,
  QuizAttemptSummary,
  StartAttemptResponse,
  ProcessAttemptResponse,
  UserBestGrade,
  QuizAccessInfo,
  QuizFeedback
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class QuizService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get quizzes by courses
   * Uses: mod_quiz_get_quizzes_by_courses
   */
  async getQuizzesByCourses(courseIds?: number[]): Promise<{ quizzes: MoodleQuiz[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ quizzes: MoodleQuiz[]; warnings?: unknown[] }>(
      'mod_quiz_get_quizzes_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View quiz (log view event)
   * Uses: mod_quiz_view_quiz
   */
  async viewQuiz(quizId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_quiz_view_quiz',
      { quizid: quizId }
    );
    return response.data;
  }

  /**
   * Get user attempts for a quiz
   * Uses: mod_quiz_get_user_attempts
   */
  async getUserAttempts(
    quizId: number,
    userId?: number,
    status: 'all' | 'finished' | 'unfinished' = 'all',
    includePreviews: boolean = false
  ): Promise<{ attempts: QuizAttempt[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ attempts: QuizAttempt[]; warnings?: unknown[] }>(
      'mod_quiz_get_user_attempts',
      { quizid: quizId, userid: userId, status, includepreviews: includePreviews }
    );
    return response.data;
  }

  /**
   * Get user's best grade for a quiz
   * Uses: mod_quiz_get_user_best_grade
   */
  async getUserBestGrade(quizId: number, userId?: number): Promise<UserBestGrade> {
    const response = await this.callWebService<UserBestGrade>(
      'mod_quiz_get_user_best_grade',
      { quizid: quizId, userid: userId }
    );
    return response.data;
  }

  /**
   * Get combined review options
   * Uses: mod_quiz_get_combined_review_options
   */
  async getCombinedReviewOptions(
    quizId: number,
    userId?: number
  ): Promise<{ someoptions: unknown[]; alloptions: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      someoptions: unknown[];
      alloptions: unknown[];
      warnings?: unknown[];
    }>('mod_quiz_get_combined_review_options', { quizid: quizId, userid: userId });
    return response.data;
  }

  /**
   * Start a new quiz attempt
   * Uses: mod_quiz_start_attempt
   */
  async startAttempt(
    quizId: number,
    preflightData?: Array<{ name: string; value: string }>,
    forcenew: boolean = false
  ): Promise<StartAttemptResponse> {
    const response = await this.callWebService<StartAttemptResponse>(
      'mod_quiz_start_attempt',
      { quizid: quizId, preflightdata: preflightData, forcenew }
    );
    return response.data;
  }

  /**
   * Get attempt data (questions for a page)
   * Uses: mod_quiz_get_attempt_data
   */
  async getAttemptData(attemptId: number, page: number): Promise<QuizAttemptData> {
    const response = await this.callWebService<QuizAttemptData>(
      'mod_quiz_get_attempt_data',
      { attemptid: attemptId, page }
    );
    return response.data;
  }

  /**
   * Get attempt summary
   * Uses: mod_quiz_get_attempt_summary
   */
  async getAttemptSummary(
    attemptId: number,
    preflightData?: Array<{ name: string; value: string }>
  ): Promise<QuizAttemptSummary> {
    const response = await this.callWebService<QuizAttemptSummary>(
      'mod_quiz_get_attempt_summary',
      { attemptid: attemptId, preflightdata: preflightData }
    );
    return response.data;
  }

  /**
   * Save attempt (auto-save answers)
   * Uses: mod_quiz_save_attempt
   */
  async saveAttempt(
    attemptId: number,
    data: Array<{ name: string; value: string }>,
    preflightData?: Array<{ name: string; value: string }>
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_quiz_save_attempt',
      { attemptid: attemptId, data, preflightdata: preflightData }
    );
    return response.data;
  }

  /**
   * Process attempt (submit page or finish)
   * Uses: mod_quiz_process_attempt
   */
  async processAttempt(
    attemptId: number,
    data?: Array<{ name: string; value: string }>,
    finishAttempt: boolean = false,
    timeUp: boolean = false,
    preflightData?: Array<{ name: string; value: string }>
  ): Promise<ProcessAttemptResponse> {
    const response = await this.callWebService<ProcessAttemptResponse>(
      'mod_quiz_process_attempt',
      {
        attemptid: attemptId,
        data,
        finishattempt: finishAttempt,
        timeup: timeUp,
        preflightdata: preflightData
      }
    );
    return response.data;
  }

  /**
   * Finish an attempt
   */
  async finishAttempt(
    attemptId: number,
    data?: Array<{ name: string; value: string }>
  ): Promise<ProcessAttemptResponse> {
    return this.processAttempt(attemptId, data, true);
  }

  /**
   * Get attempt review
   * Uses: mod_quiz_get_attempt_review
   */
  async getAttemptReview(attemptId: number, page?: number): Promise<QuizAttemptReview> {
    const response = await this.callWebService<QuizAttemptReview>(
      'mod_quiz_get_attempt_review',
      { attemptid: attemptId, page }
    );
    return response.data;
  }

  /**
   * View attempt (log view event)
   * Uses: mod_quiz_view_attempt
   */
  async viewAttempt(attemptId: number, page: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_quiz_view_attempt',
      { attemptid: attemptId, page }
    );
    return response.data;
  }

  /**
   * View attempt summary (log view event)
   * Uses: mod_quiz_view_attempt_summary
   */
  async viewAttemptSummary(
    attemptId: number,
    preflightData?: Array<{ name: string; value: string }>
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_quiz_view_attempt_summary',
      { attemptid: attemptId, preflightdata: preflightData }
    );
    return response.data;
  }

  /**
   * View attempt review (log view event)
   * Uses: mod_quiz_view_attempt_review
   */
  async viewAttemptReview(attemptId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_quiz_view_attempt_review',
      { attemptid: attemptId }
    );
    return response.data;
  }

  /**
   * Get quiz feedback for a grade
   * Uses: mod_quiz_get_quiz_feedback_for_grade
   */
  async getQuizFeedbackForGrade(quizId: number, grade: number): Promise<QuizFeedback> {
    const response = await this.callWebService<QuizFeedback>(
      'mod_quiz_get_quiz_feedback_for_grade',
      { quizid: quizId, grade }
    );
    return response.data;
  }

  /**
   * Get quiz access information
   * Uses: mod_quiz_get_quiz_access_information
   */
  async getQuizAccessInformation(quizId: number): Promise<QuizAccessInfo> {
    const response = await this.callWebService<QuizAccessInfo>(
      'mod_quiz_get_quiz_access_information',
      { quizid: quizId }
    );
    return response.data;
  }

  /**
   * Get attempt access information
   * Uses: mod_quiz_get_attempt_access_information
   */
  async getAttemptAccessInformation(
    quizId: number,
    attemptId?: number
  ): Promise<{
    endtime?: number;
    isfinished: boolean;
    ispreflightcheckrequired?: boolean;
    preventnewattemptreasons: string[];
    warnings?: unknown[];
  }> {
    const response = await this.callWebService<{
      endtime?: number;
      isfinished: boolean;
      ispreflightcheckrequired?: boolean;
      preventnewattemptreasons: string[];
      warnings?: unknown[];
    }>('mod_quiz_get_attempt_access_information', { quizid: quizId, attemptid: attemptId });
    return response.data;
  }

  /**
   * Get required question types
   * Uses: mod_quiz_get_quiz_required_qtypes
   */
  async getQuizRequiredQtypes(quizId: number): Promise<{ questiontypes: string[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ questiontypes: string[]; warnings?: unknown[] }>(
      'mod_quiz_get_quiz_required_qtypes',
      { quizid: quizId }
    );
    return response.data;
  }
}

// Singleton instance
export const quizService = new QuizService();

export default quizService;
