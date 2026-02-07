// Tool Mobile Service
// Moodle API functions: tool_mobile_*

import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface MobileConfig {
  name: string;
  value: string;
}

export interface MobilePlugin {
  component: string;
  version: string;
  addon: string;
  dependencies: string[];
  fileurl: string;
  filehash: string;
  filesize: number;
  handlers: string;
  lang: string;
}

export interface PublicConfig {
  wwwroot: string;
  httpswwwroot: string;
  sitename: string;
  guestlogin: number;
  rememberusername: number;
  authloginviaemail: number;
  registerauth: string;
  forgottenpasswordurl: string;
  authinstructions: string;
  authnoneenabled: number;
  enablewebservices: number;
  enablemobilewebservice: number;
  maintenanceenabled: number;
  maintenancemessage: string;
  logourl: string;
  compactlogourl: string;
  typeoflogin: number;
  launchurl: string;
  mobilecssurl: string;
  tool_mobile_disabledfeatures: string;
  identityproviders: unknown[];
  country: string;
  agedigitalconsentverification: boolean;
  supportname: string;
  supportemail: string;
  supportpage: string;
  supportavailability: number;
  autolang: number;
  lang: string;
  langmenu: number;
  langlist: string;
  locale: string;
  warnings?: unknown[];
}

export class ToolMobileService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  /**
   * Get public config
   * Uses: tool_mobile_get_public_config
   */
  async getPublicConfig(): Promise<PublicConfig> {
    const response = await this.client.callFunction<PublicConfig>(
      'tool_mobile_get_public_config',
      {}
    );
    return response;
  }

  /**
   * Get autologin key
   * Uses: tool_mobile_get_autologin_key
   */
  async getAutologinKey(privateToken: string): Promise<{ key: string; autologinurl: string; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ key: string; autologinurl: string; warnings?: unknown[] }>(
      'tool_mobile_get_autologin_key',
      { privatetoken: privateToken }
    );
    return response;
  }

  /**
   * Get config
   * Uses: tool_mobile_get_config
   */
  async getConfig(section?: string): Promise<{ settings: MobileConfig[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ settings: MobileConfig[]; warnings?: unknown[] }>(
      'tool_mobile_get_config',
      { section }
    );
    return response;
  }

  /**
   * Get plugins supporting mobile
   * Uses: tool_mobile_get_plugins_supporting_mobile
   */
  async getPluginsSupportingMobile(): Promise<{ plugins: MobilePlugin[]; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ plugins: MobilePlugin[]; warnings?: unknown[] }>(
      'tool_mobile_get_plugins_supporting_mobile',
      {}
    );
    return response;
  }

  /**
   * Get content
   * Uses: tool_mobile_get_content
   */
  async getContent(component: string, method: string, args?: Array<{ name: string; value: string }>): Promise<{ templates: unknown[]; javascript: string; otherdata: unknown[]; files: unknown[]; restrict: unknown; disabled: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      templates: unknown[];
      javascript: string;
      otherdata: unknown[];
      files: unknown[];
      restrict: unknown;
      disabled: boolean;
      warnings?: unknown[];
    }>('tool_mobile_get_content', { component, method, args });
    return response;
  }

  /**
   * Call external functions
   * Uses: tool_mobile_call_external_functions
   */
  async callExternalFunctions(requests: Array<{ function: string; arguments?: string; settingraw?: boolean; settingfileurl?: boolean; settingfilter?: boolean; settinglang?: string }>): Promise<{ responses: Array<{ error: boolean; data?: string; exception?: string }>; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{
      responses: Array<{ error: boolean; data?: string; exception?: string }>;
      warnings?: unknown[];
    }>('tool_mobile_call_external_functions', { requests });
    return response;
  }

  /**
   * Validate subscription key
   * Uses: tool_mobile_validate_subscription_key
   */
  async validateSubscriptionKey(key: string): Promise<{ validated: boolean; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ validated: boolean; warnings?: unknown[] }>(
      'tool_mobile_validate_subscription_key',
      { key }
    );
    return response;
  }

  /**
   * Get tokens for QR login
   * Uses: tool_mobile_get_tokens_for_qr_login
   */
  async getTokensForQrLogin(qrLoginKey: string, userId: number): Promise<{ token: string; privatetoken: string; warnings?: unknown[] }> {
    const response = await this.client.callFunction<{ token: string; privatetoken: string; warnings?: unknown[] }>(
      'tool_mobile_get_tokens_for_qr_login',
      { qrloginkey: qrLoginKey, userid: userId }
    );
    return response;
  }
}

// Singleton instance
export const toolMobileService = new ToolMobileService();

export default toolMobileService;
