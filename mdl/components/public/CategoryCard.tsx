'use client';

import React from 'react';
import Link from 'next/link';

export interface CategoryCardProps {
  name: string;
  icon: string;
  count: number;
  color?: 'maroon' | 'blue' | 'green' | 'amber' | 'purple' | 'indigo';
}

const colorClasses = {
  maroon: 'bg-maroon-50 text-maroon-700 border-maroon-200',
  blue: 'bg-blue-50 text-blue-700 border-blue-200',
  green: 'bg-green-50 text-green-700 border-green-200',
  amber: 'bg-amber-50 text-amber-700 border-amber-200',
  purple: 'bg-purple-50 text-purple-700 border-purple-200',
  indigo: 'bg-indigo-50 text-indigo-700 border-indigo-200',
};

const iconBgClasses = {
  maroon: 'bg-maroon-100 text-maroon-700',
  blue: 'bg-blue-100 text-blue-700',
  green: 'bg-green-100 text-green-700',
  amber: 'bg-amber-100 text-amber-700',
  purple: 'bg-purple-100 text-purple-700',
  indigo: 'bg-indigo-100 text-indigo-700',
};

export const CategoryCard: React.FC<CategoryCardProps> = ({
  name,
  icon,
  count,
  color = 'maroon',
}) => {
  return (
    <Link href={`/catalog?category=${encodeURIComponent(name)}`}>
      <div className={`group p-6 rounded-xl border-2 transition-all duration-200 hover:shadow-md ${colorClasses[color]}`}>
        <div className={`w-14 h-14 rounded-xl flex items-center justify-center text-3xl mb-4 mx-auto ${iconBgClasses[color]}`}>
          {icon}
        </div>
        <h3 className="text-center font-semibold text-gray-900 mb-1 group-hover:text-maroon-700 transition-colors">
          {name}
        </h3>
        <p className="text-center text-sm text-gray-500">{count} หลักสูตร</p>
      </div>
    </Link>
  );
};

export default CategoryCard;
