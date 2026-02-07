// Competency Service
// Moodle API functions: core_competency_*, tool_lp_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Competency {
  shortname: string;
  idnumber: string;
  description: string;
  descriptionformat: number;
  sortorder: number;
  parentid: number;
  path: string;
  ruleoutcome: number;
  ruletype: string;
  ruleconfig: string;
  scaleid: number;
  scaleconfiguration: string;
  competencyframeworkid: number;
  id: number;
  timecreated: number;
  timemodified: number;
  usermodified: number;
}

export interface CompetencyFramework {
  shortname: string;
  idnumber: string;
  description: string;
  descriptionformat: number;
  visible: boolean;
  scaleid: number;
  scaleconfiguration: string;
  contextid: number;
  taxonomies: string;
  id: number;
  timecreated: number;
  timemodified: number;
  usermodified: number;
  canmanage: boolean;
  competenciescount: number;
}

export interface UserCompetency {
  userid: number;
  competencyid: number;
  status: number;
  reviewerid: number;
  proficiency: boolean;
  grade: number;
  id: number;
  timecreated: number;
  timemodified: number;
  usermodified: number;
  canrequestreview: boolean;
  canreview: boolean;
  gradename: string;
  isrequestreviewallowed: boolean;
  iscancelreviewrequestallowed: boolean;
  isstartreviewallowed: boolean;
  isstopreviewallowed: boolean;
  isstatusidle: boolean;
  isstatusinreview: boolean;
  isstatuswaitingforreview: boolean;
  url: string;
}

export interface LearningPlan {
  name: string;
  description: string;
  descriptionformat: number;
  userid: number;
  templateid: number;
  origtemplateid: number;
  status: number;
  duedate: number;
  reviewerid: number;
  id: number;
  timecreated: number;
  timemodified: number;
  usermodified: number;
  statusname: string;
  isbasedontemplate: boolean;
  canmanage: boolean;
  canrequestreview: boolean;
  canreview: boolean;
  canbeedited: boolean;
  isactive: boolean;
  isdraft: boolean;
  iscompleted: boolean;
  isinreview: boolean;
  iswaitingforreview: boolean;
  isreopenallowed: boolean;
  iscompleteallowed: boolean;
  isunlinkallowed: boolean;
  isrequestreviewallowed: boolean;
  iscancelreviewrequestallowed: boolean;
  isstartreviewallowed: boolean;
  isstopreviewallowed: boolean;
  isapproveallowed: boolean;
  isunapproveallowed: boolean;
  duedateformatted: string;
  commentarea: unknown;
  reviewer: unknown;
  template: unknown;
  url: string;
}

