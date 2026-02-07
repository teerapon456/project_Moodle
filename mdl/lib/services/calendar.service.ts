// Calendar Service
// Moodle API functions: core_calendar_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  CalendarEvent,
  CalendarMonthView,
  CalendarDay,
  CreateCalendarEventParams,
  CalendarAccessInfo
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class CalendarService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get calendar monthly view
   * Uses: core_calendar_get_calendar_monthly_view
   */
  async getMonthlyView(
    year: number,
    month: number,
    courseId?: number,
    categoryId?: number,
    includeNavigation: boolean = true,
    mini: boolean = false
  ): Promise<CalendarMonthView> {
    const response = await this.callWebService<CalendarMonthView>(
      'core_calendar_get_calendar_monthly_view',
      { year, month, courseid: courseId, categoryid: categoryId, includenavigation: includeNavigation, mini }
    );
    return response.data;
  }

  /**
   * Get calendar day view
   * Uses: core_calendar_get_calendar_day_view
   */
  async getDayView(
    year: number,
    month: number,
    day: number,
    courseId?: number,
    categoryId?: number
  ): Promise<{ events: CalendarEvent[]; date: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ events: CalendarEvent[]; date: unknown; warnings?: unknown[] }>(
      'core_calendar_get_calendar_day_view',
      { year, month, day, courseid: courseId, categoryid: categoryId }
    );
    return response.data;
  }

  /**
   * Get calendar upcoming view
   * Uses: core_calendar_get_calendar_upcoming_view
   */
  async getUpcomingView(
    courseId?: number,
    categoryId?: number
  ): Promise<{ events: CalendarEvent[]; date: unknown; warnings?: unknown[] }> {
    const response = await this.callWebService<{ events: CalendarEvent[]; date: unknown; warnings?: unknown[] }>(
      'core_calendar_get_calendar_upcoming_view',
      { courseid: courseId, categoryid: categoryId }
    );
    return response.data;
  }

  /**
   * Get calendar events
   * Uses: core_calendar_get_calendar_events
   */
  async getEvents(
    events?: { eventids?: number[]; courseids?: number[]; groupids?: number[]; categoryids?: number[] },
    options?: {
      userevents?: boolean;
      siteevents?: boolean;
      timestart?: number;
      timeend?: number;
      ignorehidden?: boolean;
    }
  ): Promise<{ events: CalendarEvent[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ events: CalendarEvent[]; warnings?: unknown[] }>(
      'core_calendar_get_calendar_events',
      { events, options }
    );
    return response.data;
  }

  /**
   * Get event by ID
   * Uses: core_calendar_get_calendar_event_by_id
   */
  async getEventById(eventId: number): Promise<{ event: CalendarEvent; warnings?: unknown[] }> {
    const response = await this.callWebService<{ event: CalendarEvent; warnings?: unknown[] }>(
      'core_calendar_get_calendar_event_by_id',
      { eventid: eventId }
    );
    return response.data;
  }

  /**
   * Create calendar events
   * Uses: core_calendar_create_calendar_events
   */
  async createEvents(
    events: CreateCalendarEventParams[]
  ): Promise<{ events: Array<{ id: number; name: string }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{ events: Array<{ id: number; name: string }>; warnings?: unknown[] }>(
      'core_calendar_create_calendar_events',
      { events }
    );
    return response.data;
  }

  /**
   * Create a single event
   */
  async createEvent(event: CreateCalendarEventParams): Promise<{ id: number; name: string }> {
    const result = await this.createEvents([event]);
    return result.events[0];
  }

  /**
   * Delete calendar events
   * Uses: core_calendar_delete_calendar_events
   */
  async deleteEvents(events: Array<{ eventid: number; repeat: boolean }>): Promise<null> {
    const response = await this.callWebService<null>(
      'core_calendar_delete_calendar_events',
      { events }
    );
    return response.data;
  }

  /**
   * Delete a single event
   */
  async deleteEvent(eventId: number, deleteRepeats: boolean = false): Promise<null> {
    return this.deleteEvents([{ eventid: eventId, repeat: deleteRepeats }]);
  }

  /**
   * Get action events by timesort
   * Uses: core_calendar_get_action_events_by_timesort
   */
  async getActionEventsByTimesort(
    timesortFrom?: number,
    timesortTo?: number,
    afterEventId?: number,
    limitNum: number = 20,
    limitToEnabled: boolean = false,
    searchValue?: string
  ): Promise<{ events: CalendarEvent[]; firstid?: number; lastid?: number }> {
    const response = await this.callWebService<{ events: CalendarEvent[]; firstid?: number; lastid?: number }>(
      'core_calendar_get_action_events_by_timesort',
      {
        timesortfrom: timesortFrom,
        timesortto: timesortTo,
        aftereventid: afterEventId,
        limitnum: limitNum,
        limittoenabled: limitToEnabled,
        searchvalue: searchValue
      }
    );
    return response.data;
  }

  /**
   * Get action events by course
   * Uses: core_calendar_get_action_events_by_course
   */
  async getActionEventsByCourse(
    courseId: number,
    timesortFrom?: number,
    timesortTo?: number,
    afterEventId?: number,
    limitNum: number = 20,
    searchValue?: string
  ): Promise<{ events: CalendarEvent[]; firstid?: number; lastid?: number }> {
    const response = await this.callWebService<{ events: CalendarEvent[]; firstid?: number; lastid?: number }>(
      'core_calendar_get_action_events_by_course',
      {
        courseid: courseId,
        timesortfrom: timesortFrom,
        timesortto: timesortTo,
        aftereventid: afterEventId,
        limitnum: limitNum,
        searchvalue: searchValue
      }
    );
    return response.data;
  }

  /**
   * Get action events by courses
   * Uses: core_calendar_get_action_events_by_courses
   */
  async getActionEventsByCourses(
    courseIds: number[],
    timesortFrom?: number,
    timesortTo?: number,
    limitNum: number = 10,
    searchValue?: string
  ): Promise<{ groupedbycourse: Array<{ courseid: number; events: CalendarEvent[] }> }> {
    const response = await this.callWebService<{ groupedbycourse: Array<{ courseid: number; events: CalendarEvent[] }> }>(
      'core_calendar_get_action_events_by_courses',
      {
        courseids: courseIds,
        timesortfrom: timesortFrom,
        timesortto: timesortTo,
        limitnum: limitNum,
        searchvalue: searchValue
      }
    );
    return response.data;
  }

  /**
   * Update event start day
   * Uses: core_calendar_update_event_start_day
   */
  async updateEventStartDay(eventId: number, dayTimestamp: number): Promise<{ event: CalendarEvent }> {
    const response = await this.callWebService<{ event: CalendarEvent }>(
      'core_calendar_update_event_start_day',
      { eventid: eventId, daytimestamp: dayTimestamp }
    );
    return response.data;
  }

  /**
   * Get calendar access information
   * Uses: core_calendar_get_calendar_access_information
   */
  async getAccessInformation(courseId?: number): Promise<CalendarAccessInfo> {
    const response = await this.callWebService<CalendarAccessInfo>(
      'core_calendar_get_calendar_access_information',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get allowed event types
   * Uses: core_calendar_get_allowed_event_types
   */
  async getAllowedEventTypes(courseId?: number): Promise<{ allowedeventtypes: string[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ allowedeventtypes: string[]; warnings?: unknown[] }>(
      'core_calendar_get_allowed_event_types',
      { courseid: courseId }
    );
    return response.data;
  }

  /**
   * Get calendar export token
   * Uses: core_calendar_get_calendar_export_token
   */
  async getExportToken(): Promise<{ token: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{ token: string; warnings?: unknown[] }>(
      'core_calendar_get_calendar_export_token'
    );
    return response.data;
  }
}

// Singleton instance
export const calendarService = new CalendarService();

export default calendarService;
