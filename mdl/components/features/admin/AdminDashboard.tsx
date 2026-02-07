'use client';

import React, { useState, useEffect } from 'react';

// Load admin data from database

// Mock data for demonstration
const systemStats = {
  totalUsers: 1247,
  activeUsers: 892,
  newUsers: 45,
  completionRate: 98.5
};

const courseStats = {
  totalCourses: 68,
  activeCourses: 45,
  completedCourses: 890,
  inProgressCourses: 234
};

const recentActivities = [
  {
    id: 1,
    type: 'user_registered',
    title: 'ผู้ใช้ใหม่สมัคร',
    description: 'สมชาย ใจดี ได้สมัครเป็นสมาชิกใหม่',
    time: '5 นาทีที่แล้ว'
  },
  {
    id: 2,
    type: 'course_completed',
    title: 'เสร็จสิ้นหลักสูตร',
    description: 'มานี รักดี ได้เสร็จสิ้นหลักสูตร "การพัฒนาเว็บ"',
    time: '15 นาทีที่แล้ว'
  },
  {
    id: 3,
    type: 'system_update',
    title: 'อัปเดตระบบ',
    description: 'ระบบได้ทำการอัปเดตเวอร์ชัน 2.1.0',
    time: '1 ชั่วโมงที่แล้ว'
  },
  {
    id: 4,
    type: 'course_completed',
    title: 'เสร็จสิ้นหลักสูตร',
    description: 'วิชัย มีชัย ได้เสร็จสิ้นหลักสูตร "การจัดการฐานข้อมูล"',
    time: '2 ชั่วโมงที่แล้ว'
  },
  {
    id: 5,
    type: 'user_registered',
    title: 'ผู้ใช้ใหม่สมัคร',
    description: 'สมศรี มีเมตตา ได้สมัครเป็นสมาชิกใหม่',
    time: '3 ชั่วโมงที่แล้ว'
  }
];

const recentUsers = [
  {
    id: 1,
    name: 'สมชาย ใจดี',
    email: 'somchai@example.com',
    role: 'student',
    status: 'active',
    registeredDate: '05/02/2024'
  },
  {
    id: 2,
    name: 'มานี รักดี',
    email: 'manee@example.com',
    role: 'instructor',
    status: 'active',
    registeredDate: '04/02/2024'
  },
  {
    id: 3,
    name: 'วิชัย มีชัย',
    email: 'vichai@example.com',
    role: 'student',
    status: 'pending',
    registeredDate: '04/02/2024'
  },
  {
    id: 4,
    name: 'สมศรี มีเมตตา',
    email: 'somsri@example.com',
    role: 'student',
    status: 'active',
    registeredDate: '03/02/2024'
  },
  {
    id: 5,
    name: 'ประเสริฐ โชคดี',
    email: 'prasert@example.com',
    role: 'admin',
    status: 'active',
    registeredDate: '03/02/2024'
  },
  {
    id: 6,
    name: 'กิตติศักดิ์ มีศักดิ์',
    email: 'kittisak@example.com',
    role: 'instructor',
    status: 'active',
    registeredDate: '02/02/2024'
  }
];

import { UserService } from '@/lib/services/user.service';
import { authService } from '@/lib/services/auth.service';

