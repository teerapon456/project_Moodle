// Workshop Service
// Moodle API functions: mod_workshop_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Workshop {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  instructauthors: string;
  instructauthorsformat: number;
  instructreviewers: string;
  instructreviewersformat: number;
  timemodified: number;
  phase: number;
  useexamples: boolean;
  usepeerassessment: boolean;
  useselfassessment: boolean;
  grade: number;
  gradinggrade: number;
  strategy: string;
  evaluation: string;
  gradedecimals: number;
  submissiontypetext: number;
  submissiontypefile: number;
  nattachments: number;
  submissionfiletypes: string;
  latesubmissions: boolean;
  maxbytes: number;
  examplesmode: number;
  submissionstart: number;
  submissionend: number;
  assessmentstart: number;
  assessmentend: number;
  phaseswitchassessment: boolean;
  conclusion: string;
  conclusionformat: number;
  overallfeedbackmode: number;
  overallfeedbackfiles: number;
  overallfeedbackfiletypes: string;
  overallfeedbackmaxbytes: number;
  coursemodule: number;
}

export interface WorkshopSubmission {
  id: number;
  workshopid: number;
  authorid: number;
  timecreated: number;
  timemodified: number;
  title: string;
  content: string;
  contentformat: number;
  contenttrust: number;
  attachment: number;
  grade: number;
  gradeover: number;
  gradeoverby: number;
  feedbackauthor: string;
  feedbackauthorformat: number;
  timegraded: number;
  published: boolean;
  late: number;
}

export interface WorkshopAssessment {
  id: number;
  submissionid: number;
  reviewerid: number;
  weight: number;
  timecreated: number;
  timemodified: number;
  grade: number;
  gradinggrade: number;
  gradinggradeover: number;
  gradinggradeoverby: number;
  feedbackauthor: string;
  feedbackauthorformat: number;
  feedbackauthorattachment: number;
  feedbackreviewer: string;
  feedbackreviewerformat: number;
}

