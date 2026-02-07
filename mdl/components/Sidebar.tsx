'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

type UserRole = 'admin' | 'hr-training' | 'course-creator' | 'instructor' | 'learner';

interface NavItem {
  name: string;
  href?: string;
  icon: React.ReactNode;
  children?: Array<{ name: string; href: string }>;
}

interface SidebarProps {
  userRole?: UserRole;
}

const Sidebar: React.FC<SidebarProps> = ({ userRole = 'learner' }) => {
  const pathname = usePathname() || '';

  // Check if instructor is in a course learning page
  const isInstructorInCourse = userRole === 'instructor' && pathname.startsWith('/instructor/course/');

  // Use learner navigation when instructor is in a course
  const effectiveRole = isInstructorInCourse ? 'learner' : userRole;

  const isActive = (path: string) => {
    // Exact match for the path
    if (pathname === path) return true;

    // For sub-routes, check if pathname starts with path + '/'
    // But exclude if there's a more specific match
    if (pathname.startsWith(path + '/')) {
      // Special handling for learner role - check all learner paths
      if (effectiveRole === 'learner') {
        const learnerPaths = [
          '/learner/my-courses',
          '/learner/progress',
          '/learner/reports',
          '/learner/profile',
          '/learner/notifications',
          '/learner/discussions',
          '/learner/roadmap',
          '/learner/achievements',
          '/learner/assignments'
        ];

        // If current path matches any specific learner path, only highlight that exact path
        for (const learnerPath of learnerPaths) {
          if (pathname.startsWith(learnerPath)) {
            return path === learnerPath;
          }
        }

        // If we're on /learner itself (dashboard), only highlight /learner
        return path === '/learner';
      }

      // For other roles, use existing logic
      const dashboardPath = getDashboardPath(effectiveRole);
      const myCoursesPath = getMyCoursesPath(effectiveRole);

      if (path === dashboardPath && pathname.startsWith(myCoursesPath)) {
        return false;
      }

      return true;
    }

    return false;
  };

  const getDashboardPath = (role: UserRole): string => {
    const rolePathMap: Record<UserRole, string> = {
      'admin': '/admin',
      'hr-training': '/hr-training',
      'course-creator': '/course-creator',
      'instructor': '/instructor',
      'learner': '/learner',
    };
    return rolePathMap[role] || '/learner';
  };

  const getMyCoursesPath = (role: UserRole): string => {
    const rolePathMap: Record<UserRole, string> = {
      'admin': '/admin/my-courses',
      'hr-training': '/hr-training/my-courses',
      'course-creator': '/course-creator/my-courses',
      'instructor': '/instructor/my-courses',
      'learner': '/learner/my-courses',
    };
    return rolePathMap[role] || '/learner/my-courses';
  };

  const baseNavItems: NavItem[] = [
    {
      name: 'แดชบอร์ด',
      href: getDashboardPath(userRole),
      icon: (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
      ),
    },
    {
      name: 'หลักสูตรทั้งหมด',
      href: '/learner/catalog',
      icon: (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
      ),
    },
    {
      name: 'หลักสูตรของฉัน',
      href: getMyCoursesPath(userRole),
      icon: (
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      ),
    },
  ];

  const roleSpecificItems: Record<UserRole, Array<NavItem>> = {
    admin: [
      { name: 'จัดการผู้ใช้', href: '/admin/users', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg> },
      { name: 'จัดการหลักสูตร', href: '/admin/courses', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg> },
      { name: 'Analytics', href: '/admin/analytics', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg> },
      { name: 'รายงาน', href: '/admin/reports', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> },
    ],
    'hr-training': [
      { name: 'รายงานการฝึกอบรม', href: '/hr-training/reports', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg> },
      { name: 'จัดการหลักสูตร', href: '/hr-training/courses', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg> },
    ],
    'course-creator': [
      { name: 'หลักสูตรที่สร้าง', href: '/course-creator/courses', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg> },
      { name: 'สร้างหลักสูตร', href: '/course-creator/create', icon: <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg> },
    ],
    instructor: [
      {
        name: 'การจัดการหลักสูตร',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        ),
        children: [
          { name: 'หลักสูตรที่สอน', href: '/instructor/courses' },
          { name: 'สร้างหลักสูตร', href: '/instructor/create-course' },
          { name: 'เนื้อหาหลักสูตร', href: '/instructor/content' },
        ]
      },
      {
        name: 'การจัดการนักเรียน',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        ),
        children: [
          { name: 'รายชื่อนักเรียน', href: '/instructor/students' },
          { name: 'กลุ่มนักเรียน', href: '/instructor/student-groups' },
          { name: 'สถิตินักเรียน', href: '/instructor/student-stats' },
        ]
      },
      {
        name: 'การจัดการงาน',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        ),
        children: [
          { name: 'งานที่ต้องตรวจ', href: '/instructor/assignments' },
          { name: 'สร้างงาน', href: '/instructor/create-assignment' },
          { name: 'ประเมินงาน', href: '/instructor/grading' },
          { name: 'แบบทดสอบ', href: '/instructor/quizzes' },
        ]
      },
      {
        name: 'การสอน',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        ),
        children: [
          { name: 'ตารางสอน', href: '/instructor/schedule' },
          { name: 'ห้องเรียน', href: '/instructor/classroom' },
          { name: 'การออนไลน์', href: '/instructor/live-sessions' },
          { name: 'วิดีโอสอน', href: '/instructor/videos' },
        ]
      },
      {
        name: 'รายงานและวิเคราะห์',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        ),
        children: [
          { name: 'สถิติการสอน', href: '/instructor/reports' },
          { name: 'รายงานผล', href: '/instructor/performance' },
          { name: 'วิเคราะห์ข้อมูล', href: '/instructor/analytics' },
          { name: 'สรุปประจำเดือน', href: '/instructor/monthly-report' },
        ]
      },
    ],
    learner: [
      {
        name: 'ความคืบหน้า',
        href: '/learner/progress',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        )
      },
      {
        name: 'งานมอบหมาย',
        href: '/learner/assignments',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2h2.828l-8.414-8.414z" />
          </svg>
        )
      },
      {
        name: 'กระทู้สนทนา',
        href: '/learner/discussions',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
          </svg>
        )
      },
      {
        name: 'แผนการพัฒนา',
        href: '/learner/roadmap',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
          </svg>
        )
      },
      {
        name: 'ความสำเร็จ',
        href: '/learner/achievements',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-4.286-2.143L4 12l5.714-2.143L13 3z" />
          </svg>
        )
      },
      {
        name: 'รายงานผลการเรียน',
        href: '/learner/reports',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v2m0 0a2 2 0 002 2h2a2 2 0 002-2v-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v2" />
          </svg>
        )
      },
    ],
  };

  const navItems = [...baseNavItems, ...(roleSpecificItems[effectiveRole] || [])];

  return (
    <div className="w-64 bg-gradient-to-b from-white to-gray-50/50 dark:from-gray-800 dark:to-gray-900/50 border-r border-gray-200/50 dark:border-gray-700/50 min-h-screen fixed left-0 top-0 shadow-lg backdrop-blur-sm">
      {/* Logo Section */}
      <div className="p-6 border-b border-gray-200/50 dark:border-gray-700/50">
        <div className="flex items-center mb-3">
          <div className="relative">
            <div className="w-12 h-12 bg-gradient-to-br from-[#A21D21] to-[#7A1818] rounded-2xl flex items-center justify-center mr-3 shadow-lg transform hover:scale-105 transition-transform">
              <svg className="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              <div className="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
            </div>
          </div>
          <div>
            <h2 className="text-xl font-bold bg-gradient-to-r from-[#A21D21] to-[#7A1818] bg-clip-text text-transparent">
              E-Learning
            </h2>
            <p className="text-xs text-gray-500 dark:text-gray-400 font-medium">ระบบพัฒนาบุคลากร</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="mt-4 px-3 pb-6">
        <div className="space-y-1">
          {navItems.map((item) => {
            if (item.children) {
              // Render dropdown menu for items with children
              return (
                <div key={item.name} className="mb-2">
                  <button
                    className={`group flex items-center px-4 py-3 text-sm font-medium transition-all rounded-xl relative overflow-hidden w-full text-left ${'text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md'
                      }`}
                  >
                    <div className={`mr-3 p-1.5 rounded-lg transition-all ${'text-gray-500 group-hover:bg-[#A21D21]/10 group-hover:text-[#A21D21]'
                      }`}>
                      {item.icon}
                    </div>
                    <span className="relative z-10">{item.name}</span>
                    <svg className="w-4 h-4 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <div className="ml-4 mt-1 space-y-1">
                    {item.children.map((child: { name: string; href: string }) => {
                      const active = isActive(child.href);
                      return (
                        <Link
                          key={child.href}
                          href={child.href}
                          className={`block px-4 py-2 text-sm rounded-lg transition-all ${active
                            ? 'bg-[#A21D21]/10 text-[#A21D21] font-medium'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                            }`}
                        >
                          {child.name}
                        </Link>
                      );
                    })}
                  </div>
                </div>
              );
            } else {
              // Render regular menu item
              const active = isActive(item.href || '');
              return (
                <Link
                  key={item.href || ''}
                  href={item.href || ''}
                  className={`group flex items-center px-4 py-3 text-sm font-medium transition-all rounded-xl relative overflow-hidden ${active
                    ? 'bg-gradient-to-r from-[#A21D21] to-[#7A1818] text-white shadow-lg shadow-[#A21D21]/20'
                    : 'text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md'
                    }`}
                >
                  {active && (
                    <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
                  )}
                  <div className={`mr-3 p-1.5 rounded-lg transition-all ${active
                    ? 'bg-white/20 text-white'
                    : 'text-gray-500 group-hover:bg-[#A21D21]/10 group-hover:text-[#A21D21]'
                    }`}>
                    {item.icon}
                  </div>
                  <span className="relative z-10">{item.name}</span>
                  {active && (
                    <div className="ml-auto">
                      <div className="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                    </div>
                  )}
                </Link>
              );
            }
          })}
        </div>

        {/* Divider */}
        <div className="my-4 px-4">
          <div className="h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-600 to-transparent"></div>
        </div>

        {/* Profile & Notifications */}
        <div className="space-y-1">
          <Link
            href="/learner/profile"
            className={`group flex items-center px-4 py-3 text-sm font-medium transition-all rounded-xl relative overflow-hidden ${(effectiveRole === 'learner' && isActive('/learner/profile')) || (effectiveRole === 'instructor' && isActive('/instructor/profile'))
              ? 'bg-gradient-to-r from-[#A21D21] to-[#7A1818] text-white shadow-lg shadow-[#A21D21]/20'
              : 'text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md'
              }`}
          >
            {(effectiveRole === 'learner' && isActive('/learner/profile')) || (effectiveRole === 'instructor' && isActive('/instructor/profile')) && (
              <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
            )}
            <div className={`mr-3 p-1.5 rounded-lg transition-all ${(effectiveRole === 'learner' && isActive('/learner/profile')) || (effectiveRole === 'instructor' && isActive('/instructor/profile'))
              ? 'bg-white/20 text-white'
              : 'text-gray-500 group-hover:bg-[#A21D21]/10 group-hover:text-[#A21D21]'
              }`}>
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 9H6a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-4m-4 0V7a2 2 0 012-2h4a2 2 0 012 2v2M10 9v6" />
              </svg>
            </div>
            <span className="relative z-10">โปรไฟล์</span>
            {(effectiveRole === 'learner' && isActive('/learner/profile')) || (effectiveRole === 'instructor' && isActive('/instructor/profile')) && (
              <div className="ml-auto">
                <div className="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
              </div>
            )}
          </Link>

          <Link
            href="/learner/notifications"
            className={`group flex items-center px-4 py-3 text-sm font-medium transition-all rounded-xl relative overflow-hidden ${(effectiveRole === 'learner' && isActive('/learner/notifications')) || (effectiveRole === 'instructor' && isActive('/instructor/notifications'))
              ? 'bg-gradient-to-r from-[#A21D21] to-[#7A1818] text-white shadow-lg shadow-[#A21D21]/20'
              : 'text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md'
              }`}
          >
            {(effectiveRole === 'learner' && isActive('/learner/notifications')) || (effectiveRole === 'instructor' && isActive('/instructor/notifications')) && (
              <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
            )}
            <div className={`mr-3 p-1.5 rounded-lg transition-all ${(effectiveRole === 'learner' && isActive('/learner/notifications')) || (effectiveRole === 'instructor' && isActive('/instructor/notifications'))
              ? 'bg-white/20 text-white'
              : 'text-gray-500 group-hover:bg-[#A21D21]/10 group-hover:text-[#A21D21]'
              }`}>
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
            <span className="relative z-10">การแจ้งเตือน</span>
            {((effectiveRole === 'learner' && isActive('/learner/notifications')) || (effectiveRole === 'instructor' && isActive('/instructor/notifications'))) && (
              <div className="ml-auto">
                <div className="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
              </div>
            )}
            {/* Notification Badge */}
            <span className="ml-auto w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
          </Link>
        </div>

      </nav>

      {/* Footer */}
      <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200/50 dark:border-gray-700/50 bg-gradient-to-t from-gray-50/80 dark:from-gray-900/80 to-transparent backdrop-blur-sm">
        <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
          <span className="font-medium">Version 2.0</span>
          <div className="flex items-center space-x-1">
            <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            <span>Online</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;
