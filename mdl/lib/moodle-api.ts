export interface MoodleApiResponse<T = unknown> {
  data: T;
  error?: string;
  exception?: string;
}

export interface MoodleSiteInfo {
  sitename: string;
  username: string;
  firstname: string;
  lastname: string;
  fullname: string;
  lang: string;
  userid: number;
  siteurl: string;
  userpictureurl: string;
  functions: Array<{ name: string; version: string }>;
  downloadfiles: number;
  uploadfiles: number;
  release: string;
  version: string;
  mobilecssurl: string;
  advancedfeatures: Array<{ name: string; value: number }>;
  usercanmanageownfiles: boolean;
  userquota: number;
  usermaxuploadfilesize: number;
  userhomepage: number;
  userprivateaccesskey: string;
  siteid: number;
  sitecalendartype: string;
  usercalendartype: string;
  userissiteadmin: boolean;
  theme: string;
  limitconcurrentlogins: number;
  usersessionscount: number;
  policyagreed: number;
}

export class MoodleApiClient {
  private baseUrl: string;
  private token: string;
  private service: string;

  constructor(baseUrl: string, token: string, service: string = 'moodle_mobile_app') {
    // Use environment variable for service name if available
    const serviceName = process.env.NEXT_PUBLIC_MOODLE_SERVICE_NAME || service;
    this.baseUrl = baseUrl;
    this.token = token;
    this.service = serviceName;
  }

  async callFunction<T>(functionName: string, params: Record<string, unknown> = {}): Promise<T> {
    const url = new URL(`${this.baseUrl}/webservice/rest/server.php`);
    
    const requestParams = {
      wstoken: this.token,
      wsfunction: functionName,
      moodlewsrestformat: 'json',
      ...params
    };

    Object.entries(requestParams).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        url.searchParams.append(key, String(value));
      }
    });

    try {
      const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      
      // Check for Moodle API errors
      if (data.exception) {
        throw new Error(`Moodle API Error: ${data.message || data.exception}`);
      }
      
      return data;
    } catch (error) {
      console.error(`Error calling Moodle function ${functionName}:`, error);
      throw error;
    }
  }

  async callFunctionWithResponse<T>(functionName: string, params: Record<string, unknown> = {}): Promise<{ data: T }> {
    const result = await this.callFunction<T>(functionName, params);
    return { data: result };
  }
}

export default MoodleApiClient;
