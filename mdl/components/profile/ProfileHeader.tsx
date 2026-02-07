'use client';

import React from 'react';
import Image from 'next/image';

interface ProfileHeaderProps {
  name: string;
  email: string;
  role: string;
  employeeId: string;
  department: string;
  position: string;
  avatar?: string;
  coverImage?: string;
}

export default function ProfileHeader({
  name,
  email,
  role,
  employeeId,
  department,
  position,
  avatar,
  coverImage,
}: ProfileHeaderProps) {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      {/* Cover Image */}
      <div className="relative h-48 bg-gradient-to-r from-[#A21D21] to-[#7A1818]">
        {coverImage && (
          <img src={coverImage} alt="Cover" className="w-full h-full object-cover" />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
      </div>

      {/* Profile Info */}
      <div className="relative px-6 pb-6">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between -mt-16 md:-mt-20">
          {/* Avatar */}
          <div className="flex flex-col md:flex-row md:items-end gap-4">
            <div className="relative">
              <div className="w-32 h-32 rounded-2xl border-4 border-white dark:border-gray-800 shadow-xl overflow-hidden bg-gray-200 dark:bg-gray-700">
                {avatar ? (
                  <img src={avatar} alt={name || 'User'} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#A21D21] to-[#7A1818]">
                    <span className="text-4xl font-bold text-white">
                      {name?.charAt(0) || 'U'}
                    </span>
                  </div>
                )}
              </div>
              <div className="absolute -bottom-2 -right-2 w-10 h-10 bg-green-500 rounded-full border-4 border-white dark:border-gray-800 flex items-center justify-center">
                <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>

            <div className="mb-4">
              <h1 className="text-3xl font-bold text-[#A21D21] dark:text-white mb-1 drop-shadow-sm">{name || 'User Name'}</h1>
              <p className="text-[#A21D21] dark:text-gray-200 font-bold text-base mb-2">{position || 'Position'}</p>
              <div className="flex flex-wrap gap-2">
                <span className="px-3 py-1 rounded-full text-xs font-bold bg-[#A21D21] text-white shadow-sm">
                  {role || 'User'}
                </span>
                <span className="px-3 py-1 rounded-full text-xs font-bold bg-blue-600 text-white shadow-sm">
                  {department || 'Department'}
                </span>
                <span className="px-3 py-1 rounded-full text-xs font-bold bg-gray-700 text-white shadow-sm">
                  รหัส: {employeeId || 'EMP-000'}
                </span>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex gap-3 mt-4 md:mt-0 md:mb-4">
            <button
              onClick={() => alert('แชร์โปรไฟล์')}
              className="flex items-center gap-2 px-5 py-3 bg-[#A21D21] hover:bg-[#8A1919] text-white rounded-lg transition-all font-bold text-sm shadow-md hover:shadow-lg transform hover:-translate-y-0.5 active:translate-y-0"
            >
              <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
              แชร์โปรไฟล์
            </button>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900 dark:text-white">12</div>
            <div className="text-xs font-semibold text-gray-600 dark:text-gray-300">หลักสูตรที่เรียน</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-green-600 dark:text-green-400">5</div>
            <div className="text-xs font-semibold text-gray-600 dark:text-gray-300">เรียนจบแล้ว</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">5</div>
            <div className="text-xs font-semibold text-gray-600 dark:text-gray-300">กำลังเรียน</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-[#A21D21] dark:text-[#C92828]">156</div>
            <div className="text-xs font-semibold text-gray-600 dark:text-gray-300">ชั่วโมงเรียน</div>
          </div>
        </div>
      </div>
    </div>
  );
}
