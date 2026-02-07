'use client';

import React from 'react';
import Link from 'next/link';
import { BookOpen, Clock, BarChart3, ArrowRight } from 'lucide-react';

export interface LearningPathCardProps {
  id: string;
  title: string;
  description: string;
  courses: number;
  duration: string;
  level: string;
  color?: 'maroon' | 'blue' | 'green';
}

const colorClasses = {
  maroon: {
    bg: 'bg-maroon-50',
    icon: 'bg-maroon-100 text-maroon-700',
    button: 'bg-maroon-700 hover:bg-maroon-800',
  },
  blue: {
    bg: 'bg-blue-50',
    icon: 'bg-blue-100 text-blue-700',
    button: 'bg-blue-600 hover:bg-blue-700',
  },
  green: {
    bg: 'bg-green-50',
    icon: 'bg-green-100 text-green-700',
    button: 'bg-green-600 hover:bg-green-700',
  },
};

export const LearningPathCard: React.FC<LearningPathCardProps> = ({
  id,
  title,
  description,
  courses,
  duration,
  level,
  color = 'maroon',
}) => {
  const colors = colorClasses[color];

  return (
    <div className={`group ${colors.bg} rounded-xl p-6 hover:shadow-lg transition-all duration-300`}>
      {/* Icon */}
      <div className={`w-12 h-12 rounded-xl flex items-center justify-center mb-4 ${colors.icon}`}>
        <BookOpen className="w-6 h-6" />
      </div>

      {/* Title */}
      <h3 className="text-lg font-semibold text-gray-900 mb-2 group-hover:text-maroon-700 transition-colors">
        {title}
      </h3>

      {/* Description */}
      <p className="text-sm text-gray-600 mb-4 line-clamp-2">{description}</p>

      {/* Stats */}
      <div className="space-y-2 mb-5">
        <div className="flex items-center justify-between text-sm">
          <span className="text-gray-500 flex items-center gap-2">
            <BookOpen className="w-4 h-4" />
            จำนวนหลักสูตร
          </span>
          <span className="font-medium text-gray-900">{courses} หลักสูตร</span>
        </div>
        <div className="flex items-center justify-between text-sm">
          <span className="text-gray-500 flex items-center gap-2">
            <Clock className="w-4 h-4" />
            ระยะเวลา
          </span>
          <span className="font-medium text-gray-900">{duration}</span>
        </div>
        <div className="flex items-center justify-between text-sm">
          <span className="text-gray-500 flex items-center gap-2">
            <BarChart3 className="w-4 h-4" />
            ระดับ
          </span>
          <span className="font-medium text-gray-900">{level}</span>
        </div>
      </div>

      {/* Action */}
      <Link
        href={`/learning-paths/${id}`}
        className={`flex items-center justify-center gap-2 w-full px-4 py-2.5 text-white text-sm font-medium rounded-lg transition-colors ${colors.button}`}
      >
        เริ่มเรียน
        <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
      </Link>
    </div>
  );
};

export default LearningPathCard;
