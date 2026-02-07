// Moodle API Types - Completion

export interface ActivityCompletion {
  cmid: number;
  modname: string;
  instance: number;
  state: number;
  timecompleted: number;
  tracking: number;
  overrideby?: number;
  valueused?: boolean;
  hascompletion?: boolean;
  isautomatic?: boolean;
  istrackeduser?: boolean;
  uservisible?: boolean;
  details?: Array<{
    rulename: string;
    rulevalue: {
      status: number;
      description: string;
    };
  }>;
}

export interface ActivitiesCompletionStatus {
  statuses: ActivityCompletion[];
  warnings?: unknown[];
}

export interface CourseCompletionStatus {
  completed: boolean;
  aggregation: number;
  completions: Array<{
    type: number;
    title: string;
    status: string;
    complete: boolean;
    timecompleted?: number;
    details: {
      type: string;
      criteria: string;
      requirement: string;
      status: string;
    };
  }>;
}

export interface CourseProgress {
  courseid: number;
  userid: number;
  progress?: number;
}