export class CompetencyService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  // ========== COMPETENCY FRAMEWORKS ==========

  /**
   * List competency frameworks
   * Uses: core_competency_list_competency_frameworks
   */
  async listCompetencyFrameworks(sort?: string, order?: string, skip?: number, limit?: number, context?: unknown, includes?: string, onlyVisible?: boolean): Promise<CompetencyFramework[]> {
    const response = await this.callWebService<CompetencyFramework[]>(
      'core_competency_list_competency_frameworks',
      { sort, order, skip, limit, context, includes, onlyvisible: onlyVisible }
    );
    return response.data;
  }

  /**
   * Read competency framework
   * Uses: core_competency_read_competency_framework
   */
  async readCompetencyFramework(id: number): Promise<CompetencyFramework> {
    const response = await this.callWebService<CompetencyFramework>(
      'core_competency_read_competency_framework',
      { id }
    );
    return response.data;
  }

  // ========== COMPETENCIES ==========

  /**
   * List competencies
   * Uses: core_competency_list_competencies
   */
  async listCompetencies(filters: Array<{ column: string; value: string }>, sort?: string, order?: string, skip?: number, limit?: number): Promise<Competency[]> {
    const response = await this.callWebService<Competency[]>(
      'core_competency_list_competencies',
      { filters, sort, order, skip, limit }
    );
    return response.data;
  }

  /**
   * Read competency
   * Uses: core_competency_read_competency
   */
  async readCompetency(id: number): Promise<Competency> {
    const response = await this.callWebService<Competency>(
      'core_competency_read_competency',
      { id }
    );
    return response.data;
  }

  /**
   * List competencies in framework
   * Uses: core_competency_list_competencies_in_framework (via search)
   */
  async searchCompetencies(competencyFrameworkId: number, searchText: string): Promise<Competency[]> {
    const response = await this.callWebService<Competency[]>(
      'core_competency_search_competencies',
      { competencyframeworkid: competencyFrameworkId, searchtext: searchText }
    );
    return response.data;
  }

  // ========== USER COMPETENCIES ==========

  /**
   * List user competencies
   * Uses: core_competency_list_user_competencies
   */
  async listUserCompetencies(userId: number): Promise<UserCompetency[]> {
    const response = await this.callWebService<UserCompetency[]>(
      'core_competency_list_user_competencies',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * Get user competency
   * Uses: core_competency_user_competency_viewed
   */
  async userCompetencyViewed(userCompetencyId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_competency_user_competency_viewed',
      { usercompetencyid: userCompetencyId }
    );
    return response.data;
  }

  /**
   * Grade user competency
   * Uses: core_competency_grade_competency
   */
  async gradeCompetency(userId: number, competencyId: number, grade: number, note?: string): Promise<UserCompetency> {
    const response = await this.callWebService<UserCompetency>(
      'core_competency_grade_competency',
      { userid: userId, competencyid: competencyId, grade, note }
    );
    return response.data;
  }

  // ========== LEARNING PLANS ==========

  /**
   * List user plans
   * Uses: tool_lp_data_for_plans_page
   */
  async listUserPlans(userId: number): Promise<{ userid: number; plans: LearningPlan[]; pluginbaseurl: string; navigation: unknown[]; canreaduserevidence: boolean; canmanageuserplans: boolean }> {
    const response = await this.callWebService<{
      userid: number;
      plans: LearningPlan[];
      pluginbaseurl: string;
      navigation: unknown[];
      canreaduserevidence: boolean;
      canmanageuserplans: boolean;
    }>('tool_lp_data_for_plans_page', { userid: userId });
    return response.data;
  }

  /**
   * Read plan
   * Uses: core_competency_read_plan
   */
  async readPlan(id: number): Promise<LearningPlan> {
    const response = await this.callWebService<LearningPlan>(
      'core_competency_read_plan',
      { id }
    );
    return response.data;
  }

  /**
   * Create plan
   * Uses: core_competency_create_plan
   */
  async createPlan(plan: { name: string; description?: string; descriptionformat?: number; userid: number; templateid?: number; status?: number; duedate?: number }): Promise<LearningPlan> {
    const response = await this.callWebService<LearningPlan>(
      'core_competency_create_plan',
      { plan }
    );
    return response.data;
  }

  /**
   * Update plan
   * Uses: core_competency_update_plan
   */
  async updatePlan(plan: { id: number; name?: string; description?: string; descriptionformat?: number; status?: number; duedate?: number; reviewerid?: number }): Promise<LearningPlan> {
    const response = await this.callWebService<LearningPlan>(
      'core_competency_update_plan',
      { plan }
    );
    return response.data;
  }

  /**
   * Delete plan
   * Uses: core_competency_delete_plan
   */
  async deletePlan(id: number): Promise<boolean> {
    const response = await this.callWebService<boolean>(
      'core_competency_delete_plan',
      { id }
    );
    return response.data;
  }

  /**
   * Complete plan
   * Uses: core_competency_complete_plan
   */
  async completePlan(planId: number): Promise<boolean> {
    const response = await this.callWebService<boolean>(
      'core_competency_complete_plan',
      { planid: planId }
    );
    return response.data;
  }

  // ========== COURSE COMPETENCIES ==========

  /**
   * List course competencies
   * Uses: core_competency_list_course_competencies
   */
  async listCourseCompetencies(courseId: number): Promise<Array<{ competency: Competency; coursecompetency: unknown }>> {
    const response = await this.callWebService<Array<{ competency: Competency; coursecompetency: unknown }>>(
      'core_competency_list_course_competencies',
      { id: courseId }
    );
    return response.data;
  }

  /**
   * Get course competency statistics
   * Uses: tool_lp_data_for_course_competencies_page
   */
  async getCourseCompetenciesPageData(courseId: number, moduleId?: number): Promise<unknown> {
    const response = await this.callWebService<unknown>(
      'tool_lp_data_for_course_competencies_page',
      { courseid: courseId, moduleid: moduleId }
    );
    return response.data;
  }
}

// Singleton instance
export const competencyService = new CompetencyService();

export default competencyService;
