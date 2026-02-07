// Moodle API Types - Calendar

export interface CalendarEvent {
  id: number;
  name: string;
  description?: string;
  descriptionformat?: number;
  location?: string;
  categoryid?: number;
  groupid?: number;
  userid?: number;
  repeatid?: number;
  eventcount?: number;
  component?: string;
  modulename?: string;
  instance?: number;
  eventtype: string;
  timestart: number;
  timeduration: number;
  timesort?: number;
  timeusermidnight?: number;
  visible: number;
  timemodified?: number;
  overdue?: boolean;
  icon?: {
    key: string;
    component: string;
    alttext: string;
    iconurl?: string;
    iconclass?: string;
  };
  category?: {
    id: number;
    name: string;
    idnumber?: string;
    description?: string;
    parent?: number;
    coursecount?: number;
    visible?: number;
    timemodified?: number;
    depth?: number;
    nestedname?: string;
    url?: string;
  };
  course?: {
    id: number;
    fullname: string;
    shortname: string;
    idnumber?: string;
    summary?: string;
    summaryformat?: number;
    startdate?: number;
    enddate?: number;
    visible?: boolean;
    showactivitydates?: boolean;
    showcompletionconditions?: boolean;
    fullnamedisplay?: string;
    viewurl?: string;
    courseimage?: string;
    progress?: number;
    hasprogress?: boolean;
    isfavourite?: boolean;
    hidden?: boolean;
    showshortname?: boolean;
    coursecategory?: string;
  };
  subscription?: {
    displayeventsource?: boolean;
    subscriptionname?: string;
    subscriptionurl?: string;
  };
  canedit?: boolean;
  candelete?: boolean;
  deleteurl?: string;
  editurl?: string;
  viewurl?: string;
  formattedtime?: string;
  formattedlocation?: string;
  isactionevent?: boolean;
  iscourseevent?: boolean;
  iscategoryevent?: boolean;
  groupname?: string;
  normalisedeventtype?: string;
  normalisedeventtypetext?: string;
  action?: {
    name: string;
    url: string;
    itemcount: number;
    actionable: boolean;
    showitemcount: boolean;
  };
  purpose?: string;
  url?: string;
}

export interface CalendarDay {
  seconds: number;
  minutes: number;
  hours: number;
  mday: number;
  wday: number;
  mon: number;
  year: number;
  yday: number;
  weekday: string;
  month: string;
  timestamp: number;
  neweventtimestamp?: number;
  viewdaylink?: string;
  viewdaylinktitle?: string;
  events: CalendarEvent[];
  hasevents: boolean;
  calendareventtypes: string[];
  previousperiod?: number;
  nextperiod?: number;
  navigation?: string;
  haslastdayofevent?: boolean;
  popovertitle?: string;
  ispast?: boolean;
  istoday?: boolean;
  isweekend?: boolean;
}

export interface CalendarMonthView {
  url: string;
  courseid: number;
  categoryid?: number;
  filter_selector?: string;
  weeks: Array<{
    prepadding: number[];
    postpadding: number[];
    days: CalendarDay[];
  }>;
  daynames: Array<{
    dayno: number;
    shortname: string;
    fullname: string;
  }>;
  date: {
    seconds: number;
    minutes: number;
    hours: number;
    mday: number;
    wday: number;
    mon: number;
    year: number;
    yday: number;
    weekday: string;
    month: string;
    timestamp: number;
  };
  periodname: string;
  includenavigation: boolean;
  initialeventsloaded?: boolean;
  previousperiod?: {
    url: string;
    timestamp: number;
  };
  nextperiod?: {
    url: string;
    timestamp: number;
  };
  largeithreshold?: number;
  defaulteventcontext?: number;
}

export interface CreateCalendarEventParams {
  name: string;
  description?: string;
  descriptionformat?: number;
  location?: string;
  format?: number;
  courseid?: number;
  groupid?: number;
  repeats?: number;
  eventtype?: string;
  timestart?: number;
  timeduration?: number;
  visible?: number;
  sequence?: number;
}

export interface CalendarAccessInfo {
  canmanageentries: boolean;
  canmanageownentries: boolean;
  canmanagegroupentries?: boolean;
}
