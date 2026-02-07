'use client';

import React from 'react';
import Link from 'next/link';
import { Star, Clock, Users, ChevronRight } from 'lucide-react';

export interface CourseCardProps {
  id: string;
  title: string;
  description?: string;
  image: string;
  category: string;
  level: string;
  instructor: string;
  rating: number;
  duration: string;
  enrolledCount?: number;
}

export const CourseCard: React.FC<CourseCardProps> = ({
  id,
  title,
  image,
  category,
  level,
  instructor,
  rating,
  duration,
  enrolledCount = 0,
}) => {
  return (
    <Link href={`/courses/${id}`}>
      <div className="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
        {/* Image */}
        <div className="relative aspect-video overflow-hidden">
          <img
            src={image}
            alt={title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          {/* Badges */}
          <div className="absolute top-3 left-3 flex gap-2">
            <span className="px-2.5 py-1 bg-maroon-700 text-white text-xs font-medium rounded-full">
              {category}
            </span>
          </div>
          <div className="absolute top-3 right-3">
            <span className="px-2.5 py-1 bg-white/90 backdrop-blur-sm text-gray-700 text-xs font-medium rounded-full">
              {level}
            </span>
          </div>
        </div>

        {/* Content */}
        <div className="p-4">
          {/* Title */}
          <h3 className="font-semibold text-gray-900 line-clamp-2 group-hover:text-maroon-700 transition-colors mb-2">
            {title}
          </h3>

          {/* Instructor */}
          <p className="text-sm text-gray-500 mb-3">{instructor}</p>

          {/* Stats Row */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-3">
            {/* Rating */}
            <div className="flex items-center gap-1">
              <Star className="w-4 h-4 fill-yellow-400 text-yellow-400" />
              <span className="font-medium text-gray-700">{rating.toFixed(1)}</span>
            </div>
            
            {/* Duration */}
            <div className="flex items-center gap-1">
              <Clock className="w-4 h-4" />
              <span>{duration}</span>
            </div>

            {/* Enrolled */}
            {enrolledCount > 0 && (
              <div className="flex items-center gap-1">
                <Users className="w-4 h-4" />
                <span>{enrolledCount.toLocaleString()}</span>
              </div>
            )}
          </div>

          {/* Action */}
          <div className="flex items-center justify-between pt-3 border-t border-gray-100">
            <span className="text-sm font-medium text-maroon-700 group-hover:underline">
              ดูรายละเอียด
            </span>
            <ChevronRight className="w-4 h-4 text-maroon-700 group-hover:translate-x-1 transition-transform" />
          </div>
        </div>
      </div>
    </Link>
  );
};

export default CourseCard;
