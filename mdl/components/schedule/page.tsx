'use client';

import React, { useState } from 'react';
import MainLayout from '@/components/MainLayout';
import Calendar from '@/components/Calendar';
import EventList from '@/components/EventList';

// Mock events data
const generateMockEvents = () => {
  const today = new Date();
  const events = [
    // Today
    {
      id: 1,
      title: 'Six Sigma Introduction',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
      startTime: '14:00',
      endTime: '16:00',
      course: 'Six Sigma Green Belt',
      instructor: 'ดร.สมชาย ใจดี',
      location: 'Zoom Meeting Room 1',
      description: 'บทนำสู่ Six Sigma และหลักการพื้นฐาน DMAIC',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    {
      id: 2,
      title: 'Quiz: Safety Module 3',
      type: 'quiz' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
      startTime: '23:59',
      course: 'มาตรฐานความปลอดภัยในโรงงาน',
      description: 'แบบทดสอบความเข้าใจในบทที่ 3',
      color: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-l-4 border-green-500',
    },
    // Tomorrow
    {
      id: 3,
      title: 'Digital Transformation Trends 2026',
      type: 'webinar' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1),
      startTime: '10:00',
      endTime: '11:30',
      course: 'Digital Transformation & Innovation',
      instructor: 'คุณวิไล เทคโนโลยี',
      location: 'Microsoft Teams',
      description: 'เทรนด์การเปลี่ยนแปลงทางดิจิทัลในปี 2026',
      color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-l-4 border-blue-500',
    },
    // Day after tomorrow
    {
      id: 4,
      title: 'Assignment: ERP Analysis',
      type: 'assignment' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 2),
      startTime: '23:59',
      course: 'การใช้งาน ERP System',
      description: 'งานวิเคราะห์กระบวนการ ERP ในองค์กร',
      color: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 border-l-4 border-purple-500',
    },
    // +3 days
    {
      id: 5,
      title: 'Data Visualization Workshop',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 3),
      startTime: '13:00',
      endTime: '17:00',
      course: 'Data Analytics for Business',
      instructor: 'ดร.ประเสริฐ ดาต้า',
      location: 'Training Room A',
      description: 'Workshop การสร้าง Dashboard และ Visualization ด้วย Power BI',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    // +5 days
    {
      id: 6,
      title: 'Leadership Assessment Deadline',
      type: 'deadline' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 5),
      startTime: '17:00',
      course: 'Leadership & Team Management',
      description: 'กำหนดส่งแบบประเมินความเป็นผู้นำ',
      color: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border-l-4 border-orange-500',
    },
    // +7 days
    {
      id: 7,
      title: 'Communication Skills Practice',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 7),
      startTime: '09:00',
      endTime: '12:00',
      course: 'Effective Communication Skills',
      instructor: 'อ.สมหญิง รักงาน',
      location: 'Conference Room B',
      description: 'ฝึกปฏิบัติทักษะการสื่อสารและการนำเสนอ',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    // +10 days
    {
      id: 8,
      title: 'Agile Methodology Webinar',
      type: 'webinar' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 10),
      startTime: '14:00',
      endTime: '15:30',
      course: 'Agile & Scrum Fundamentals',
      instructor: 'คุณชัยวัฒน์ อไจล์',
      location: 'Google Meet',
      description: 'แนะนำ Agile Methodology และการประยุกต์ใช้',
      color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-l-4 border-blue-500',
    },
    // +12 days
    {
      id: 9,
      title: 'Final Project Submission',
      type: 'deadline' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 12),
      startTime: '23:59',
      course: 'Project Management Professional',
      description: 'ส่งโปรเจกต์ปลายภาค',
      color: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border-l-4 border-orange-500',
    },
    // +14 days
    {
      id: 10,
      title: 'Quality Control Final Exam',
      type: 'quiz' as const,
      date: new Date(today.getFullYear(), today.getMonth(), today.getDate() + 14),
      startTime: '10:00',
      endTime: '12:00',
      course: 'การควบคุมคุณภาพผลิตภัณฑ์',
      description: 'สอบปลายภาคการควบคุมคุณภาพ',
      color: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-l-4 border-green-500',
    },
    // Next month events
    {
      id: 11,
      title: 'Advanced Excel Workshop',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 1, 5),
      startTime: '13:00',
      endTime: '16:00',
      course: 'Data Analytics for Business',
      instructor: 'ดร.ประเสริฐ ดาต้า',
      location: 'Computer Lab 1',
      description: 'Workshop Excel ขั้นสูงสำหรับการวิเคราะห์ข้อมูล',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    {
      id: 12,
      title: 'Cybersecurity Awareness Training',
      type: 'webinar' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 1, 10),
      startTime: '15:00',
      endTime: '16:00',
      course: 'Cybersecurity Awareness',
      instructor: 'คุณเทพ ไซเบอร์',
      location: 'Zoom Meeting',
      description: 'การรับมือกับภัยคุกคามทางไซเบอร์',
      color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-l-4 border-blue-500',
    },
    // Month +1 additional events
    {
      id: 13,
      title: 'Strategic Planning Workshop',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 1, 15),
      startTime: '09:00',
      endTime: '17:00',
      course: 'Leadership & Team Management',
      instructor: 'ดร.พัฒนา องค์กร',
      location: 'Conference Hall',
      description: 'Workshop การวางแผนกลยุทธ์องค์กร',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    {
      id: 14,
      title: 'Quiz: Digital Marketing',
      type: 'quiz' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 1, 20),
      startTime: '14:00',
      endTime: '15:00',
      course: 'Digital Marketing Fundamentals',
      description: 'แบบทดสอบการตลาดดิจิทัล',
      color: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-l-4 border-green-500',
    },
    // Month +2 events
    {
      id: 15,
      title: 'Innovation & Creativity Workshop',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 2, 3),
      startTime: '10:00',
      endTime: '16:00',
      course: 'Innovation Management',
      instructor: 'คุณสร้างสรรค์ นวัตกรรม',
      location: 'Innovation Lab',
      description: 'Workshop การคิดสร้างสรรค์และนวัตกรรม',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    {
      id: 16,
      title: 'Supply Chain Management Webinar',
      type: 'webinar' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 2, 8),
      startTime: '13:00',
      endTime: '14:30',
      course: 'Supply Chain Optimization',
      instructor: 'คุณลอจิส ติกส์',
      location: 'Zoom Meeting',
      description: 'การบริหารห่วงโซ่อุปทานอย่างมีประสิทธิภาพ',
      color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-l-4 border-blue-500',
    },
    {
      id: 17,
      title: 'Assignment: Business Case Study',
      type: 'assignment' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 2, 15),
      startTime: '23:59',
      course: 'Strategic Management',
      description: 'วิเคราะห์กรณีศึกษาทางธุรกิจ',
      color: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 border-l-4 border-purple-500',
    },
    // Month +3 events
    {
      id: 18,
      title: 'HR Analytics Workshop',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 3, 5),
      startTime: '09:00',
      endTime: '12:00',
      course: 'HR Analytics & Metrics',
      instructor: 'คุณบุคคล ทรัพยากร',
      location: 'HR Training Room',
      description: 'การวิเคราะห์ข้อมูลทรัพยากรบุคคล',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
    {
      id: 19,
      title: 'Financial Planning Deadline',
      type: 'deadline' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 3, 10),
      startTime: '17:00',
      course: 'Financial Management',
      description: 'ส่งแผนการเงินประจำปี',
      color: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border-l-4 border-orange-500',
    },
    {
      id: 20,
      title: 'Customer Service Excellence Quiz',
      type: 'quiz' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 3, 20),
      startTime: '10:00',
      endTime: '11:00',
      course: 'Customer Service Excellence',
      description: 'แบบทดสอบการบริการลูกค้า',
      color: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-l-4 border-green-500',
    },
    // Month +4 events
    {
      id: 21,
      title: 'Change Management Seminar',
      type: 'webinar' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 4, 7),
      startTime: '14:00',
      endTime: '16:00',
      course: 'Change Management',
      instructor: 'ดร.พัฒนา องค์กร',
      location: 'Microsoft Teams',
      description: 'การบริหารการเปลี่ยนแปลงในองค์กร',
      color: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-l-4 border-blue-500',
    },
    {
      id: 22,
      title: 'Team Building Activity',
      type: 'live-session' as const,
      date: new Date(today.getFullYear(), today.getMonth() + 4, 15),
      startTime: '08:00',
      endTime: '17:00',
      course: 'Team Development',
      instructor: 'คุณทีม เวิร์ค',
      location: 'Outdoor Training Center',
      description: 'กิจกรรมสร้างทีมและพัฒนาความสัมพันธ์',
      color: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-l-4 border-red-500',
    },
  ];

  return events;
};

