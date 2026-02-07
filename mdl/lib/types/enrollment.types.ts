// Moodle API Types - Enrollment

export interface EnrolmentMethod {
  id: number;
  courseid: number;
  type: string;
  name: string;
  status: string;
  wsfunction?: string;
}

export interface EnrolledUser {
  id: number;
  username: string;
  firstname: string;
  lastname: string;
  fullname: string;
  email: string;
  department?: string;
  firstaccess?: number;
  lastaccess?: number;
  lastcourseaccess?: number;
  description?: string;
  descriptionformat?: number;
  city?: string;
  country?: string;
  profileimageurlsmall?: string;
  profileimageurl?: string;
  groups?: Array<{
    id: number;
    name: string;
    description?: string;
    descriptionformat?: number;
  }>;
  roles?: Array<{
    roleid: number;
    name: string;
    shortname: string;
    sortorder: number;
  }>;
  enrolledcourses?: Array<{
    id: number;
    fullname: string;
    shortname: string;
  }>;
}

export interface UserEnrolment {
  id: number;
  courseid: number;
  userid: number;
  status: number;
  enrolid: number;
  timestart: number;
  timeend: number;
  timecreated: number;
  timemodified: number;
  roleid?: number;
}

export interface ManualEnrolParams {
  roleid: number;
  userid: number;
  courseid: number;
  timestart?: number;
  timeend?: number;
  suspend?: number;
}

export interface SelfEnrolParams {
  courseid: number;
  password?: string;
  instanceid?: number;
}

export interface SelfEnrolResponse {
  status: boolean;
  warnings?: unknown[];
}

export interface GuestEnrolInfo {
  instanceid: number;
  status: boolean;
  passwordrequired: boolean;
  warnings?: unknown[];
}
