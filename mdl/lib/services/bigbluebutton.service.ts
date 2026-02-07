// BigBlueButton Service
// Moodle API functions: mod_bigbluebuttonbn_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface BigBlueButtonBN {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  meetingid: string;
  moderatorpass: string;
  viewerpass: string;
  wait: boolean;
  record: boolean;
  recordalwaysdisplayoption: boolean;
  recordaltrecordingoutputs: number;
  recordhidebutton: boolean;
  voicebridge: number;
  openingtime: number;
  closingtime: number;
  timecreated: number;
  timemodified: number;
  presentation: string;
  participants: string;
  userlimit: number;
  recordings_html: boolean;
  recordings_deleted: boolean;
  recordings_imported: boolean;
  recordings_preview: boolean;
  clienttype: number;
  muteonstart: boolean;
  disablecam: boolean;
  disablemic: boolean;
  disableprivatechat: boolean;
  disablepublicchat: boolean;
  disablenote: boolean;
  hideuserlist: boolean;
  lockedlayout: boolean;
  lockonjoin: boolean;
  lockonjoinconfigurable: boolean;
  coursemodule: number;
}

export interface BBBMeeting {
  meetingID: string;
  meetingName: string;
  createDate: string;
  hasBeenForciblyEnded: boolean;
  hasUserJoined: boolean;
  internalMeetingID: string;
  isBreakout: boolean;
  moderatorPW: string;
  attendeePW: string;
  participantCount: number;
  running: boolean;
  voiceBridge: number;
  durationInMinutes: number;
}

export interface BBBRecording {
  recordID: string;
  meetingID: string;
  meetingName: string;
  published: boolean;
  state: string;
  startTime: number;
  endTime: number;
  participants: number;
  rawSize: number;
  size: number;
  playback: {
    type: string;
    url: string;
    processingTime: number;
    length: number;
  }[];
  metadata: Record<string, string>;
}

export class BigBlueButtonService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get BigBlueButton by courses
   * Uses: mod_bigbluebuttonbn_get_bigbluebuttonbns_by_courses
   */
  async getBigBlueButtonBnsByCourses(courseIds?: number[]): Promise<{ bigbluebuttonbns: BigBlueButtonBN[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ bigbluebuttonbns: BigBlueButtonBN[]; warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_get_bigbluebuttonbns_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View BigBlueButton (log event)
   * Uses: mod_bigbluebuttonbn_view_bigbluebuttonbn
   */
  async viewBigBlueButtonBn(bigbluebuttonbnId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_view_bigbluebuttonbn',
      { bigbluebuttonbnid: bigbluebuttonbnId }
    );
    return response.data;
  }

  /**
   * Get join URL
   * Uses: mod_bigbluebuttonbn_get_join_url
   */
  async getJoinUrl(cmId: number, groupId?: number): Promise<{ join_url: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{ join_url: string; warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_get_join_url',
      { cmid: cmId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Meeting info
   * Uses: mod_bigbluebuttonbn_meeting_info
   */
  async getMeetingInfo(bigbluebuttonbnId: number, groupId?: number, updateCache?: boolean): Promise<{ cmid: number; userlimit: number; bigbluebuttonbnid: string; meetingid: string; openingtime: number; closingtime: number; statusrunning: boolean; statusclosed: boolean; statusopen: boolean; statusmessage: string; moderatorcount: number; participantcount: number; moderatorplural: boolean; participantplural: boolean; canjoin: boolean; presentations: unknown[]; joinurl: string; guestaccessenabled: boolean; guestjoinurl: string; guestpassword: string; features: unknown }> {
    const response = await this.callWebService<{
      cmid: number;
      userlimit: number;
      bigbluebuttonbnid: string;
      meetingid: string;
      openingtime: number;
      closingtime: number;
      statusrunning: boolean;
      statusclosed: boolean;
      statusopen: boolean;
      statusmessage: string;
      moderatorcount: number;
      participantcount: number;
      moderatorplural: boolean;
      participantplural: boolean;
      canjoin: boolean;
      presentations: unknown[];
      joinurl: string;
      guestaccessenabled: boolean;
      guestjoinurl: string;
      guestpassword: string;
      features: unknown;
    }>('mod_bigbluebuttonbn_meeting_info', {
      bigbluebuttonbnid: bigbluebuttonbnId,
      groupid: groupId,
      updatecache: updateCache
    });
    return response.data;
  }

  /**
   * End meeting
   * Uses: mod_bigbluebuttonbn_end_meeting
   */
  async endMeeting(bigbluebuttonbnId: number, groupId?: number): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_end_meeting',
      { bigbluebuttonbnid: bigbluebuttonbnId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Can join
   * Uses: mod_bigbluebuttonbn_can_join
   */
  async canJoin(cmId: number, groupId?: number): Promise<{ can_join: boolean; message?: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{ can_join: boolean; message?: string; warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_can_join',
      { cmid: cmId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get recordings
   * Uses: mod_bigbluebuttonbn_get_recordings
   */
  async getRecordings(bigbluebuttonbnId: number, tools?: string, groupId?: number): Promise<{ status: boolean; tabledata: { columns: unknown[]; data: unknown[] }; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      status: boolean;
      tabledata: { columns: unknown[]; data: unknown[] };
      warnings?: unknown[];
    }>('mod_bigbluebuttonbn_get_recordings', {
      bigbluebuttonbnid: bigbluebuttonbnId,
      tools,
      groupid: groupId
    });
    return response.data;
  }

  /**
   * Get recordings to import
   * Uses: mod_bigbluebuttonbn_get_recordings_to_import
   */
  async getRecordingsToImport(destinationInstanceId: number, sourceInstanceId?: number, sourceCourseId?: number, tools?: string, groupId?: number): Promise<{ status: boolean; tabledata: { columns: unknown[]; data: unknown[] }; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      status: boolean;
      tabledata: { columns: unknown[]; data: unknown[] };
      warnings?: unknown[];
    }>('mod_bigbluebuttonbn_get_recordings_to_import', {
      destinationinstanceid: destinationInstanceId,
      sourceinstanceid: sourceInstanceId,
      sourcecourseid: sourceCourseId,
      tools,
      groupid: groupId
    });
    return response.data;
  }

  /**
   * Update recording
   * Uses: mod_bigbluebuttonbn_update_recording
   */
  async updateRecording(bigbluebuttonbnId: number, recordingId: string, action: string, additionaloptions?: string): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_update_recording',
      { bigbluebuttonbnid: bigbluebuttonbnId, recordingid: recordingId, action, additionaloptions }
    );
    return response.data;
  }

  /**
   * Completion validate
   * Uses: mod_bigbluebuttonbn_completion_validate
   */
  async completionValidate(bigbluebuttonbnId: number): Promise<{ warnings?: unknown[] }> {
    const response = await this.callWebService<{ warnings?: unknown[] }>(
      'mod_bigbluebuttonbn_completion_validate',
      { bigbluebuttonbnid: bigbluebuttonbnId }
    );
    return response.data;
  }
}

// Singleton instance
export const bigBlueButtonService = new BigBlueButtonService();

export default bigBlueButtonService;
