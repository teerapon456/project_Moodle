'use client';

import React from 'react';
import Sidebar from './Sidebar';
import Navbar from './Navbar';

type UserRole = 'admin' | 'hr-training' | 'course-creator' | 'instructor' | 'learner';

interface MainLayoutProps {
  children: React.ReactNode;
  userName?: string;
  userRole?: UserRole;
}

const MainLayout: React.FC<MainLayoutProps> = ({ 
  children, 
  userName = 'สมชาย ใจดี', 
  userRole = 'learner' 
}) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
      <Sidebar userRole={userRole} />
      <div className="ml-64">
        <Navbar userName={userName} userRole={userRole} />
        <main className="pt-24 px-8 pb-8">
          {children}
        </main>
      </div>
    </div>
  );
};

export default MainLayout;
