// Moodle API Types - Course Management

export interface MoodleCourse {
  id: number;
  shortname: string;
  fullname: string;
  displayname?: string;
  idnumber?: string;
  summary?: string;
  summaryformat?: number;
  format?: string;
  showgrades?: boolean;
  newsitems?: number;
  startdate?: number;
  enddate?: number;
  numsections?: number;
  marker?: number;
  maxbytes?: number;
  legacyfiles?: number;
  showscale?: boolean;
  visible?: boolean;
  groupmode?: number;
  groupmodeforce?: number;
  defaultgroupingid?: number;
  enablecompletion?: boolean;
  completionnotify?: boolean;
  lang?: string;
  theme?: string;
  timecreated?: number;
  timemodified?: number;
  sortorder?: number;
  calendartype?: string;
  category?: number;
  categoryid?: number;
  categorysortorder?: number;
  coursecategoryid?: number;
  progress?: number;
  completed?: boolean;
  enrolledusercount?: number;
  overviewfiles?: Array<{
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    mimetype: string;
  }>;
  courseformatoptions?: Array<{
    name: string;
    value: unknown;
  }>;
}

export interface MoodleCourseContent {
  id: number;
  name: string;
  visible?: number;
  summary: string;
  summaryformat: number;
  section?: number;
  hiddenbynumsections?: number;
  uservisible?: boolean;
  modules: MoodleCourseModule[];
}

export interface MoodleCourseModule {
  id: number;
  url?: string;
  name: string;
  instance?: number;
  contextid?: number;
  description?: string;
  visible?: number;
  uservisible?: boolean;
  availabilityinfo?: string;
  visibleoncoursepage?: number;
  modicon?: string;
  modname: string;
  modplural?: string;
  availability?: string;
  indent?: number;
  onclick?: string;
  afterlink?: string;
  customdata?: string;
  noviewlink?: boolean;
  completion?: number;
  completiondata?: {
    state: number;
    timecompleted: number;
    overrideby: number | null;
    valueused?: boolean;
  };
  contents?: Array<{
    type: string;
    filename: string;
    filepath: string;
    filesize: number;
    fileurl: string;
    timecreated: number;
    timemodified: number;
    sortorder: number;
    mimetype?: string;
    isexternalfile?: boolean;
    userid?: number;
    author?: string;
    license?: string;
  }>;
  contentsinfo?: {
    filescount: number;
    filessize: number;
    lastmodified: number;
    mimetypes: string[];
    repositorytype?: string;
  };
}

export interface MoodleCategory {
  id: number;
  name: string;
  idnumber?: string;
  description?: string;
  descriptionformat?: number;
  parent?: number;
  sortorder?: number;
  coursecount?: number;
  visible?: number;
  visibleold?: number;
  timemodified?: number;
  depth?: number;
  path?: string;
  theme?: string;
}

export interface CreateCourseParams {
  fullname: string;
  shortname: string;
  categoryid: number;
  idnumber?: string;
  summary?: string;
  summaryformat?: number;
  format?: string;
  showgrades?: number;
  newsitems?: number;
  startdate?: number;
  enddate?: number;
  numsections?: number;
  maxbytes?: number;
  showreports?: number;
  visible?: number;
  hiddensections?: number;
  groupmode?: number;
  groupmodeforce?: number;
  defaultgroupingid?: number;
  enablecompletion?: number;
  completionnotify?: number;
  lang?: string;
  forcetheme?: string;
  courseformatoptions?: Array<{ name: string; value: unknown }>;
  customfields?: Array<{ shortname: string; value: unknown }>;
}

export interface UpdateCourseParams {
  id: number;
  fullname?: string;
  shortname?: string;
  categoryid?: number;
  idnumber?: string;
  summary?: string;
  summaryformat?: number;
  format?: string;
  showgrades?: number;
  newsitems?: number;
  startdate?: number;
  enddate?: number;
  numsections?: number;
  maxbytes?: number;
  showreports?: number;
  visible?: number;
  hiddensections?: number;
  groupmode?: number;
  groupmodeforce?: number;
  defaultgroupingid?: number;
  enablecompletion?: number;
  completionnotify?: number;
  lang?: string;
  forcetheme?: string;
  courseformatoptions?: Array<{ name: string; value: unknown }>;
  customfields?: Array<{ shortname: string; value: unknown }>;
}

export interface CreateCategoryParams {
  name: string;
  parent?: number;
  idnumber?: string;
  description?: string;
  descriptionformat?: number;
  theme?: string;
}

export interface CourseSearchCriteria {
  criterianame: 'search' | 'modulelist' | 'blocklist' | 'tagid';
  criteriavalue: string;
}

export interface CourseByFieldParams {
  field: 'id' | 'ids' | 'shortname' | 'idnumber' | 'category';
  value: string;
}
