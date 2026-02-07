// LTI (External Tool) Service
// Moodle API functions: mod_lti_*
import MoodleApiClient from '@/lib/moodle-api';

const MOODLE_URL = process.env.NEXT_PUBLIC_MOODLE_URL || 'http://localhost:8080';
const MOODLE_ADMIN_TOKEN = process.env.MOODLE_ADMIN_TOKEN || process.env.NEXT_PUBLIC_MOODLE_ADMIN_TOKEN || '';

export interface Lti {
  id: number;
  course: number;
  name: string;
  intro: string;
  introformat: number;
  introfiles: unknown[];
  timecreated: number;
  timemodified: number;
  typeid: number;
  toolurl: string;
  securetoolurl: string;
  instructorchoicesendname: boolean;
  instructorchoicesendemailaddr: boolean;
  instructorchoiceallowroster: boolean;
  instructorchoiceallowsetting: boolean;
  instructorcustomparameters: string;
  instructorchoiceacceptgrades: number;
  grade: number;
  launchcontainer: number;
  resourcekey: string;
  password: string;
  debuglaunch: boolean;
  showtitlelaunch: boolean;
  showdescriptionlaunch: boolean;
  servicesalt: string;
  icon: string;
  secureicon: string;
  section: number;
  visible: boolean;
  groupmode: number;
  groupingid: number;
  coursemodule: number;
}

export interface LtiToolType {
  id: number;
  name: string;
  description: string;
  platformid: string;
  clientid: string;
  deploymentid: number;
  urls: {
    icon: string;
    publickeyset: string;
    accesstoken: string;
    authrequest: string;
  };
  state: {
    text: string;
    pending: boolean;
    configured: boolean;
    rejected: boolean;
    unknown: boolean;
  };
  hascapabilitygroups: boolean;
  capabilitygroups: unknown[];
  courseid: number;
  instanceids: number[];
  instancecount: number;
}

export interface LtiToolProxy {
  id: number;
  name: string;
  regurl: string;
  state: number;
  guid: string;
  secret: string;
  vendorcode: string;
  capabilityoffered: string;
  serviceoffered: string;
  toolproxy: string;
  createdby: number;
  timecreated: number;
  timemodified: number;
}

export class LtiService {
  private client: MoodleApiClient;

  constructor(token: string = MOODLE_ADMIN_TOKEN) {
    this.client = new MoodleApiClient(MOODLE_URL, token);
  }

  private async callWebService<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.client.callFunction<T>(functionName, params);
    return { data: result };
  }

  /**
   * Get LTIs by courses
   * Uses: mod_lti_get_ltis_by_courses
   */
  async getLtisByCourses(courseIds?: number[]): Promise<{ ltis: Lti[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ ltis: Lti[]; warnings?: unknown[] }>(
      'mod_lti_get_ltis_by_courses',
      { courseids: courseIds }
    );
    return response.data;
  }

  /**
   * View LTI (log event)
   * Uses: mod_lti_view_lti
   */
  async viewLti(ltiId: number): Promise<{ status: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ status: boolean; warnings?: unknown[] }>(
      'mod_lti_view_lti',
      { ltiid: ltiId }
    );
    return response.data;
  }

  /**
   * Get tool launch data
   * Uses: mod_lti_get_tool_launch_data
   */
  async getToolLaunchData(toolId: number): Promise<{ endpoint: string; parameters: Array<{ name: string; value: string }>; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      endpoint: string;
      parameters: Array<{ name: string; value: string }>;
      warnings?: unknown[];
    }>('mod_lti_get_tool_launch_data', { toolid: toolId });
    return response.data;
  }

  /**
   * Get tool types
   * Uses: mod_lti_get_tool_types
   */
  async getToolTypes(courseId?: number, toolProxyId?: number): Promise<{ types: LtiToolType[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{ types: LtiToolType[]; warnings?: unknown[] }>(
      'mod_lti_get_tool_types',
      { courseid: courseId, toolproxyid: toolProxyId }
    );
    return response.data;
  }

  /**
   * Create tool type
   * Uses: mod_lti_create_tool_type
   */
  async createToolType(cartridgeUrl?: string, key?: string, secret?: string): Promise<{ id: number; name: string; urls: unknown; state: unknown; hascapabilitygroups: boolean; capabilitygroups: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      id: number;
      name: string;
      urls: unknown;
      state: unknown;
      hascapabilitygroups: boolean;
      capabilitygroups: unknown[];
      warnings?: unknown[];
    }>('mod_lti_create_tool_type', { cartridgeurl: cartridgeUrl, key, secret });
    return response.data;
  }

  /**
   * Update tool type
   * Uses: mod_lti_update_tool_type
   */
  async updateToolType(id: number, name?: string, description?: string, state?: number): Promise<{ id: number; name: string; description: string; urls: unknown; state: unknown; hascapabilitygroups: boolean; capabilitygroups: unknown[]; warnings?: unknown[] }> {
    const response = await this.callWebService<{
      id: number;
      name: string;
      description: string;
      urls: unknown;
      state: unknown;
      hascapabilitygroups: boolean;
      capabilitygroups: unknown[];
      warnings?: unknown[];
    }>('mod_lti_update_tool_type', { id, name, description, state });
    return response.data;
  }

  /**
   * Delete tool type
   * Uses: mod_lti_delete_tool_type
   */
  async deleteToolType(id: number): Promise<{ id: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ id: number; warnings?: unknown[] }>(
      'mod_lti_delete_tool_type',
      { id }
    );
    return response.data;
  }

  /**
   * Get tool proxies
   * Uses: mod_lti_get_tool_proxies
   */
  async getToolProxies(orphanedOnly?: boolean): Promise<LtiToolProxy[]> {
    const response = await this.callWebService<LtiToolProxy[]>(
      'mod_lti_get_tool_proxies',
      { orphanedonly: orphanedOnly }
    );
    return response.data;
  }

  /**
   * Create tool proxy
   * Uses: mod_lti_create_tool_proxy
   */
  async createToolProxy(name: string, regUrl: string, capabilityOffered: string[], serviceOffered: string[]): Promise<{ id: number; name: string; regurl: string; state: number; guid: string; secret: string; capabilityoffered: string; serviceoffered: string }> {
    const response = await this.callWebService<{
      id: number;
      name: string;
      regurl: string;
      state: number;
      guid: string;
      secret: string;
      capabilityoffered: string;
      serviceoffered: string;
    }>('mod_lti_create_tool_proxy', {
      name,
      regurl: regUrl,
      capabilityoffered: capabilityOffered,
      serviceoffered: serviceOffered
    });
    return response.data;
  }

  /**
   * Delete tool proxy
   * Uses: mod_lti_delete_tool_proxy
   */
  async deleteToolProxy(id: number): Promise<{ id: number; warnings?: unknown[] }> {
    const response = await this.callWebService<{ id: number; warnings?: unknown[] }>(
      'mod_lti_delete_tool_proxy',
      { id }
    );
    return response.data;
  }

  /**
   * Is cartridge
   * Uses: mod_lti_is_cartridge
   */
  async isCartridge(url: string): Promise<{ iscartridge: boolean; warnings?: unknown[] }> {
    const response = await this.callWebService<{ iscartridge: boolean; warnings?: unknown[] }>(
      'mod_lti_is_cartridge',
      { url }
    );
    return response.data;
  }
}

// Singleton instance
export const ltiService = new LtiService();

export default ltiService;
