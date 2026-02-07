// Moodle API Types - Grade

export interface GradeItem {
  id: number;
  itemname?: string;
  itemtype: string;
  itemmodule?: string;
  iteminstance?: number;
  itemnumber?: number;
  idnumber?: string;
  categoryid?: number;
  outcomeid?: number;
  scaleid?: number;
  locked?: boolean;
  cmid?: number;
  weightraw?: number;
  weightformatted?: string;
  status?: string;
  graderaw?: number;
  gradedatesubmitted?: number;
  gradedategraded?: number;
  gradehiddenbydate?: boolean;
  gradeneedsupdate?: boolean;
  gradeishidden?: boolean;
  gradeislocked?: boolean;
  gradeisoverridden?: boolean;
  gradeformatted?: string;
  grademin?: number;
  grademax?: number;
  rangeformatted?: string;
  percentageformatted?: string;
  lettergradeformatted?: string;
  rank?: number;
  numusers?: number;
  averageformatted?: string;
  feedback?: string;
  feedbackformat?: number;
}

export interface GradeCategory {
  id: number;
  courseid: number;
  parent?: number;
  depth: number;
  path: string;
  fullname: string;
  aggregation: number;
  keephigh: number;
  droplow: number;
  aggregateonlygraded: boolean;
  aggregateoutcomes: boolean;
  timecreated: number;
  timemodified: number;
  hidden: boolean;
}

export interface UserGradesTable {
  courseid: number;
  userid: number;
  userfullname: string;
  maxdepth: number;
  tabledata: Array<{
    itemname?: {
      class?: string;
      colspan?: number;
      content?: string;
      id?: string;
    };
    leader?: {
      class?: string;
      rowspan?: number;
    };
    weight?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    grade?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    range?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    percentage?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    lettergrade?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    rank?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    average?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    feedback?: {
      class?: string;
      content?: string;
      headers?: string;
    };
    contributiontocoursetotal?: {
      class?: string;
      content?: string;
      headers?: string;
    };
  }>;
  warnings?: unknown[];
}

export interface CourseGrades {
  courseid: number;
  grade?: string;
  rawgrade?: string;
  rank?: number;
}

export interface OverviewGrades {
  grades: CourseGrades[];
  warnings?: unknown[];
}

export interface GradeAccessInfo {
  canviewmygrades: boolean;
  canviewallgrades: boolean;
  canviewhiddengrades?: boolean;
}

export interface UpdateGradesParams {
  source: string;
  courseid: number;
  component: string;
  activityid: number;
  itemnumber: number;
  grades: Array<{
    studentid: number;
    grade: number;
    feedback?: string;
    feedbackformat?: number;
  }>;
  itemdetails?: {
    itemname?: string;
    idnumber?: number;
    gradetype?: number;
    grademax?: number;
    grademin?: number;
    scaleid?: number;
    multfactor?: number;
    plusfactor?: number;
    deleted?: boolean;
    hidden?: boolean;
  };
}
