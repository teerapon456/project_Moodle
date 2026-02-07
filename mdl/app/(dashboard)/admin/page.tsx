'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import AdminDashboard from '@/components/features/admin/AdminDashboard';
import Sidebar from '@/components/ui/Sidebar';
import Navbar from '@/components/ui/Navbar';
import { useAuth } from '@/hooks/use-auth';

export default function AdminPage() {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  // Redirect to login if not authenticated
  React.useEffect(() => {
    if (!isLoading && (!user || user.role !== 'admin')) {
      router.push('/login');
    }
  }, [user, isLoading, router]);

  // Show loading state while checking authentication
  if (isLoading || !user || user.role !== 'admin') {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-900 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#A21D21] mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-400">กำลังตรวจสอบสิทธิ์...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-900">
      {/* Sidebar */}
      <Sidebar userRole="admin" />

      {/* Main Content */}
      <div className="ml-64">
        {/* Navbar */}
        <Navbar userName={user?.fullname} userRole={user?.role} />
        {/* Dashboard Content */}
        <main className="pt-24 px-8 pb-8">
          <AdminDashboard />
        </main>
      </div>
    </div>
  );
}