// Moodle API Types - Message

export interface MoodleConversation {
  id: number;
  name?: string;
  subname?: string;
  imageurl?: string;
  type: number;
  membercount: number;
  ismuted: boolean;
  isfavourite: boolean;
  isread: boolean;
  unreadcount?: number;
  members: ConversationMember[];
  messages: ConversationMessage[];
  candeletemessagesforallusers?: boolean;
}

export interface ConversationMember {
  id: number;
  fullname: string;
  profileurl?: string;
  profileimageurl?: string;
  profileimageurlsmall?: string;
  isonline?: boolean;
  showonlinestatus?: boolean;
  isblocked?: boolean;
  iscontact?: boolean;
  isdeleted?: boolean;
  canmessageevenifblocked?: boolean;
  canmessage?: boolean;
  requirescontact?: boolean;
  contactrequests?: unknown[];
}

export interface ConversationMessage {
  id: number;
  useridfrom: number;
  text: string;
  timecreated: number;
}

export interface MoodleMessage {
  id: number;
  useridfrom: number;
  useridto: number;
  subject?: string;
  text: string;
  fullmessage?: string;
  fullmessageformat?: number;
  fullmessagehtml?: string;
  smallmessage?: string;
  notification?: number;
  contexturl?: string;
  contexturlname?: string;
  timecreated: number;
  timeread?: number;
  usertofullname?: string;
  userfromfullname?: string;
}

export interface MoodleNotification {
  id: number;
  useridfrom: number;
  useridto: number;
  subject: string;
  shortenedsubject?: string;
  text: string;
  fullmessage?: string;
  fullmessageformat?: number;
  fullmessagehtml?: string;
  smallmessage?: string;
  contexturl?: string;
  contexturlname?: string;
  timecreated: number;
  timecreatedpretty?: string;
  timeread?: number;
  read: boolean;
  deleted: boolean;
  iconurl?: string;
  component?: string;
  eventtype?: string;
  customdata?: string;
}

export interface PopupNotificationsResponse {
  notifications: MoodleNotification[];
  unreadcount: number;
}

export interface SendMessageParams {
  touserid: number;
  text: string;
  textformat?: number;
  clientmsgid?: string;
}

export interface SendMessageResponse {
  msgid: number;
  clientmsgid?: string;
  errormessage?: string;
}

export interface ConversationCountsResponse {
  favourites: number;
  types: {
    [key: number]: number;
  };
}

export interface ContactRequest {
  id: number;
  userid: number;
  requesteduserid: number;
  timecreated: number;
}

export interface BlockedUser {
  id: number;
  fullname: string;
  profileimageurl?: string;
}
