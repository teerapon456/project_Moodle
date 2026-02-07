import { LoginCredentials, LoginResponse, User } from '../types/auth.types';

const API_URL = '/moodle-app/api/auth';
const TOKEN_KEY = 'auth-token'; // Unified token key

export const authService = {
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await fetch(`${API_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(credentials),
    });

    const data = await response.json();

    if (data.success && data.data?.token) {
      localStorage.setItem(TOKEN_KEY, data.data.token);
    }

    return data;
  },

  async logout(): Promise<void> {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem('user-data');
    // Clean up legacy keys
    localStorage.removeItem('moodle_token');
    localStorage.removeItem('moodle-token');
    try {
      await fetch(`${API_URL}/logout`, { method: 'POST' });
    } catch (error) {
      console.error('Logout error:', error);
    }
  },

  async getCurrentUser(): Promise<User | null> {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return null;

    try {
      const response = await fetch(`${API_URL}/me`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (!response.ok) {
        throw new Error('Failed to get user');
      }

      return await response.json();
    } catch (error) {
      console.error('Get user error:', error);
      return null;
    }
  },

  async confirmUser(username: string, secret: string): Promise<boolean> {
    try {
      const response = await fetch(`${API_URL}/confirm`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, secret }),
      });

      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Confirm user error:', error);
      return false;
    }
  },

  getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }
};
