// Question Service
// Moodle API functions: core_question_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface QuestionCategory {
  id: number;
  name: string;
  contextid: number;
  contextlevel: number;
  contextinstanceid: number;
  info: string;
  infoformat: number;
  idnumber: string;
  stamp: string;
  parent: number;
  sortorder: number;
}

export interface QuestionBankEntry {
  id: number;
  questioncategoryid: number;
  idnumber: string;
  ownerid: number;
}

export interface Question {
  id: number;
  questionbankentryid: number;
  version: number;
  name: string;
  questiontext: string;
  questiontextformat: number;
  generalfeedback: string;
  generalfeedbackformat: number;
  defaultmark: number;
  penalty: number;
  qtype: string;
  length: number;
  stamp: string;
  timecreated: number;
  timemodified: number;
  createdby: number;
  modifiedby: number;
  status: string;
}

export interface RandomQuestionSummary {
  category: QuestionCategory;
  questionscount: number;
  questions: Question[];
}

export class QuestionService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Submit question for commenting
   * Uses: core_question_submit_tags_form
   */
  async submitTagsForm(questionId: number, contextId: number, formData: string): Promise<{ result: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ result: boolean; warnings?: unknown[] }>(
      'core_question_submit_tags_form',
      { questionid: questionId, contextid: contextId, formdata: formData }
    );
    return response.data;
  }

  /**
   * Get random question summaries
   * Uses: core_question_get_random_question_summaries
   */
  async getRandomQuestionSummaries(categoryId: number, includeSubcategories: boolean, tagIds: number[], contextId: number, limit?: number, offset?: number): Promise<{ totalcount: number; questions: RandomQuestionSummary[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      totalcount: number;
      questions: RandomQuestionSummary[];
      warnings?: unknown[];
    }>('core_question_get_random_question_summaries', {
      categoryid: categoryId,
      includesubcategories: includeSubcategories,
      tagids: tagIds,
      contextid: contextId,
      limit,
      offset
    });
    return response.data;
  }

  /**
   * Update question flag
   * Uses: core_question_update_flag
   */
  async updateFlag(questionId: number, sessionId: number, checksum: string, newState: boolean): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_question_update_flag',
      { qubaid: questionId, sessionid: sessionId, checksum, newstate: newState }
    );
    return response.data;
  }
}

// Singleton instance
export const questionService = new QuestionService();

export default questionService;