export default function AdminDashboard() {
  const [activeTab, setActiveTab] = useState('overview');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedTimeRange, setSelectedTimeRange] = useState('7d');
  const [realUserCount, setRealUserCount] = useState<number | null>(null);

  const [notifications, setNotifications] = useState([
    { id: 1, type: 'warning', message: 'มีผู้ใช้ใหม่ 5 คนรอการอนุมัติ', time: '5 นาทีที่แล้ว' },
    { id: 2, type: 'info', message: 'ระบบสำรองข้อมูลเสร็จสิ้น', time: '1 ชั่วโมงที่แล้ว' },
    { id: 3, type: 'success', message: 'หลักสูตรใหม่ "การจัดการความปลอดภัย" ได้รับการอนุมัติ', time: '2 ชั่วโมงที่แล้ว' },
  ]);

  // Real-time updates simulation & Data Fetching
  useEffect(() => {
    // Fetch real stats
    // Fetch real stats from Portal DB
    const fetchStats = async () => {
      try {
        const response = await fetch('/moodle-app/api/portal/stats');
        if (response.ok) {
          const data = await response.json();
          setRealUserCount(data.totalUsers);
        }
      } catch (error) {
        console.error("Failed to fetch Portal stats:", error);
      }
    };
    fetchStats();

    const interval = setInterval(() => {
      // Simulate real-time data updates
      setNotifications(prev => [
        ...prev.slice(0, 4),
        {
          id: Date.now(),
          type: 'info',
          message: `อัปเดตข้อมูลเมื่อ ${new Date().toLocaleTimeString('th-TH')}`,
          time: 'เมื่อสักครู่'
        }
      ]);
    }, 30000); // Update every 30 seconds

    return () => clearInterval(interval);
  }, []);

  const tabs = [
    { id: 'overview', label: 'ภาพรวม', icon: '📊' },
    { id: 'users', label: 'ผู้ใช้งาน', icon: '👥' },
    { id: 'courses', label: 'หลักสูตร', icon: '📚' },
    { id: 'system', label: 'ระบบ', icon: '⚙️' },
    { id: 'reports', label: 'รายงาน', icon: '📈' },
    { id: 'settings', label: 'ตั้งค่า', icon: '🔧' },
  ];

  const renderOverview = () => (
    <div className="space-y-6">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <span className="text-sm font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">+12%</span>
          </div>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{realUserCount !== null ? realUserCount : systemStats.totalUsers}</h3>
          <p className="text-gray-600 dark:text-gray-400">ผู้ใช้ทั้งหมด (Portal DB)</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <span className="text-sm font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">+8%</span>
          </div>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{courseStats.totalCourses}</h3>
          <p className="text-gray-600 dark:text-gray-400">หลักสูตรทั้งหมด</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span className="text-sm font-medium text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full">+23%</span>
          </div>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{systemStats.activeUsers}</h3>
          <p className="text-gray-600 dark:text-gray-400">ผู้ใช้งานออนไลน์</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
          <div className="flex items-center justify-between mb-4">
            <div className="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <span className="text-sm font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">98.5%</span>
          </div>
          <h3 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{systemStats.completionRate}%</h3>
          <p className="text-gray-600 dark:text-gray-400">อัตราการเสร็จสิ้น</p>
        </div>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* User Growth Chart */}
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">การเติบโตของผู้ใช้</h3>
          <div className="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div className="text-center">
              <svg className="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <p className="text-gray-500">กราฟการเติบโตของผู้ใช้</p>
              <p className="text-sm text-gray-400 mt-1">+12% เพิ่มขึ้นจากเดือนที่แล้ว</p>
            </div>
          </div>
        </div>

        {/* Course Completion Chart */}
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">การเสร็จสิ้นหลักสูตร</h3>
          <div className="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div className="text-center">
              <svg className="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
              </svg>
              <p className="text-gray-500">กราฟการเสร็จสิ้นหลักสูตร</p>
              <p className="text-sm text-gray-400 mt-1">98.5% อัตราการเสร็จสิ้นโดยเฉลี่ย</p>
            </div>
          </div>
        </div>
      </div>

      {/* Recent Activities */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">กิจกรรมล่าสุด</h3>
        <div className="space-y-3">
          {recentActivities.slice(0, 5).map((activity) => (
            <div key={activity.id} className="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
              <div className={`w-10 h-10 rounded-lg flex items-center justify-center mr-3 ${activity.type === 'user_registered' ? 'bg-blue-500' :
                activity.type === 'course_completed' ? 'bg-green-500' :
                  activity.type === 'system_update' ? 'bg-purple-500' :
                    'bg-orange-500'
                }`}>
                <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  {activity.type === 'user_registered' && (
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  )}
                  {activity.type === 'course_completed' && (
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  )}
                  {activity.type === 'system_update' && (
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  )}
                </svg>
              </div>
              <div className="flex-1">
                <p className="font-medium text-gray-900 dark:text-gray-100">{activity.title}</p>
                <p className="text-sm text-gray-500 dark:text-gray-400">{activity.description}</p>
              </div>
              <span className="text-sm text-gray-500">{activity.time}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  interface RecentUser {
    id: number | string;
    name: string;
    email: string;
    role: string;
    status: string;
    registeredDate: string;
  }

  const renderUsers = () => (
    <div className="space-y-6">
      {/* User Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">ผู้ใช้ทั้งหมด</h3>
          <p className="text-3xl font-bold text-blue-600">{systemStats.totalUsers}</p>
          <p className="text-sm text-gray-500">+12% จากเดือนที่แล้ว</p>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">ผู้ใช้งานออนไลน์</h3>
          <p className="text-3xl font-bold text-green-600">{systemStats.activeUsers}</p>
          <p className="text-sm text-gray-500">23 คน กำลังใช้งานอยู่</p>
        </div>
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">ผู้ใช้ใหม่</h3>
          <p className="text-3xl font-bold text-purple-600">{systemStats.newUsers}</p>
          <p className="text-sm text-gray-500">7 วันล่าสุด</p>
        </div>
      </div>

      {/* Recent Users */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">ผู้ใช้ล่าสุด</h3>
          <button className="px-4 py-2 bg-[#A21D21] text-white rounded-lg hover:bg-[#8A1919] transition-colors">
            จัดการผู้ใช้
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200 dark:border-gray-700">
                <th className="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">ผู้ใช้</th>
                <th className="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">บทบาท</th>
                <th className="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">สถานะ</th>
                <th className="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">วันที่สมัคร</th>
                <th className="text-left py-3 px-4 font-medium text-gray-700 dark:text-gray-300">การดำเนินการ</th>
              </tr>
            </thead>
            <tbody>
              {recentUsers.map((user: RecentUser) => (
                <tr key={user.id} className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td className="py-3 px-4">
                    <div className="flex items-center">
                      <div className="w-8 h-8 bg-gray-300 rounded-full mr-3"></div>
                      <div>
                        <p className="font-medium text-gray-900 dark:text-gray-100">{user.name}</p>
                        <p className="text-sm text-gray-500">{user.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${user.role === 'admin' ? 'bg-red-100 text-red-700' :
                      user.role === 'instructor' ? 'bg-blue-100 text-blue-700' :
                        user.role === 'student' ? 'bg-green-100 text-green-700' :
                          'bg-purple-100 text-purple-700'
                      }`}>
                      {user.role === 'admin' ? 'ผู้ดูแลระบบ' :
                        user.role === 'instructor' ? 'อาจารย์' :
                          user.role === 'student' ? 'ผู้เรียน' :
                            'ผู้สร้างหลักสูตร'}
                    </span>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-700' :
                      user.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-red-100 text-red-700'
                      }`}>
                      {user.status === 'active' ? 'ใช้งาน' :
                        user.status === 'pending' ? 'รออนุมัติ' :
                          'ระงับ'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-sm text-gray-500">{user.registeredDate}</td>
                  <td className="py-3 px-4">
                    <button className="text-[#A21D21] hover:text-[#8A1919] font-medium text-sm">
                      จัดการ
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );

  const renderSystem = () => (
    <div className="space-y-6">
      {/* System Health */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">สถานะระบบ</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg className="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h4 className="font-semibold text-gray-900 dark:text-gray-100">Database</h4>
            <p className="text-sm text-green-600">ปกติ</p>
          </div>
          <div className="text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg className="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h4 className="font-semibold text-gray-900 dark:text-gray-100">Server</h4>
            <p className="text-sm text-green-600">ปกติ</p>
          </div>
          <div className="text-center">
            <div className="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg className="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h4 className="font-semibold text-gray-900 dark:text-gray-100">Storage</h4>
            <p className="text-sm text-yellow-600">75% ใช้งาน</p>
          </div>
          <div className="text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg className="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h4 className="font-semibold text-gray-900 dark:text-gray-100">API</h4>
            <p className="text-sm text-green-600">ปกติ</p>
          </div>
        </div>
      </div>

      {/* System Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ประสิทธิภาพระบบ</h3>
          <div className="space-y-4">
            <div>
              <div className="flex justify-between mb-1">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">CPU Usage</span>
                <span className="text-sm text-gray-500">45%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-blue-600 h-2 rounded-full" style={{ width: '45%' }}></div>
              </div>
            </div>
            <div>
              <div className="flex justify-between mb-1">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Memory Usage</span>
                <span className="text-sm text-gray-500">67%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-yellow-600 h-2 rounded-full" style={{ width: '67%' }}></div>
              </div>
            </div>
            <div>
              <div className="flex justify-between mb-1">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Disk Usage</span>
                <span className="text-sm text-gray-500">75%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-orange-600 h-2 rounded-full" style={{ width: '75%' }}></div>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ข้อมูลระบบ</h3>
          <div className="space-y-3">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600 dark:text-gray-400">เวอร์ชันระบบ</span>
              <span className="text-sm font-medium text-gray-900 dark:text-gray-100">v2.1.0</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600 dark:text-gray-400">อัปเดตล่าสุด</span>
              <span className="text-sm font-medium text-gray-900 dark:text-gray-100">2 วันที่แล้ว</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600 dark:text-gray-400">Uptime</span>
              <span className="text-sm font-medium text-gray-900 dark:text-gray-100">99.9%</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600 dark:text-gray-400">Response Time</span>
              <span className="text-sm font-medium text-gray-900 dark:text-gray-100">245ms</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  return (
    <div className="pt-4">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
          แดชบอร์ดผู้ดูแลระบบ
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          จัดการและตรวจสอบระบบ E-Learning ทั้งหมด
        </p>
      </div>

      {/* Notifications */}
      <div className="mb-6">
        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <svg className="w-5 h-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span className="text-blue-800 dark:text-blue-200 font-medium">
                มีการแจ้งเตือน {notifications.length} รายการ
              </span>
            </div>
            <button className="text-blue-600 hover:text-blue-800 font-medium text-sm">
              ดูทั้งหมด
            </button>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav className="flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm transition-colors ${activeTab === tab.id
                ? 'border-[#A21D21] text-[#A21D21]'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
            >
              <span className="mr-2">{tab.icon}</span>
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      <div>
        {activeTab === 'overview' && renderOverview()}
        {activeTab === 'users' && renderUsers()}
        {activeTab === 'system' && renderSystem()}
        {activeTab === 'courses' && (
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">จัดการหลักสูตร</h3>
            <p className="text-gray-600 dark:text-gray-400">ฟีเจอร์นี้กำลังพัฒนา...</p>
          </div>
        )}
        {activeTab === 'reports' && (
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">รายงาน</h3>
            <p className="text-gray-600 dark:text-gray-400">ฟีเจอร์นี้กำลังพัฒนา...</p>
          </div>
        )}
        {activeTab === 'settings' && (
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ตั้งค่าระบบ</h3>
            <p className="text-gray-600 dark:text-gray-400">ฟีเจอร์นี้กำลังพัฒนา...</p>
          </div>
        )}
      </div>
    </div>
  );
}
