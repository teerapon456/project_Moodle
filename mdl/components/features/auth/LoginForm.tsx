'use client';

import React, { useState } from 'react';

interface LoginFormProps {
  onSuccess?: (credentials: { username: string; password: string }) => void;
  className?: string;
}

interface LoginFormState {
  username: string;
  password: string;
  remember: boolean;
}

export function LoginForm({ onSuccess, className }: LoginFormProps) {
  const [formData, setFormData] = useState<LoginFormState>({
    username: '',
    password: '',
    remember: false,
  });

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const handleInputChange = (field: keyof LoginFormState) => (
    e: React.ChangeEvent<HTMLInputElement>
  ) => {
    const value = field === 'remember' ? e.target.checked : e.target.value;
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear error when user starts typing
    if (error) {
      setError('');
    }
  };

  const validateForm = (): boolean => {
    if (!formData.username.trim()) {
      setError('กรุณากรอกชื่อผู้ใช้');
      return false;
    }
    
    if (!formData.password) {
      setError('กรุณากรอกรหัสผ่าน');
      return false;
    }
    
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      const { authService } = await import('@/lib/services/auth.service');
      const response = await authService.login({
        username: formData.username,
        password: formData.password
      });

      if (response.success) {
        onSuccess?.({
          username: formData.username,
          password: formData.password
        });
      } else {
        setError(response.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
      }
    } catch (err) {
      console.error('Login error:', err);
      setError('เกิดข้อผิดพลาด กรุณาลองใหม่');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className={`space-y-4 ${className}`}>
      {/* Email Input */}
      <div>
        <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-2">
          ชื่อผู้ใช้
        </label>
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
          </div>
          <input
            id="username"
            type="text"
            value={formData.username}
            onChange={handleInputChange('username')}
            className="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#A21D21] focus:border-transparent transition-all duration-200"
            placeholder="username"
            disabled={isLoading}
            required
          />
        </div>
      </div>

      {/* Password Input */}
      <div>
        <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
          รหัสผ่าน
        </label>
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <input
            id="password"
            type="password"
            value={formData.password}
            onChange={handleInputChange('password')}
            className="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#A21D21] focus:border-transparent transition-all duration-200"
            placeholder="••••••••"
            disabled={isLoading}
            required
          />
        </div>
      </div>

      {/* Remember Me & Forgot Password */}
      <div className="flex items-center justify-between">
        <label className="flex items-center">
          <input
            type="checkbox"
            checked={formData.remember}
            onChange={handleInputChange('remember')}
            disabled={isLoading}
            className="w-4 h-4 text-[#A21D21] bg-white border-gray-300 rounded focus:ring-[#A21D21] focus:ring-2"
          />
          <span className="ml-2 text-sm text-gray-600">จำฉันไว้</span>
        </label>
        <a href="#" className="text-sm text-[#A21D21] hover:text-[#7A1818] transition-colors">
          ลืมรหัสผ่าน?
        </a>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
          {error}
        </div>
      )}

      {/* Submit Button */}
      <button
        type="submit"
        disabled={isLoading}
        className="w-full bg-gradient-to-r from-[#A21D21] to-[#DC2626] text-white rounded-xl px-4 py-3 font-medium hover:from-[#7A1818] hover:to-[#B91C1C] focus:outline-none focus:ring-2 focus:ring-[#A21D21] focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
      >
        {isLoading ? (
          <span className="flex items-center justify-center gap-2">
            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            กำลังเข้าสู่ระบบ...
          </span>
        ) : (
          'เข้าสู่ระบบ'
        )}
      </button>
    </form>
  );
}
