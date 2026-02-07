// Survey/Choice/Feedback Service
// Moodle API functions: mod_survey_*, mod_choice_*, mod_feedback_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

// Survey (Standard Survey)
export interface Survey {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  template?: number;
  days?: number;
  questions?: string;
  surveydone?: number;
  timecreated?: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

// Choice (Simple Poll)
export interface Choice {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  publish: boolean;
  showresults: number;
  display: number;
  allowupdate: boolean;
  allowmultiple: boolean;
  showunanswered: boolean;
  includeinactive: boolean;
  limitanswers: boolean;
  timeopen: number;
  timeclose: number;
  showpreview: boolean;
  timemodified: number;
  completionsubmit: boolean;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface ChoiceOption {
  id: number;
  text: string;
  maxanswers: number;
  displaylayout: number;
  countanswers: number;
  checked: boolean;
  disabled: boolean;
}

// Feedback
export interface Feedback {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  anonymous: number;
  email_notification: boolean;
  multiple_submit: boolean;
  autonumbering: boolean;
  site_after_submit: string;
  page_after_submit: string;
  page_after_submitformat: number;
  publish_stats: boolean;
  timeopen: number;
  timeclose: number;
  timemodified: number;
  completionsubmit: boolean;
  coursemodule: number;
  introfiles: unknown[];
  pageaftersubmitfiles: unknown[];
}

export interface FeedbackItem {
  id: number;
  feedback: number;
  template: number;
  name: string;
  label: string;
  presentation: string;
  typ: string;
  hasvalue: number;
  position: number;
  required: boolean;
  dependitem: number;
  dependvalue: string;
  options: string;
  itemfiles: unknown[];
  itemnumber: number;
  otherdata: string;
}

export class SurveyService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  // ========== SURVEY (Standard) ==========

  /**
   * Get surveys by courses
   * Uses: mod_survey_get_surveys_by_courses
   */
  async getSurveysByCourses(courseIds?: number[]): Promise<{ surveys: Survey[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ surveys: Survey[]; warnings?: unknown[] }>(
      'mod_survey_get_surveys_by_courses',
      { courseids: courseIds }
    );
    return response;
  }

  /**
   * View survey (log event)
   * Uses: mod_survey_view_survey
   */
  async viewSurvey(surveyId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_survey_view_survey',
      { surveyid: surveyId }
    );
    return response;
  }

