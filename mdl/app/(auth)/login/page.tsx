'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { LoginForm } from '@/components/features/auth/LoginForm';
import { useAuth } from '@/hooks/use-auth';

export default function LoginPage() {
  const router = useRouter();
  const { login } = useAuth();

  const handleLoginSuccess = async (credentials: { username: string; password: string }) => {
    try {
      const response = await login(credentials);
      
      // Redirect to admin page after successful login
      if (response.success) {
        router.push('/admin');
      }
    } catch (error) {
      // Error is handled by the LoginForm component
      console.error('Login redirect error:', error);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50 flex items-center justify-center p-4">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-5">
        <div className="absolute inset-0 bg-gradient-to-r from-[#A21D21] to-[#DC2626]"></div>
      </div>

      {/* Login Container */}
      <div className="relative w-full max-w-md">


        {/* Login Form */}
        <div className="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-8">
        {/* Logo/Brand Section */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#A21D21] to-[#DC2626] rounded-2xl mb-4 shadow-lg">
            <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">ยินดีต้อนรับกลับมา</h1>
          <p className="text-gray-600 text-sm">เข้าสู่ระบบเพื่อเข้าถึงแดชบอร์ดของคุณ</p>
        </div>
          <LoginForm onSuccess={handleLoginSuccess} />

          {/* Sign Up Link */}
          <div className="mt-6 text-center">
            <p className="text-sm text-gray-600">
              ยังไม่มีบัญชี?{' '}
              <Link href="#" className="text-[#A21D21] hover:text-[#7A1818] font-medium transition-colors">
                สมัครสมาชิก
              </Link>
            </p>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-8 text-center">
          <p className="text-xs text-gray-500">
            © 2024 LMS System. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  );
}
