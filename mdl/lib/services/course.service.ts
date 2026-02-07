// Course Management Service
// Moodle API functions: core_course_*, core_courseformat_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  MoodleCourse,
  MoodleCourseContent,
  MoodleCategory,
  CreateCourseParams,
  UpdateCourseParams,
  CreateCategoryParams,
  CourseSearchCriteria,
  CourseByFieldParams
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class CourseService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get all courses or courses by IDs
   * Uses: core_course_get_courses
   */
  async getCourses(options?: { ids?: number[] }): Promise<MoodleCourse[]> {
    const response = await this.callWebService<MoodleCourse[]>(
      'core_course_get_courses',
      { options: options || {} }
    );
    return response.data;
  }

  /**
   * Get all courses
   */
  async getAllCourses(): Promise<MoodleCourse[]> {
    return this.getCourses();
  }

  /**
   * Get course by ID
   */
  async getCourseById(courseId: number): Promise<MoodleCourse | null> {
    const courses = await this.getCourses({ ids: [courseId] });
    return courses.length > 0 ? courses[0] : null;
  }

  /**
   * Get courses by field
   * Uses: core_course_get_courses_by_field
   */
  async getCoursesByField(params: CourseByFieldParams): Promise<{ courses: MoodleCourse[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ courses: MoodleCourse[]; warnings?: unknown[] }>(
      'core_course_get_courses_by_field',
      params as unknown as Record<string, unknown>
    );
    return response.data;
  }

  /**
   * Search courses
   * Uses: core_course_search_courses
   */
  async searchCourses(
    criteria: CourseSearchCriteria,
    page: number = 0,
    perPage: number = 50
  ): Promise<{ total: number; courses: MoodleCourse[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ total: number; courses: MoodleCourse[]; warnings?: unknown[] }>(
      'core_course_search_courses',
      {
        criterianame: criteria.criterianame,
        criteriavalue: criteria.criteriavalue,
        page,
        perpage: perPage
      }
    );
    return response.data;
  }

  /**
   * Create courses
   * Uses: core_course_create_courses
   */
  async createCourses(courses: CreateCourseParams[]): Promise<Array<{ id: number; shortname: string }>> {
    const response = await this.callWebService<Array<{ id: number; shortname: string }>>(
      'core_course_create_courses',
      { courses }
    );
    return response.data;
  }

  /**
   * Create a single course
   */
  async createCourse(course: CreateCourseParams): Promise<{ id: number; shortname: string }> {
    const result = await this.createCourses([course]);
    return result[0];
  }

  /**
   * Update courses
   * Uses: core_course_update_courses
   */
  async updateCourses(courses: UpdateCourseParams[]): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'core_course_update_courses',
      { courses }
    );
    return response.data;
  }

  /**
   * Update a single course
   */
  async updateCourse(course: UpdateCourseParams): Promise<{ warnings?: unknown[] }> {
    return this.updateCourses([course]);
  }

  /**
   * Delete courses
   * Uses: core_course_delete_courses
   */
  async deleteCourses(courseIds: number[]): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'core_course_delete_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Delete a single course
   */
  async deleteCourse(courseId: number): Promise<{ warnings?: unknown[] }> {
    return this.deleteCourses([courseId]);
  }

  /**
   * Get course contents (sections and modules)
   * Uses: core_course_get_contents
   */
  async getCourseContents(
    courseId: number,
    options?: Array<{ name: string; value: unknown }>
  ): Promise<MoodleCourseContent[]> {
    const response = await this.callWebService<MoodleCourseContent[]>(
      'core_course_get_contents',
      { courseid: courseId, options }
    );
    return response.data;
  }

  /**
   * View course (log view event)
   * Uses: core_course_view_course
   */
  async viewCourse(courseId: number, sectionNumber?: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_course_view_course',
      { courseid: courseId, sectionnumber: sectionNumber }
    );
    return response.data;
  }

  /**
   * Get course categories
   * Uses: core_course_get_categories
   */
  async getCategories(criteria?: Array<{ key: string; value: string }>): Promise<MoodleCategory[]> {
    const response = await this.callWebService<MoodleCategory[]>(
      'core_course_get_categories',
      { criteria }
    );
    return response.data;
  }

  /**
   * Create categories
   * Uses: core_course_create_categories
   */
  async createCategories(categories: CreateCategoryParams[]): Promise<Array<{ id: number; name: string }>> {
    const response = await this.callWebService<Array<{ id: number; name: string }>>(
      'core_course_create_categories',
      { categories }
    );
    return response.data;
  }

  /**
   * Create a single category
   */
  async createCategory(category: CreateCategoryParams): Promise<{ id: number; name: string }> {
    const result = await this.createCategories([category]);
    return result[0];
  }

  /**
   * Update categories
   * Uses: core_course_update_categories
   */
  async updateCategories(
    categories: Array<{ id: number; name?: string; idnumber?: string; parent?: number; description?: string; descriptionformat?: number; theme?: string }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_course_update_categories',
      { categories }
    );
    return response.data;
  }

  /**
   * Delete categories
   * Uses: core_course_delete_categories
   */
  async deleteCategories(
    categories: Array<{ id: number; newparent?: number; recursive?: number }>
  ): Promise<null> {
    const response = await this.callWebService<null>(
      'core_course_delete_categories',
      { categories }
    );
    return response.data;
  }

  /**
   * Get recent courses for current user
   * Uses: core_course_get_recent_courses
   */
  async getRecentCourses(userId?: number, limit: number = 10): Promise<MoodleCourse[]> {
    const response = await this.callWebService<MoodleCourse[]>(
      'core_course_get_recent_courses',
      { userid: userId, limit }
    );
    return response.data;
  }

  /**
   * Get enrolled courses by timeline classification
   * Uses: core_course_get_enrolled_courses_by_timeline_classification
   */
  async getEnrolledCoursesByTimeline(
    classification: 'all' | 'inprogress' | 'future' | 'past' | 'favourites' | 'hidden',
    limit: number = 0,
    offset: number = 0,
    sort?: string
  ): Promise<{ courses: MoodleCourse[]; nextoffset: number }> {
    const response = await this.callWebService<{ courses: MoodleCourse[]; nextoffset: number }>(
      'core_course_get_enrolled_courses_by_timeline_classification',
      { classification, limit, offset, sort }
    );
    return response.data;
  }

  /**
   * Set favourite courses
   * Uses: core_course_set_favourite_courses
   */
  async setFavouriteCourses(
    courses: Array<{ id: number; favourite: boolean }>
  ): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'core_course_set_favourite_courses',
      { courses }
    );
    return response.data;
  }

  /**
   * Get course module by ID
   * Uses: core_course_get_course_module
   */
  async getCourseModule(cmId: number): Promise<{ cm: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ cm: unknown; warnings?: unknown[] }>(
      'core_course_get_course_module',
      { cmid: cmId }
    );
    return response.data;
  }

  /**
   * Get course module by instance
   * Uses: core_course_get_course_module_by_instance
   */
  async getCourseModuleByInstance(
    module: string,
    instance: number
  ): Promise<{ cm: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ cm: unknown; warnings?: unknown[] }>(
      'core_course_get_course_module_by_instance',
      { module, instance }
    );
    return response.data;
  }

  /**
   * Duplicate a course
   * Uses: core_course_duplicate_course
   */
  async duplicateCourse(
    courseId: number,
    fullname: string,
    shortname: string,
    categoryId: number,
    visible?: boolean,
    options?: Array<{ name: string; value: string }>
  ): Promise<{ id: number; shortname: string }> {
    const response = await this.callWebService<{ id: number; shortname: string }>(
      'core_course_duplicate_course',
      {
        courseid: courseId,
        fullname,
        shortname,
        categoryid: categoryId,
        visible,
        options
      }
    );
    return response.data;
  }

  /**
   * Check for updates in a course
   * Uses: core_course_check_updates
   */
  async checkUpdates(
    courseId: number,
    toCheck: Array<{ contextlevel: string; id: number; since: number }>
  ): Promise<{ instances: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ instances: unknown[]; warnings?: unknown[] }>(
      'core_course_check_updates',
      { courseid: courseId, tocheck: toCheck }
    );
    return response.data;
  }

  /**
   * Get updates since a timestamp
   * Uses: core_course_get_updates_since
   */
  async getUpdatesSince(courseId: number, since: number): Promise<{ instances: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ instances: unknown[]; warnings?: unknown[] }>(
      'core_course_get_updates_since',
      { courseid: courseId, since }
    );
    return response.data;
  }
}

// Singleton instance
export const courseService = new CourseService();

export default courseService;
