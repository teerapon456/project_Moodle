'use client';

import React from 'react';

interface ProfileInfoProps {
  email: string;
  phone?: string;
  dateOfBirth?: string;
  gender?: string;
  address?: string;
  emergencyContact?: {
    name: string;
    phone: string;
    relationship: string;
  };
  joinDate?: string;
  employeeType?: string;
  onEdit?: () => void;
}

export default function ProfileInfo({
  email,
  phone,
  dateOfBirth,
  gender,
  address,
  emergencyContact,
  joinDate,
  employeeType,
  onEdit,
}: ProfileInfoProps) {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">ข้อมูลส่วนตัว</h2>
        {onEdit && (
          <button
            onClick={onEdit}
            className="flex items-center gap-2 px-4 py-2 bg-[#A21D21] text-white rounded-lg hover:bg-[#8A1919] transition-colors font-semibold text-sm"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            แก้ไขข้อมูล
          </button>
        )}
      </div>

      <div className="space-y-4">
        {/* Email */}
        <div className="flex items-start">
          <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
            <svg className="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div className="flex-1">
            <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">อีเมล</div>
            <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{email}</div>
          </div>
        </div>

        {/* Phone */}
        {phone && (
          <div className="flex items-start">
            <div className="w-10 h-10 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
              <svg className="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <div className="flex-1">
              <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">เบอร์โทรศัพท์</div>
              <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{phone}</div>
            </div>
          </div>
        )}

        {/* Date of Birth & Gender */}
        <div className="grid grid-cols-2 gap-4">
          {dateOfBirth && (
            <div className="flex items-start">
              <div className="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                <svg className="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div className="flex-1">
                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">วันเกิด</div>
                <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{dateOfBirth}</div>
              </div>
            </div>
          )}

          {gender && (
            <div className="flex items-start">
              <div className="w-10 h-10 bg-pink-100 dark:bg-pink-900/40 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                <svg className="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div className="flex-1">
                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">เพศ</div>
                <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{gender}</div>
              </div>
            </div>
          )}
        </div>

        {/* Address */}
        {address && (
          <div className="flex items-start">
            <div className="w-10 h-10 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
              <svg className="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div className="flex-1">
              <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">ที่อยู่</div>
              <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{address}</div>
            </div>
          </div>
        )}

        {/* Work Info */}
        <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
          <h3 className="text-sm font-bold text-gray-900 dark:text-gray-100 mb-3">ข้อมูลการทำงาน</h3>
          <div className="grid grid-cols-2 gap-4">
            {joinDate && (
              <div>
                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">วันที่เริ่มงาน</div>
                <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{joinDate}</div>
              </div>
            )}
            {employeeType && (
              <div>
                <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">ประเภทพนักงาน</div>
                <div className="text-sm font-semibold text-gray-900 dark:text-gray-100">{employeeType}</div>
              </div>
            )}
          </div>
        </div>

        {/* Emergency Contact */}
        {emergencyContact && (
          <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
            <h3 className="text-sm font-bold text-gray-900 dark:text-gray-100 mb-3">ผู้ติดต่อฉุกเฉิน</h3>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-xs text-gray-500 dark:text-gray-400">ชื่อ:</span>
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">{emergencyContact.name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-xs text-gray-500 dark:text-gray-400">เบอร์โทร:</span>
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">{emergencyContact.phone}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-xs text-gray-500 dark:text-gray-400">ความสัมพันธ์:</span>
                <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">{emergencyContact.relationship}</span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
