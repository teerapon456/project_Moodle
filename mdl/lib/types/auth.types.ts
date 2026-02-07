// lib/api/types/auth.types.ts
// ============================================
// Authentication Types
// ============================================

export type UserRole = 'admin' | 'manager' | 'instructor' | 'coursecreator' | 'learner';

export interface User {
  id: number;
  username: string;
  firstname: string;
  lastname: string;
  fullname: string;
  email: string;
  userpictureurl?: string;
  siteid?: number;
  sitename?: string;
  is_site_admin?: boolean;
  role?: UserRole;
  roleAssignments?: string[];
  token?: string;
}

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface LoginResponse {
  success: boolean;
  data?: {
    token: string;
    moodleUserId: number;
    user: User;
  };
  error?: string;
}

export interface MoodleTokenResponse {
  token: string;
  privatetoken?: string;
  error?: string;
  errorcode?: string;
  stacktrace?: string;
  debuginfo?: string;
  reproductionlink?: string;
}

export interface SignupSettings {
  namefields: string[];
  passwordpolicy: string;
  sitepolicy: string;
  sitepolicyhandler: string;
  disabilitychoices: string[];
  recaptchapublickey: string;
  recaptchachallengeimage: string;
  country: string;
  defaultcity: string;
  warnings: unknown[];
}

export interface SignupUserParams {
  username: string;
  password: string;
  firstname: string;
  lastname: string;
  email: string;
  city?: string;
  country?: string;
  recaptchachallengeimage?: string;
  recaptcharesponse?: string;
  customprofilefields?: Array<{ type: string; name: string; value: string }>;
  redirect?: string;
}

export interface ConfirmUserParams {
  username: string;
  secret: string;
}

export interface ConfirmUserResponse {
  success: boolean;
  warnings?: unknown[];
}

export interface PasswordResetResponse {
  status: string;
  notice: string;
  warnings?: unknown[];
}
