'use client';

import React from 'react';
import Link from 'next/link';

interface Certificate {
  id: string;
  courseTitle: string;
  courseCode: string;
  completedDate: string;
  certificateId: string;
  score: number;
  grade: string;
}

interface ProfileCertificatesProps {
  certificates: Certificate[];
  userRole: string;
}

export default function ProfileCertificates({ certificates, userRole }: ProfileCertificatesProps) {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">ใบประกาศนียบัตร</h2>
        <Link
          href={`/${userRole}/my-courses?filter=completed`}
          className="text-sm text-[#A21D21] dark:text-[#C92828] hover:underline font-semibold"
        >
          ดูทั้งหมด →
        </Link>
      </div>

      {certificates.length === 0 ? (
        <div className="text-center py-8">
          <svg className="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          <p className="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีใบประกาศนียบัตร</p>
        </div>
      ) : (
        <div className="space-y-3">
          {certificates.map((cert) => (
            <Link
              key={cert.id}
              href={`/${userRole}/my-courses/${cert.id}/certificate`}
              className="block p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-[#A21D21] dark:hover:border-[#A21D21] hover:shadow-md transition-all group"
            >
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <svg className="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate group-hover:text-[#A21D21] dark:group-hover:text-[#C92828] transition-colors">
                      {cert.courseTitle}
                    </h3>
                  </div>
                  <div className="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mb-2">
                    <span className="font-mono">{cert.courseCode}</span>
                    <span>•</span>
                    <span>{cert.completedDate}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                      คะแนน: {cert.score}
                    </span>
                    <span className="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                      เกรด: {cert.grade}
                    </span>
                  </div>
                </div>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-[#A21D21] dark:group-hover:text-[#C92828] transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
