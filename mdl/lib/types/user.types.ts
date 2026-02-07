// Moodle API Types - User Management

export interface MoodleUser {
  id: number;
  username: string;
  firstname: string;
  lastname: string;
  fullname: string;
  email: string;
  department?: string;
  institution?: string;
  phone1?: string;
  phone2?: string;
  address?: string;
  city?: string;
  country?: string;
  description?: string;
  descriptionformat?: number;
  auth?: string;
  confirmed?: boolean;
  suspended?: boolean;
  deleted?: boolean;
  idnumber?: string;
  lang?: string;
  theme?: string;
  timezone?: string;
  firstaccess?: number;
  lastaccess?: number;
  lastlogin?: number;
  currentlogin?: number;
  picture?: number;
  url?: string;
  mailformat?: number;
  maildigest?: number;
  maildisplay?: number;
  autosubscribe?: number;
  trackforums?: number;
  mnethostid?: number;
  imagealt?: string;
  lastsiteaccess?: number;
  lastip?: string;
  secret?: string;
  profileimageurlsmall?: string;
  profileimageurl?: string;
  customfields?: Array<{
    type: string;
    value: string;
    name: string;
    shortname: string;
  }>;
  preferences?: Array<{
    name: string;
    value: string;
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

export interface CreateUserParams {
  username: string;
  password: string;
  firstname: string;
  lastname: string;
  email: string;
  auth?: string;
  idnumber?: string;
  lang?: string;
  theme?: string;
  timezone?: string;
  mailformat?: number;
  description?: string;
  city?: string;
  country?: string;
  institution?: string;
  department?: string;
  phone1?: string;
  phone2?: string;
  address?: string;
  customfields?: Array<{ type: string; name: string; value: string }>;
  preferences?: Array<{ type: string; name: string; value: string }>;
}

export interface UpdateUserParams {
  id: number;
  username?: string;
  password?: string;
  firstname?: string;
  lastname?: string;
  email?: string;
  auth?: string;
  idnumber?: string;
  lang?: string;
  theme?: string;
  timezone?: string;
  mailformat?: number;
  description?: string;
  city?: string;
  country?: string;
  institution?: string;
  department?: string;
  phone1?: string;
  phone2?: string;
  address?: string;
  suspended?: boolean;
  customfields?: Array<{ type: string; name: string; value: string }>;
  preferences?: Array<{ type: string; name: string; value: string }>;
}

export interface UserCriteria {
  key: 'id' | 'lastname' | 'firstname' | 'idnumber' | 'username' | 'email' | 'auth';
  value: string;
}

export interface UserPreference {
  name: string;
  value: string;
  userid?: number;
}