export default function SchedulePage() {
  const [selectedEvent, setSelectedEvent] = useState<any>(null);
  const [filterType, setFilterType] = useState<string>('all');
  const [currentPage, setCurrentPage] = useState(1);
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set(['today', 'tomorrow', 'thisWeek']));
  const itemsPerPage = 7; // แสดง 7 กลุ่มต่อหน้า

  const allEvents = generateMockEvents();

  // Filter events
  const filteredEvents = filterType === 'all'
    ? allEvents
    : allEvents.filter(event => event.type === filterType);

  // Get upcoming events (next 7 days)
  const today = new Date();
  const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
  const upcomingEvents = allEvents
    .filter(event => event.date >= today && event.date <= nextWeek)
    .sort((a, b) => a.date.getTime() - b.date.getTime());

  const handleEventClick = (event: any) => {
    setSelectedEvent(event);
  };

  // Group events by date
  const groupEventsByDate = (events: any[]) => {
    const sorted = [...events].sort((a, b) => a.date.getTime() - b.date.getTime());
    const groups: { [key: string]: { label: string; events: any[] } } = {};

    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);

    const tomorrowDate = new Date(todayDate);
    tomorrowDate.setDate(todayDate.getDate() + 1);

    const thisWeekEnd = new Date(todayDate);
    thisWeekEnd.setDate(todayDate.getDate() + 7);

    const thisMonthEnd = new Date(todayDate.getFullYear(), todayDate.getMonth() + 1, 0);

    sorted.forEach(event => {
      const eventDate = new Date(event.date);
      eventDate.setHours(0, 0, 0, 0);

      let groupKey = '';
      let groupLabel = '';

      if (eventDate.getTime() === todayDate.getTime()) {
        groupKey = 'today';
        groupLabel = '📅 วันนี้';
      } else if (eventDate.getTime() === tomorrowDate.getTime()) {
        groupKey = 'tomorrow';
        groupLabel = '📅 พรุ่งนี้';
      } else if (eventDate > tomorrowDate && eventDate <= thisWeekEnd) {
        groupKey = 'thisWeek';
        groupLabel = '📅 สัปดาห์นี้';
      } else if (eventDate > thisWeekEnd && eventDate <= thisMonthEnd) {
        groupKey = 'thisMonth';
        groupLabel = '📅 เดือนนี้';
      } else if (eventDate > thisMonthEnd) {
        const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
          'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        groupKey = `${eventDate.getFullYear()}-${eventDate.getMonth()}`;
        groupLabel = `📅 ${monthNames[eventDate.getMonth()]} ${eventDate.getFullYear() + 543}`;
      } else {
        const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
          'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        groupKey = `past-${eventDate.getFullYear()}-${eventDate.getMonth()}`;
        groupLabel = `📅 ${monthNames[eventDate.getMonth()]} ${eventDate.getFullYear() + 543} (ย้อนหลัง)`;
      }

      if (!groups[groupKey]) {
        groups[groupKey] = { label: groupLabel, events: [] };
      }
      groups[groupKey].events.push(event);
    });

    return groups;
  };

  const groupedEvents = groupEventsByDate(filteredEvents);
  const groupKeys = Object.keys(groupedEvents);

  // Pagination
  const totalPages = Math.ceil(groupKeys.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentGroups = groupKeys.slice(startIndex, endIndex);

  const toggleGroup = (groupKey: string) => {
    const newExpanded = new Set(expandedGroups);
    if (newExpanded.has(groupKey)) {
      newExpanded.delete(groupKey);
    } else {
      newExpanded.add(groupKey);
    }
    setExpandedGroups(newExpanded);
  };

  const expandAll = () => {
    setExpandedGroups(new Set(groupKeys));
  };

  const collapseAll = () => {
    setExpandedGroups(new Set());
  };

  return (
    <MainLayout userName="สมชาย ใจดี" userRole="learner">
      <div className="mb-4 pt-4">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <svg className="w-8 h-8 text-[#A21D21] dark:text-[#C92828]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          ตารางเรียนและกิจกรรม
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-1">
          จัดการตารางเรียน กิจกรรม และกำหนดส่งงานของคุณ
        </p>
      </div>

      {/* Filter Buttons */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setFilterType('all')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'all'
              ? 'bg-[#A21D21] text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            ทั้งหมด ({allEvents.length})
          </button>
          <button
            onClick={() => setFilterType('live-session')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'live-session'
              ? 'bg-red-500 text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            Live Session ({allEvents.filter(e => e.type === 'live-session').length})
          </button>
          <button
            onClick={() => setFilterType('webinar')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'webinar'
              ? 'bg-blue-500 text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            Webinar ({allEvents.filter(e => e.type === 'webinar').length})
          </button>
          <button
            onClick={() => setFilterType('deadline')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'deadline'
              ? 'bg-orange-500 text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            Deadline ({allEvents.filter(e => e.type === 'deadline').length})
          </button>
          <button
            onClick={() => setFilterType('assignment')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'assignment'
              ? 'bg-purple-500 text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            Assignment ({allEvents.filter(e => e.type === 'assignment').length})
          </button>
          <button
            onClick={() => setFilterType('quiz')}
            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${filterType === 'quiz'
              ? 'bg-green-500 text-white shadow-md'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
          >
            Quiz ({allEvents.filter(e => e.type === 'quiz').length})
          </button>
        </div>
      </div>

      {/* Calendar and Events Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        {/* Calendar - Takes 2 columns */}
        <div className="lg:col-span-2">
          <Calendar events={filteredEvents} onEventClick={handleEventClick} />
        </div>

        {/* Upcoming Events - Takes 1 column */}
        <div className="lg:col-span-1">
          <EventList events={upcomingEvents} title="กิจกรรมที่กำลังจะมาถึง (7 วัน)" />
        </div>
      </div>

      {/* Grouped Events List with Pagination */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
            <svg className="w-6 h-6 mr-2 text-[#A21D21] dark:text-[#C92828]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {filterType === 'all' ? 'กิจกรรมทั้งหมด' : `กิจกรรมประเภท ${filterType}`}
            <span className="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
              ({filteredEvents.length} รายการ)
            </span>
          </h2>
          <div className="flex gap-2">
            <button
              onClick={expandAll}
              className="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              เปิดทั้งหมด
            </button>
            <button
              onClick={collapseAll}
              className="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              ปิดทั้งหมด
            </button>
          </div>
        </div>

        {filteredEvents.length === 0 ? (
          <div className="text-center py-12">
            <svg className="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p className="text-gray-500 dark:text-gray-400">ไม่มีกิจกรรมในขณะนี้</p>
          </div>
        ) : (
          <>
            {/* Grouped Events */}
            <div className="space-y-4">
              {currentGroups.map(groupKey => {
                const group = groupedEvents[groupKey];
                const isExpanded = expandedGroups.has(groupKey);

                return (
                  <div key={groupKey} className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    {/* Group Header */}
                    <button
                      onClick={() => toggleGroup(groupKey)}
                      className="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <svg
                          className={`w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100">
                          {group.label}
                        </h3>
                        <span className="px-2 py-1 bg-[#A21D21]/10 dark:bg-[#A21D21]/20 text-[#A21D21] dark:text-[#C92828] rounded-full text-xs font-semibold">
                          {group.events.length} กิจกรรม
                        </span>
                      </div>
                    </button>

                    {/* Group Content */}
                    {isExpanded && (
                      <div className="p-4 space-y-3 bg-white dark:bg-gray-800">
                        {group.events.map((event: any) => {
                          const getEventIcon = (type: string) => {
                            switch (type) {
                              case 'live-session':
                                return (
                                  <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                  </svg>
                                );
                              case 'webinar':
                                return (
                                  <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                  </svg>
                                );
                              case 'deadline':
                                return (
                                  <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                  </svg>
                                );
                              case 'assignment':
                                return (
                                  <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                  </svg>
                                );
                              case 'quiz':
                                return (
                                  <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                  </svg>
                                );
                              default:
                                return null;
                            }
                          };

                          const getEventColor = (type: string) => {
                            switch (type) {
                              case 'live-session': return 'bg-red-500';
                              case 'webinar': return 'bg-blue-500';
                              case 'deadline': return 'bg-orange-500';
                              case 'assignment': return 'bg-purple-500';
                              case 'quiz': return 'bg-green-500';
                              default: return 'bg-gray-500';
                            }
                          };

                          const getEventBorderColor = (type: string) => {
                            switch (type) {
                              case 'live-session': return 'border-red-300 dark:border-red-700 hover:border-red-400 dark:hover:border-red-600';
                              case 'webinar': return 'border-blue-300 dark:border-blue-700 hover:border-blue-400 dark:hover:border-blue-600';
                              case 'deadline': return 'border-orange-300 dark:border-orange-700 hover:border-orange-400 dark:hover:border-orange-600';
                              case 'assignment': return 'border-purple-300 dark:border-purple-700 hover:border-purple-400 dark:hover:border-purple-600';
                              case 'quiz': return 'border-green-300 dark:border-green-700 hover:border-green-400 dark:hover:border-green-600';
                              default: return 'border-gray-300 dark:border-gray-700';
                            }
                          };

                          const getEventTypeName = (type: string) => {
                            switch (type) {
                              case 'live-session': return 'Live Session';
                              case 'webinar': return 'Webinar';
                              case 'deadline': return 'Deadline';
                              case 'assignment': return 'Assignment';
                              case 'quiz': return 'Quiz';
                              default: return type;
                            }
                          };

                          const formatDate = (date: Date) => {
                            const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                            const day = date.getDate();
                            const monthName = thaiMonths[date.getMonth()];
                            const year = date.getFullYear() + 543;
                            return `${day} ${monthName} ${year}`;
                          };

                          return (
                            <div
                              key={event.id}
                              className={`p-4 rounded-lg border-2 transition-all cursor-pointer hover:shadow-lg ${getEventBorderColor(event.type)}`}
                            >
                              <div className="flex items-start gap-4">
                                <div className={`w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 ${getEventColor(event.type)}`}>
                                  {getEventIcon(event.type)}
                                </div>
                                <div className="flex-1 min-w-0">
                                  <div className="flex items-start justify-between gap-2 mb-2">
                                    <div className="flex-1">
                                      <h3 className="font-bold text-gray-900 dark:text-gray-100 mb-1">
                                        {event.title}
                                      </h3>
                                      {event.course && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                          {event.course}
                                        </p>
                                      )}
                                    </div>
                                    <span className={`px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${event.type === 'live-session' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' :
                                      event.type === 'webinar' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' :
                                        event.type === 'deadline' ? 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300' :
                                          event.type === 'assignment' ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' :
                                            'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                      }`}>
                                      {getEventTypeName(event.type)}
                                    </span>
                                  </div>

                                  <div className="space-y-1">
                                    <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                      <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                      </svg>
                                      {formatDate(event.date)}
                                    </div>
                                    {event.startTime && (
                                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {event.startTime}
                                        {event.endTime && ` - ${event.endTime}`}
                                      </div>
                                    )}
                                    {event.instructor && (
                                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {event.instructor}
                                      </div>
                                    )}
                                    {event.location && (
                                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {event.location}
                                      </div>
                                    )}
                                  </div>

                                  {event.description && (
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                      {event.description}
                                    </p>
                                  )}
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex items-center justify-between mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  แสดงกลุ่มที่ {startIndex + 1}-{Math.min(endIndex, groupKeys.length)} จาก {groupKeys.length} กลุ่ม
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                    disabled={currentPage === 1}
                    className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${currentPage === 1
                      ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                      }`}
                  >
                    ← ก่อนหน้า
                  </button>

                  <div className="flex items-center gap-1">
                    {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
                      <button
                        key={page}
                        onClick={() => setCurrentPage(page)}
                        className={`w-10 h-10 rounded-lg font-semibold text-sm transition-all ${currentPage === page
                          ? 'bg-[#A21D21] text-white shadow-md'
                          : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                          }`}
                      >
                        {page}
                      </button>
                    ))}
                  </div>

                  <button
                    onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                    disabled={currentPage === totalPages}
                    className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all ${currentPage === totalPages
                      ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-600 cursor-not-allowed'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                      }`}
                  >
                    ถัดไป →
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </MainLayout>
  );
}