  /**
   * Get main properties of survey (questions etc)
   * Uses: mod_survey_get_questions
   */
  async getQuestions(surveyId: number): Promise<{ questions: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ questions: unknown[]; warnings?: unknown[] }>(
      'mod_survey_get_questions',
      { surveyid: surveyId }
    );
    return response;
  }

  // ========== CHOICE (Simple Poll) ==========

  /**
   * Get choices by courses
   * Uses: mod_choice_get_choices_by_courses
   */
  async getChoicesByCourses(courseIds?: number[]): Promise<{ choices: Choice[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ choices: Choice[]; warnings?: unknown[] }>(
      'mod_choice_get_choices_by_courses',
      { courseids: courseIds }
    );
    return response;
  }

  /**
   * Get choice options
   * Uses: mod_choice_get_choice_options
   */
  async getChoiceOptions(choiceId: number): Promise<{ options: ChoiceOption[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ options: ChoiceOption[]; warnings?: unknown[] }>(
      'mod_choice_get_choice_options',
      { choiceid: choiceId }
    );
    return response;
  }

  /**
   * Get choice results
   * Uses: mod_choice_get_choice_results
   */
  async getChoiceResults(choiceId: number): Promise<{ options: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ options: unknown[]; warnings?: unknown[] }>(
      'mod_choice_get_choice_results',
      { choiceid: choiceId }
    );
    return response;
  }

  /**
   * Submit choice response
   * Uses: mod_choice_submit_choice_response
   */
  async submitChoiceResponse(choiceId: number, responses: number[]): Promise<{ answers: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ answers: unknown[]; warnings?: unknown[] }>(
      'mod_choice_submit_choice_response',
      { choiceid: choiceId, responses }
    );
    return response;
  }

  /**
   * View choice (log event)
   * Uses: mod_choice_view_choice
   */
  async viewChoice(choiceId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_choice_view_choice',
      { choiceid: choiceId }
    );
    return response;
  }

  /**
   * Delete choice responses
   * Uses: mod_choice_delete_choice_responses
   */
  async deleteChoiceResponses(choiceId: number, responses?: number[]): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_choice_delete_choice_responses',
      { choiceid: choiceId, responses }
    );
    return response;
  }

  // ========== FEEDBACK ==========

  /**
   * Get feedbacks by courses
   * Uses: mod_feedback_get_feedbacks_by_courses
   */
  async getFeedbacksByCourses(courseIds?: number[]): Promise<{ feedbacks: Feedback[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ feedbacks: Feedback[]; warnings?: unknown[] }>(
      'mod_feedback_get_feedbacks_by_courses',
      { courseids: courseIds }
    );
    return response;
  }

  /**
   * View feedback (log event)
   * Uses: mod_feedback_view_feedback
   */
  async viewFeedback(feedbackId: number, moduleContext?: boolean, courseId?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_feedback_view_feedback',
      { feedbackid: feedbackId, modulecontext: moduleContext, courseid: courseId }
    );
    return response;
  }

  /**
   * Get feedback access information
   * Uses: mod_feedback_get_feedback_access_information
   */
  async getFeedbackAccessInformation(feedbackId: number, courseId?: number): Promise<unknown> {
    const response = await this.client.callFunction<unknown>(
      'mod_feedback_get_feedback_access_information',
      { feedbackid: feedbackId, courseid: courseId }
    );
    return response;
  }

  /**
   * Get feedback items
   * Uses: mod_feedback_get_items
   */
  async getFeedbackItems(feedbackId: number, courseId?: number): Promise<{ items: FeedbackItem[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ items: FeedbackItem[]; warnings?: unknown[] }>(
      'mod_feedback_get_items',
      { feedbackid: feedbackId, courseid: courseId }
    );
    return response;
  }

  /**
   * Launch feedback
   * Uses: mod_feedback_launch_feedback
   */
  async launchFeedback(feedbackId: number, courseId?: number): Promise<{ gopage: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ gopage: number; warnings?: unknown[] }>(
      'mod_feedback_launch_feedback',
      { feedbackid: feedbackId, courseid: courseId }
    );
    return response;
  }

  /**
   * Get page items
   * Uses: mod_feedback_get_page_items
   */
  async getFeedbackPageItems(feedbackId: number, page: number, courseId?: number): Promise<{ items: FeedbackItem[]; hasprevpage: boolean; hasnextpage: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ items: FeedbackItem[]; hasprevpage: boolean; hasnextpage: boolean; warnings?: unknown[] }>(
      'mod_feedback_get_page_items',
      { feedbackid: feedbackId, page, courseid: courseId }
    );
    return response;
  }

  /**
   * Process page
   * Uses: mod_feedback_process_page
   */
  async processFeedbackPage(feedbackId: number, page: number, responses: Array<{ name: string; value: string }>, goNext?: boolean, courseId?: number): Promise<{ jumpto: number; completed: boolean; completionpagecontents: string; siteaftersubmit: string; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      jumpto: number;
      completed: boolean;
      completionpagecontents: string;
      siteaftersubmit: string;
      warnings?: unknown[];
    }>('mod_feedback_process_page', {
      feedbackid: feedbackId,
      page,
      responses,
      goprevious: !goNext,
      courseid: courseId
    });
    return response;
  }

  /**
   * Get unfinished responses
   * Uses: mod_feedback_get_unfinished_responses
   */
  async getUnfinishedResponses(feedbackId: number, courseId?: number): Promise<{ responses: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ responses: unknown[]; warnings?: unknown[] }>(
      'mod_feedback_get_unfinished_responses',
      { feedbackid: feedbackId, courseid: courseId }
    );
    return response;
  }

  /**
   * Get finished responses
   * Uses: mod_feedback_get_finished_responses
   */
  async getFinishedResponses(feedbackId: number, courseId?: number): Promise<{ responses: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ responses: unknown[]; warnings?: unknown[] }>(
      'mod_feedback_get_finished_responses',
      { feedbackid: feedbackId, courseid: courseId }
    );
    return response;
  }

  /**
   * Get non respondents
   * Uses: mod_feedback_get_non_respondents
   */
  async getNonRespondents(feedbackId: number, groupId?: number, sort?: string, page?: number, perPage?: number, courseId?: number): Promise<{ users: unknown[]; total: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ users: unknown[]; total: number; warnings?: unknown[] }>(
      'mod_feedback_get_non_respondents',
      { feedbackid: feedbackId, groupid: groupId, sort, page, perpage: perPage, courseid: courseId }
    );
    return response;
  }

  /**
   * Get responses analysis
   * Uses: mod_feedback_get_responses_analysis
   */
  async getResponsesAnalysis(feedbackId: number, groupId?: number, page?: number, perPage?: number, courseId?: number): Promise<{ attempts: unknown[]; totalattempts: number; anonattempts: unknown[]; totalanonattempts: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      attempts: unknown[];
      totalattempts: number;
      anonattempts: unknown[];
      totalanonattempts: number;
      warnings?: unknown[];
    }>('mod_feedback_get_responses_analysis', {
      feedbackid: feedbackId,
      groupid: groupId,
      page,
      perpage: perPage,
      courseid: courseId
    });
    return response;
  }

  /**
   * Get analysis
   * Uses: mod_feedback_get_analysis
   */
  async getFeedbackAnalysis(feedbackId: number, groupId?: number, courseId?: number): Promise<{ completedcount: number; itemscount: number; itemsdata: unknown[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ completedcount: number; itemscount: number; itemsdata: unknown[]; warnings?: unknown[] }>(
      'mod_feedback_get_analysis',
      { feedbackid: feedbackId, groupid: groupId, courseid: courseId }
    );
    return response;
  }
}

// Singleton instance
export const surveyService = new SurveyService();

export default surveyService;
