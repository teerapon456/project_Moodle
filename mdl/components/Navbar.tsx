'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import NotificationBell from './NotificationBell';

interface NavbarProps {
  userName?: string;
  userRole?: string;
}

const Navbar: React.FC<NavbarProps> = ({ userName = 'ผู้ใช้', userRole = 'learner' }) => {
  const [showDropdown, setShowDropdown] = useState(false);
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('darkMode') === 'true';
    }
    return false;
  });
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    if (darkMode) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }, [darkMode]);

  useEffect(() => {
    const handleScroll = () => {
      setScrolled(window.scrollY > 20);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const toggleDarkMode = () => {
    const newDarkMode = !darkMode;
    setDarkMode(newDarkMode);
    localStorage.setItem('darkMode', String(newDarkMode));
    if (newDarkMode) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  return (
    <nav className={`transition-all duration-300 ease-in-out px-8 py-4 fixed top-0 right-0 left-64 z-50 shadow-sm ${scrolled
      ? 'bg-white/95 dark:bg-gray-900/95 backdrop-blur-lg border-b border-gray-200/60 dark:border-gray-700/60 shadow-md'
      : 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200/50 dark:border-gray-700/50'
      }`}>
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <div className="flex items-center space-x-3">
            <div className="h-10 w-1.5 bg-gradient-to-b from-[#A21D21] to-[#7A1818] rounded-full"></div>
            <h1 className="text-2xl font-bold bg-gradient-to-r from-[#A21D21] to-[#7A1818] bg-clip-text text-transparent">
              ระบบ E-Learning
            </h1>
          </div>
        </div>

        <div className="flex items-center space-x-4">
          {/* Search */}
          <div className="hidden md:flex items-center bg-gray-50 dark:bg-gray-800 rounded-xl px-5 py-3 border border-gray-200 dark:border-gray-700 hover:border-[#A21D21]/30 transition-all">
            <svg className="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              placeholder="ค้นหาหลักสูตร..."
              className="bg-transparent text-base outline-none w-52 text-gray-700 dark:text-gray-200 placeholder-gray-400"
            />
          </div>

          {/* Theme Toggle */}
          <button
            onClick={toggleDarkMode}
            className="relative p-3 text-gray-600 dark:text-gray-300 hover:text-[#A21D21] hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-all group"
            title={darkMode ? 'สลับเป็นโหมดสว่าง' : 'สลับเป็นโหมดมืด'}
          >
            {darkMode ? (
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            ) : (
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            )}
          </button>

          {/* Notifications */}
          <NotificationBell />

          {/* User Menu */}
          <div className="relative">
            <button
              onClick={() => setShowDropdown(!showDropdown)}
              className="flex items-center space-x-3 px-4 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-all border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
            >
              <div className="w-11 h-11 rounded-xl bg-gradient-to-br from-[#A21D21] to-[#7A1818] flex items-center justify-center text-white font-bold text-lg shadow-sm">
                {userName.charAt(0)}
              </div>
              <div className="text-left hidden md:block">
                <p className="text-base font-semibold text-gray-900 dark:text-gray-100">{userName}</p>
                <p className="text-sm text-gray-500 dark:text-gray-400">{userRole === 'learner' ? 'ผู้เรียน' : userRole === 'instructor' ? 'วิทยากร' : userRole === 'course-creator' ? 'ผู้สร้างหลักสูตร' : userRole === 'admin' ? 'ผู้ดูแลระบบ' : userRole}</p>
              </div>
              <svg className="w-5 h-5 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            {showDropdown && (
              <div className="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl py-2 z-[60] border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div className="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800">
                  <p className="text-base font-semibold text-gray-900 dark:text-gray-100">{userName}</p>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{userRole === 'learner' ? 'ผู้เรียน' : userRole === 'instructor' ? 'วิทยากร' : userRole === 'course-creator' ? 'ผู้สร้างหลักสูตร' : userRole === 'admin' ? 'ผู้ดูแลระบบ' : userRole}</p>
                </div>
                <Link
                  href="/learner/profile"
                  className="flex items-center px-5 py-3.5 text-base text-gray-700 dark:text-gray-200 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-700 transition-all group"
                  onClick={() => setShowDropdown(false)}
                >
                  <div className="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 group-hover:bg-[#A21D21]/10 transition-colors">
                    <svg className="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-[#A21D21]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <span className="font-medium">โปรไฟล์</span>
                </Link>
                <Link
                  href="/learner/notifications"
                  className="flex items-center px-5 py-3.5 text-base text-gray-700 dark:text-gray-200 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-700 transition-all group"
                  onClick={() => setShowDropdown(false)}
                >
                  <div className="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 group-hover:bg-[#A21D21]/10 transition-colors">
                    <svg className="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-[#A21D21]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                  </div>
                  <span className="font-medium">การแจ้งเตือน</span>
                </Link>
                <Link
                  href="/learner/achievements"
                  className="flex items-center px-5 py-3.5 text-base text-gray-700 dark:text-gray-200 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-700 transition-all group"
                  onClick={() => setShowDropdown(false)}
                >
                  <div className="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 group-hover:bg-[#A21D21]/10 transition-colors">
                    <svg className="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-[#A21D21]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v13m0-13C6.477 5 2 9.477 2 15c0 1.746.477 3.332 1.253 4.5m18.494 0C22.523 18.332 23 16.746 23 15c0-5.523-4.477-10-10-10" />
                    </svg>
                  </div>
                  <span className="font-medium">ความสำเร็จ</span>
                </Link>
                <Link
                  href="/settings"
                  className="flex items-center px-5 py-3.5 text-base text-gray-700 dark:text-gray-200 hover:bg-gradient-to-r hover:from-gray-50 hover:to-white dark:hover:from-gray-700 dark:hover:to-gray-700 transition-all group"
                  onClick={() => setShowDropdown(false)}
                >
                  <div className="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 group-hover:bg-[#A21D21]/10 transition-colors">
                    <svg className="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-[#A21D21]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <span className="font-medium">การตั้งค่า</span>
                </Link>
                <hr className="my-2 border-gray-100 dark:border-gray-700" />
                <Link
                  href="/login"
                  className="flex items-center px-5 py-3.5 text-base text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all group"
                  onClick={() => setShowDropdown(false)}
                >
                  <div className="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center mr-3 group-hover:bg-red-100 dark:group-hover:bg-red-900/30 transition-colors">
                    <svg className="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                  </div>
                  <span className="font-medium">ออกจากระบบ</span>
                </Link>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
