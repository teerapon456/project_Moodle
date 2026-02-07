// Chat Service
// Moodle API functions: mod_chat_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Chat {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  keepdays: number;
  studentlogs: number;
  chattime: number;
  schedule: number;
  timemodified: number;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface ChatSession {
  sessionstart: number;
  sessionend: number;
  sessionusers: unknown[];
  iscomplete: boolean;
}

export interface ChatMessage {
  id: number;
  chatid: number;
  userid: number;
  groupid: number;
  issystem: boolean;
  message: string;
  timestamp: number;
}

export class ChatService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get chats by courses
   * Uses: mod_chat_get_chats_by_courses
   */
  async getChatsByCourses(courseIds?: number[]): Promise<{ chats: Chat[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ chats: Chat[]; warnings?: unknown[] }>(
      'mod_chat_get_chats_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * Login to a chat session
   * Uses: mod_chat_login_user
   */
  async loginUser(chatId: number, groupId?: number): Promise<{ chatsid: string; warnings?: unknown[] }> {
    const response = await this.callWebService<{ chatsid: string; warnings?: unknown[] }>(
      'mod_chat_login_user',
      { chatid: chatId, groupid: groupId }
    );
    return response.data;
  }

  /**
   * Get chat latest messages
   * Uses: mod_chat_get_chat_latest_messages
   */
  async getLatestMessages(chatSid: string, chatLastTime?: number): Promise<{ messages: ChatMessage[]; chatnewlasttime: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ messages: ChatMessage[]; chatnewlasttime: number; warnings?: unknown[] }>(
      'mod_chat_get_chat_latest_messages',
      { chatsid: chatSid, chatlasttime: chatLastTime }
    );
    return response.data;
  }

  /**
   * Send chat message
   * Uses: mod_chat_send_chat_message
   */
  async sendMessage(chatSid: string, messageText: string, beepId?: string): Promise<{ messageid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ messageid: number; warnings?: unknown[] }>(
      'mod_chat_send_chat_message',
      { chatsid: chatSid, messagetext: messageText, beepid: beepId }
    );
    return response.data;
  }

  /**
   * Get chat users
   * Uses: mod_chat_get_chat_users
   */
  async getChatUsers(chatSid: string): Promise<{ users: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ users: unknown[]; warnings?: unknown[] }>(
      'mod_chat_get_chat_users',
      { chatsid: chatSid }
    );
    return response.data;
  }

  /**
   * View chat (log event)
   * Uses: mod_chat_view_chat
   */
  async viewChat(chatId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_chat_view_chat',
      { chatid: chatId }
    );
    return response.data;
  }

  /**
   * Get sessions
   * Uses: mod_chat_get_sessions
   */
  async getSessions(chatId: number, groupId?: number, showAll?: boolean): Promise<{ sessions: ChatSession[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ sessions: ChatSession[]; warnings?: unknown[] }>(
      'mod_chat_get_sessions',
      { chatid: chatId, groupid: groupId, showall: showAll }
    );
    return response.data;
  }

  /**
   * Get session messages
   * Uses: mod_chat_get_session_messages
   */
  async getSessionMessages(chatId: number, sessionStart: number, sessionEnd: number, groupId?: number): Promise<{ messages: ChatMessage[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ messages: ChatMessage[]; warnings?: unknown[] }>(
      'mod_chat_get_session_messages',
      { chatid: chatId, sessionstart: sessionStart, sessionend: sessionEnd, groupid: groupId }
    );
    return response.data;
  }
}

// Singleton instance
export const chatService = new ChatService();

export default chatService;
