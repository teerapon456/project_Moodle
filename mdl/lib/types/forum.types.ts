// Moodle API Types - Forum

export interface MoodleForum {
  id: number;
  course: number;
  type: string;
  name: string;
  intro: string;
  introformat?: number;
  introfiles?: unknown[];
  duedate?: number;
  cutoffdate?: number;
  assessed?: number;
  assesstimestart?: number;
  assesstimefinish?: number;
  scale?: number;
  maxbytes?: number;
  maxattachments?: number;
  forcesubscribe?: number;
  trackingtype?: number;
  rsstype?: number;
  rssarticles?: number;
  timemodified?: number;
  warnafter?: number;
  blockafter?: number;
  blockperiod?: number;
  completiondiscussions?: number;
  completionreplies?: number;
  completionposts?: number;
  cmid: number;
  numdiscussions?: number;
  cancreatediscussions?: boolean;
  lockdiscussionafter?: number;
  istracked?: boolean;
  unreadpostscount?: number;
}

export interface ForumDiscussion {
  id: number;
  name: string;
  groupid: number;
  timemodified: number;
  usermodified: number;
  timestart: number;
  timeend: number;
  discussion: number;
  parent: number;
  userid: number;
  created: number;
  modified: number;
  mailed: number;
  subject: string;
  message: string;
  messageformat: number;
  messagetrust: number;
  messageinlinefiles?: unknown[];
  attachment: string;
  attachments?: unknown[];
  totalscore: number;
  mailnow: number;
  userfullname?: string;
  usermodifiedfullname?: string;
  userpictureurl?: string;
  usermodifiedpictureurl?: string;
  numreplies?: number;
  numunread?: number;
  pinned?: boolean;
  locked?: boolean;
  starred?: boolean;
  canreply?: boolean;
  canlock?: boolean;
  canfavourite?: boolean;
}

export interface ForumPost {
  id: number;
  discussionid: number;
  parentid: number;
  hasparent: boolean;
  timecreated: number;
  timemodified?: number;
  subject: string;
  replysubject?: string;
  message: string;
  messageformat: number;
  author: {
    id: number;
    fullname: string;
    isdeleted?: boolean;
    groups?: unknown[];
    urls: {
      profile?: string;
      profileimage?: string;
    };
  };
  attachments?: unknown[];
  messageinlinefiles?: unknown[];
  tags?: unknown[];
  html?: {
    rating?: string;
    taglist?: string;
    authorsubheading?: string;
  };
  charcount?: number;
  wordcount?: number;
  capabilities?: {
    view: boolean;
    edit: boolean;
    delete: boolean;
    split: boolean;
    reply: boolean;
    export: boolean;
    controlreadstatus: boolean;
    canreplyprivately?: boolean;
    selfenrol?: boolean;
  };
  unread?: boolean;
  isdeleted?: boolean;
  isprivatereply?: boolean;
  haswordcount?: boolean;
}

export interface AddDiscussionParams {
  forumid: number;
  subject: string;
  message: string;
  groupid?: number;
  options?: Array<{
    name: string;
    value: unknown;
  }>;
}

export interface AddPostParams {
  postid: number;
  subject: string;
  message: string;
  messageformat?: number;
  options?: Array<{
    name: string;
    value: unknown;
  }>;
}
