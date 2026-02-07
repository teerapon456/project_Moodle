// Message Service
// Moodle API functions: core_message_*, message_popup_*

import MoodleApiClient from '@/lib/moodle-api';
import type {
  MoodleConversation,
  ConversationMessage,
  MoodleNotification,
  PopupNotificationsResponse,
  SendMessageParams,
  SendMessageResponse,
  ConversationCountsResponse,
  BlockedUser
} from '../types';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export class MessageService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get conversations for a user
   * Uses: core_message_get_conversations
   */
  async getConversations(
    userId: number,
    limitFrom: number = 0,
    limitNum: number = 20,
    type?: number,
    favourites?: boolean,
    mergeself: boolean = false
  ): Promise<{ conversations: MoodleConversation[] }> {
    const response = await this.callWebService<{ conversations: MoodleConversation[] }>(
      'core_message_get_conversations',
      { userid: userId, limitfrom: limitFrom, limitnum: limitNum, type, favourites, mergeself }
    );
    return response.data;
  }

  /**
   * Get a single conversation
   * Uses: core_message_get_conversation
   */
  async getConversation(
    userId: number,
    conversationId: number,
    includeContactRequests: boolean = false,
    includePrivacyInfo: boolean = false,
    memberLimit: number = 0,
    memberOffset: number = 0,
    messageLimit: number = 100,
    messageOffset: number = 0,
    newestFirst: boolean = true
  ): Promise<MoodleConversation> {
    const response = await this.callWebService<MoodleConversation>(
      'core_message_get_conversation',
      {
        userid: userId,
        conversationid: conversationId,
        includecontactrequests: includeContactRequests,
        includeprivacyinfo: includePrivacyInfo,
        memberlimit: memberLimit,
        memberoffset: memberOffset,
        messagelimit: messageLimit,
        messageoffset: messageOffset,
        newestmessagesfirst: newestFirst
      }
    );
    return response.data;
  }

  /**
   * Get conversation between two users
   * Uses: core_message_get_conversation_between_users
   */
  async getConversationBetweenUsers(
    userId: number,
    otherUserId: number,
    includeContactRequests: boolean = false,
    includePrivacyInfo: boolean = false,
    memberLimit: number = 0,
    memberOffset: number = 0,
    messageLimit: number = 100,
    messageOffset: number = 0,
    newestFirst: boolean = true
  ): Promise<MoodleConversation> {
    const response = await this.callWebService<MoodleConversation>(
      'core_message_get_conversation_between_users',
      {
        userid: userId,
        otheruserid: otherUserId,
        includecontactrequests: includeContactRequests,
        includeprivacyinfo: includePrivacyInfo,
        memberlimit: memberLimit,
        memberoffset: memberOffset,
        messagelimit: messageLimit,
        messageoffset: messageOffset,
        newestmessagesfirst: newestFirst
      }
    );
    return response.data;
  }

  /**
   * Get conversation messages
   * Uses: core_message_get_conversation_messages
   */
  async getConversationMessages(
    currentUserId: number,
    conversationId: number,
    limitFrom: number = 0,
    limitNum: number = 20,
    newestFirst: boolean = true,
    timeFrom: number = 0
  ): Promise<{ id: number; members: unknown[]; messages: ConversationMessage[] }> {
    const response = await this.callWebService<{ id: number; members: unknown[]; messages: ConversationMessage[] }>(
      'core_message_get_conversation_messages',
      {
        currentuserid: currentUserId,
        convid: conversationId,
        limitfrom: limitFrom,
        limitnum: limitNum,
        newest: newestFirst,
        timefrom: timeFrom
      }
    );
    return response.data;
  }

  /**
   * Send instant messages
   * Uses: core_message_send_instant_messages
   */
  async sendInstantMessages(messages: SendMessageParams[]): Promise<SendMessageResponse[]> {
    const response = await this.callWebService<SendMessageResponse[]>(
      'core_message_send_instant_messages',
      { messages }
    );
    return response.data;
  }

  /**
   * Send a single message
   */
  async sendMessage(toUserId: number, text: string): Promise<SendMessageResponse> {
    const result = await this.sendInstantMessages([{ touserid: toUserId, text }]);
    return result[0];
  }

  /**
   * Send messages to a conversation
   * Uses: core_message_send_messages_to_conversation
   */
  async sendToConversation(
    conversationId: number,
    messages: Array<{ text: string; textformat?: number }>
  ): Promise<SendMessageResponse[]> {
    const response = await this.callWebService<SendMessageResponse[]>(
      'core_message_send_messages_to_conversation',
      { conversationid: conversationId, messages }
    );
    return response.data;
  }

  /**
   * Mark message as read
   * Uses: core_message_mark_message_read
   */
  async markMessageRead(messageId: number, timeRead?: number): Promise<{ messageid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ messageid: number; warnings?: unknown[] }>(
      'core_message_mark_message_read',
      { messageid: messageId, timeread: timeRead }
    );
    return response.data;
  }

  /**
   * Mark all conversation messages as read
   * Uses: core_message_mark_all_conversation_messages_as_read
   */
  async markAllConversationMessagesAsRead(userId: number, conversationId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_mark_all_conversation_messages_as_read',
      { userid: userId, conversationid: conversationId }
    );
    return response.data;
  }

  /**
   * Delete a message
   * Uses: core_message_delete_message
   */
  async deleteMessage(messageId: number, userId: number, read?: boolean): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'core_message_delete_message',
      { messageid: messageId, userid: userId, read }
    );
    return response.data;
  }

  /**
   * Delete conversations by ID
   * Uses: core_message_delete_conversations_by_id
   */
  async deleteConversations(userId: number, conversationIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_delete_conversations_by_id',
      { userid: userId, conversationids: conversationIds }
    );
    return response.data;
  }

  /**
   * Get popup notifications
   * Uses: message_popup_get_popup_notifications
   */
  async getPopupNotifications(
    userId: number,
    newestFirst: boolean = true,
    limit: number = 20,
    offset: number = 0
  ): Promise<PopupNotificationsResponse> {
    const response = await this.callWebService<PopupNotificationsResponse>(
      'message_popup_get_popup_notifications',
      { useridto: userId, newestfirst: newestFirst, limit, offset }
    );
    return response.data;
  }

  /**
   * Get unread popup notification count
   * Uses: message_popup_get_unread_popup_notification_count
   */
  async getUnreadPopupNotificationCount(userId: number): Promise<number> {
    const response = await this.callWebService<number>(
      'message_popup_get_unread_popup_notification_count',
      { useridto: userId }
    );
    return response.data;
  }

  /**
   * Mark notification as read
   * Uses: core_message_mark_notification_read
   */
  async markNotificationRead(notificationId: number, timeRead?: number): Promise<{ notificationid: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ notificationid: number; warnings?: unknown[] }>(
      'core_message_mark_notification_read',
      { notificationid: notificationId, timeread: timeRead }
    );
    return response.data;
  }

  /**
   * Mark all notifications as read
   * Uses: core_message_mark_all_notifications_as_read
   */
  async markAllNotificationsAsRead(userId: number, toUserId?: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_mark_all_notifications_as_read',
      { useridto: userId, useridfrom: toUserId }
    );
    return response.data;
  }

  /**
   * Get unread notification count
   * Uses: core_message_get_unread_notification_count
   */
  async getUnreadNotificationCount(userId: number): Promise<number> {
    const response = await this.callWebService<number>(
      'core_message_get_unread_notification_count',
      { useridto: userId }
    );
    return response.data;
  }

  /**
   * Get conversation counts
   * Uses: core_message_get_conversation_counts
   */
  async getConversationCounts(userId: number): Promise<ConversationCountsResponse> {
    const response = await this.callWebService<ConversationCountsResponse>(
      'core_message_get_conversation_counts',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * Get unread conversation counts
   * Uses: core_message_get_unread_conversation_counts
   */
  async getUnreadConversationCounts(userId: number): Promise<ConversationCountsResponse> {
    const response = await this.callWebService<ConversationCountsResponse>(
      'core_message_get_unread_conversation_counts',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * Block a user
   * Uses: core_message_block_user
   */
  async blockUser(userId: number, blockedUserId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_block_user',
      { userid: userId, blockeduserid: blockedUserId }
    );
    return response.data;
  }

  /**
   * Unblock a user
   * Uses: core_message_unblock_user
   */
  async unblockUser(userId: number, unblockedUserId: number): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_unblock_user',
      { userid: userId, unblockeduserid: unblockedUserId }
    );
    return response.data;
  }

  /**
   * Get blocked users
   * Uses: core_message_get_blocked_users
   */
  async getBlockedUsers(userId: number): Promise<{ users: BlockedUser[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ users: BlockedUser[]; warnings?: unknown[] }>(
      'core_message_get_blocked_users',
      { userid: userId }
    );
    return response.data;
  }

  /**
   * Set conversations as favourites
   * Uses: core_message_set_favourite_conversations
   */
  async setFavouriteConversations(userId: number, conversationIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_set_favourite_conversations',
      { userid: userId, conversations: conversationIds }
    );
    return response.data;
  }

  /**
   * Unset conversations as favourites
   * Uses: core_message_unset_favourite_conversations
   */
  async unsetFavouriteConversations(userId: number, conversationIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_unset_favourite_conversations',
      { userid: userId, conversations: conversationIds }
    );
    return response.data;
  }

  /**
   * Mute conversations
   * Uses: core_message_mute_conversations
   */
  async muteConversations(userId: number, conversationIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_mute_conversations',
      { userid: userId, conversationids: conversationIds }
    );
    return response.data;
  }

  /**
   * Unmute conversations
   * Uses: core_message_unmute_conversations
   */
  async unmuteConversations(userId: number, conversationIds: number[]): Promise<null> {
    const response = await this.callWebService<null>(
      'core_message_unmute_conversations',
      { userid: userId, conversationids: conversationIds }
    );
    return response.data;
  }
}

// Singleton instance
export const messageService = new MessageService();

export default messageService;