export class WorkshopService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  /**
   * Get workshops by courses
   * Uses: mod_workshop_get_workshops_by_courses
   */
  async getWorkshopsByCourses(courseIds?: number[]): Promise<{ workshops: Workshop[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ workshops: Workshop[]; warnings?: unknown[] }>(
      'mod_workshop_get_workshops_by_courses',
      { courseids: courseIds }
    );
    return response;
  }

  /**
   * Get workshop access information
   * Uses: mod_workshop_get_workshop_access_information
   */
  async getAccessInformation(workshopId: number): Promise<unknown> {
    const response = await this.client.callFunction<unknown>(
      'mod_workshop_get_workshop_access_information',
      { workshopid: workshopId }
    );
    return response;
  }

  /**
   * Get user plan
   * Uses: mod_workshop_get_user_plan
   */
  async getUserPlan(workshopId: number, userId?: number): Promise<{ userplan: unknown; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ userplan: unknown; warnings?: unknown[] }>(
      'mod_workshop_get_user_plan',
      { workshopid: workshopId, userid: userId }
    );
    return response;
  }

  /**
   * View workshop (log event)
   * Uses: mod_workshop_view_workshop
   */
  async viewWorkshop(workshopId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_workshop_view_workshop',
      { workshopid: workshopId }
    );
    return response;
  }

  /**
   * Get submissions
   * Uses: mod_workshop_get_submissions
   */
  async getSubmissions(
    workshopId: number,
    userId?: number,
    groupId?: number,
    page?: number,
    perPage?: number
  ): Promise<{ submissions: WorkshopSubmission[]; totalcount: number; totalfilesize: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      submissions: WorkshopSubmission[];
      totalcount: number;
      totalfilesize: number;
      warnings?: unknown[];
    }>('mod_workshop_get_submissions', {
      workshopid: workshopId,
      userid: userId,
      groupid: groupId,
      page,
      perpage: perPage
    });
    return response;
  }

  /**
   * Get submission
   * Uses: mod_workshop_get_submission
   */
  async getSubmission(submissionId: number): Promise<{ submission: WorkshopSubmission; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ submission: WorkshopSubmission; warnings?: unknown[] }>(
      'mod_workshop_get_submission',
      { submissionid: submissionId }
    );
    return response;
  }

  /**
   * Get submission assessments
   * Uses: mod_workshop_get_submission_assessments
   */
  async getSubmissionAssessments(submissionId: number): Promise<{ assessments: WorkshopAssessment[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ assessments: WorkshopAssessment[]; warnings?: unknown[] }>(
      'mod_workshop_get_submission_assessments',
      { submissionid: submissionId }
    );
    return response;
  }

  /**
   * Get reviewer assessments
   * Uses: mod_workshop_get_reviewer_assessments
   */
  async getReviewerAssessments(workshopId: number, userId?: number): Promise<{ assessments: WorkshopAssessment[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ assessments: WorkshopAssessment[]; warnings?: unknown[] }>(
      'mod_workshop_get_reviewer_assessments',
      { workshopid: workshopId, userid: userId }
    );
    return response;
  }

  /**
   * Get assessment
   * Uses: mod_workshop_get_assessment
   */
  async getAssessment(assessmentId: number): Promise<{ assessment: WorkshopAssessment; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ assessment: WorkshopAssessment; warnings?: unknown[] }>(
      'mod_workshop_get_assessment',
      { assessmentid: assessmentId }
    );
    return response;
  }

  /**
   * Get assessment form definition
   * Uses: mod_workshop_get_assessment_form_definition
   */
  async getAssessmentFormDefinition(
    assessmentId: number,
    mode?: string
  ): Promise<{ dimenssionsinfo: unknown[]; descriptionfiles: unknown[]; options: unknown; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      dimenssionsinfo: unknown[];
      descriptionfiles: unknown[];
      options: unknown;
      warnings?: unknown[];
    }>('mod_workshop_get_assessment_form_definition', {
      assessmentid: assessmentId,
      mode
    });
    return response;
  }

  /**
   * Get grades
   * Uses: mod_workshop_get_grades
   */
  async getGrades(workshopId: number, userId?: number): Promise<{ assessmentrawgrade: number; assessmentlongstrgrade: string; assessmentgradehiddenbyac: boolean; submissionrawgrade: number; submissionlongstrgrade: string; submissiongradehiddenbyac: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      assessmentrawgrade: number;
      assessmentlongstrgrade: string;
      assessmentgradehiddenbyac: boolean;
      submissionrawgrade: number;
      submissionlongstrgrade: string;
      submissiongradehiddenbyac: boolean;
      warnings?: unknown[];
    }>('mod_workshop_get_grades', { workshopid: workshopId, userid: userId });
    return response;
  }

  /**
   * Get grades report
   * Uses: mod_workshop_get_grades_report
   */
  async getGradesReport(
    workshopId: number,
    groupId?: number,
    sortBy?: string,
    sortDirection?: string,
    page?: number,
    perPage?: number
  ): Promise<{ report: unknown; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ report: unknown; warnings?: unknown[] }>(
      'mod_workshop_get_grades_report',
      {
        workshopid: workshopId,
        groupid: groupId,
        sortby: sortBy,
        sortdirection: sortDirection,
        page,
        perpage: perPage
      }
    );
    return response;
  }

  /**
   * Add submission
   * Uses: mod_workshop_add_submission
   */
  async addSubmission(
    workshopId: number,
    title: string,
    content?: string,
    contentformat?: number,
    inlineattachmentsId?: number,
    attachmentsId?: number
  ): Promise<{ status: boolean; submissionid?: number; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; submissionid?: number; warnings?: unknown[] }>(
      'mod_workshop_add_submission',
      {
        workshopid: workshopId,
        title,
        content,
        contentformat,
        inlineattachmentsid: inlineattachmentsId,
        attachmentsid: attachmentsId
      }
    );
    return response;
  }

  /**
   * Update submission
   * Uses: mod_workshop_update_submission
   */
  async updateSubmission(
    submissionId: number,
    title: string,
    content?: string,
    contentformat?: number,
    inlineattachmentsId?: number,
    attachmentsId?: number
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_workshop_update_submission',
      {
        submissionid: submissionId,
        title,
        content,
        contentformat,
        inlineattachmentsid: inlineattachmentsId,
        attachmentsid: attachmentsId
      }
    );
    return response;
  }

  /**
   * Delete submission
   * Uses: mod_workshop_delete_submission
   */
  async deleteSubmission(submissionId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_workshop_delete_submission',
      { submissionid: submissionId }
    );
    return response;
  }

  /**
   * Evaluate submission
   * Uses: mod_workshop_evaluate_submission
   */
  async evaluateSubmission(
    submissionId: number,
    feedbackText?: string,
    feedbackFormat?: number,
    published?: boolean,
    gradeOver?: string
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_workshop_evaluate_submission',
      {
        submissionid: submissionId,
        feedbacktext: feedbackText,
        feedbackformat: feedbackFormat,
        published,
        gradeover: gradeOver
      }
    );
    return response;
  }

  /**
   * Evaluate assessment
   * Uses: mod_workshop_evaluate_assessment
   */
  async evaluateAssessment(
    assessmentId: number,
    feedbackText?: string,
    feedbackFormat?: number,
    weight?: number,
    gradingGradeOver?: string
  ): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ status: boolean; warnings?: unknown[] }>(
      'mod_workshop_evaluate_assessment',
      {
        assessmentid: assessmentId,
        feedbacktext: feedbackText,
        feedbackformat: feedbackFormat,
        weight,
        gradinggradeover: gradingGradeOver
      }
    );
    return response;
  }
}

// Singleton instance
export const workshopService = new WorkshopService();

export default workshopService;
