// Moodle API Types - Badge

export interface MoodleBadge {
  id: number;
  name: string;
  description: string;
  timecreated: number;
  timemodified: number;
  usercreated: number;
  usermodified: number;
  issuername: string;
  issuerurl: string;
  issuercontact?: string;
  expiredate?: number;
  expireperiod?: number;
  type: number;
  courseid?: number;
  message?: string;
  messagesubject?: string;
  attachment?: number;
  notification?: number;
  status: number;
  nextcron?: number;
  version?: string;
  language?: string;
  imageauthorname?: string;
  imageauthoremail?: string;
  imageauthorurl?: string;
  imagecaption?: string;
  badgeurl: string;
  endorsement?: {
    id: number;
    badgeid: number;
    issuername: string;
    issuerurl: string;
    issueremail: string;
    claimid?: string;
    claimcomment?: string;
    dateissued: number;
  };
  alignment?: Array<{
    id: number;
    badgeid: number;
    targetname: string;
    targeturl: string;
    targetdescription?: string;
    targetframework?: string;
    targetcode?: string;
  }>;
  relatedbadges?: Array<{
    id: number;
    name: string;
    version?: string;
    language?: string;
    type: number;
  }>;
}

export interface UserBadge extends MoodleBadge {
  dateissued: number;
  dateexpire?: number;
  uniquehash: string;
  email?: string;
  visible?: number;
}

export interface BadgeCriteria {
  id: number;
  badgeid: number;
  criteriatype: number;
  method?: number;
  descriptionformat?: number;
  description?: string;
  params?: Array<{
    id: number;
    critid: number;
    name: string;
    value?: string;
  }>;
}
