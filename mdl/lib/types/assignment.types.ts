// Moodle API Types - Assignment

export interface MoodleAssignment {
  id: number;
  cmid: number;
  course: number;
  name: string;
  nosubmissions: number;
  submissiondrafts: number;
  sendnotifications: number;
  sendlatenotifications: number;
  sendstudentnotifications: number;
  duedate: number;
  allowsubmissionsfromdate: number;
  grade: number;
  timemodified: number;
  completionsubmit: number;
  cutoffdate: number;
  gradingduedate: number;
  teamsubmission: number;
  requireallteammemberssubmit: number;
  teamsubmissiongroupingid: number;
  blindmarking: number;
  hidegrader: number;
  revealidentities: number;
  attemptreopenmethod: string;
  maxattempts: number;
  markingworkflow: number;
  markingallocation: number;
  requiresubmissionstatement: number;
  preventsubmissionnotingroup?: number;
  configs?: Array<{
    id?: number;
    assignment?: number;
    plugin: string;
    subtype: string;
    name: string;
    value: string;
  }>;
  intro?: string;
  introformat?: number;
  introfiles?: Array<{
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    timemodified: number;
    mimetype: string;
    isexternalfile?: boolean;
  }>;
  introattachments?: unknown[];
}

export interface AssignmentCourse {
  id: number;
  fullname: string;
  shortname: string;
  timemodified: number;
  assignments: MoodleAssignment[];
}

export interface MoodleSubmission {
  id: number;
  userid: number;
  attemptnumber: number;
  timecreated: number;
  timemodified: number;
  status: 'new' | 'draft' | 'submitted';
  groupid: number;
  assignment?: number;
  latest?: number;
  plugins?: Array<{
    type: string;
    name: string;
    fileareas?: Array<{
      area: string;
      files: unknown[];
    }>;
    editorfields?: Array<{
      name: string;
      description: string;
      text: string;
      format: number;
    }>;
  }>;
  gradingstatus?: string;
}

export interface SubmissionStatus {
  gradingsummary?: {
    participantcount: number;
    submissiondraftscount: number;
    submissionsenabled: boolean;
    submissionssubmittedcount: number;
    submissionsneedgradingcount: number;
    warnofungroupedusers: string;
  };
  lastattempt?: {
    submission?: MoodleSubmission;
    teamsubmission?: MoodleSubmission;
    submissiongroup?: number;
    submissiongroupmemberswhoneedtosubmit?: number[];
    submissionsenabled: boolean;
    locked: boolean;
    graded: boolean;
    canedit: boolean;
    caneditowner: boolean;
    cansubmit: boolean;
    extensionduedate?: number;
    blindmarking: boolean;
    gradingstatus: string;
    usergroups: number[];
  };
  feedback?: {
    grade?: {
      id: number;
      assignment: number;
      userid: number;
      attemptnumber: number;
      timecreated: number;
      timemodified: number;
      grader: number;
      grade: string;
      gradefordisplay?: string;
      gradeddate?: number;
    };
    gradefordisplay?: string;
    gradeddate?: number;
    plugins?: unknown[];
  };
  previousattempts?: unknown[];
  assignmentdata?: {
    attachments?: {
      intro?: unknown[];
      activity?: unknown[];
    };
    activity?: string;
    activityformat?: number;
  };
  warnings?: unknown[];
}

export interface AssignmentGrade {
  id: number;
  assignment: number;
  userid: number;
  attemptnumber: number;
  timecreated: number;
  timemodified: number;
  grader: number;
  grade: string;
  gradefordisplay?: string;
  gradeddate?: number;
}

export interface AssignmentParticipant {
  id: number;
  fullname: string;
  submitted: boolean;
  requiregrading: boolean;
  grantedextension: boolean;
  groupid?: number;
  groupname?: string;
}

export interface SaveSubmissionParams {
  assignmentid: number;
  plugindata: {
    onlinetext_editor?: {
      text: string;
      format: number;
      itemid: number;
    };
    files_filemanager?: number;
  };
}

export interface SaveGradeParams {
  assignmentid: number;
  userid: number;
  grade: number;
  attemptnumber: number;
  addattempt: number;
  workflowstate: string;
  applytoall: number;
  plugindata?: {
    assignfeedbackcomments_editor?: {
      text: string;
      format: number;
    };
    files_filemanager?: number;
  };
}
