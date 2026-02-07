'use client';

import React, { useState, useEffect, useRef } from 'react';

interface CalendarEvent {
  id: number;
  title: string;
  type: 'live-session' | 'webinar' | 'deadline' | 'assignment' | 'quiz';
  date: Date;
  startTime?: string;
  endTime?: string;
  course?: string;
  instructor?: string;
  color: string;
}

interface CalendarProps {
  events: CalendarEvent[];
  onEventClick?: (event: CalendarEvent) => void;
}

const Calendar: React.FC<CalendarProps> = ({ events, onEventClick }) => {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [view, setView] = useState<'month' | 'week'>('month');
  const [showYearPicker, setShowYearPicker] = useState(false);
  const yearPickerRef = useRef<HTMLDivElement>(null);

  // Close year picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (yearPickerRef.current && !yearPickerRef.current.contains(event.target as Node)) {
        setShowYearPicker(false);
      }
    };

    if (showYearPicker) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showYearPicker]);

  // Get days in month
  const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    return { daysInMonth, startingDayOfWeek, year, month };
  };

  const { daysInMonth, startingDayOfWeek, year, month } = getDaysInMonth(currentDate);

  // Get week dates
  const getWeekDates = (date: Date) => {
    const dayOfWeek = date.getDay();
    const weekStart = new Date(date);
    weekStart.setDate(date.getDate() - dayOfWeek);
    
    const weekDates = [];
    for (let i = 0; i < 7; i++) {
      const day = new Date(weekStart);
      day.setDate(weekStart.getDate() + i);
      weekDates.push(day);
    }
    return weekDates;
  };

  const weekDates = getWeekDates(currentDate);

  // Navigation
  const goToPrevious = () => {
    if (view === 'month') {
      setCurrentDate(new Date(year, month - 1, 1));
    } else {
      const newDate = new Date(currentDate);
      newDate.setDate(currentDate.getDate() - 7);
      setCurrentDate(newDate);
    }
  };

  const goToNext = () => {
    if (view === 'month') {
      setCurrentDate(new Date(year, month + 1, 1));
    } else {
      const newDate = new Date(currentDate);
      newDate.setDate(currentDate.getDate() + 7);
      setCurrentDate(newDate);
    }
  };

  const goToToday = () => {
    setCurrentDate(new Date());
  };

  const changeYear = (newYear: number) => {
    const newDate = new Date(currentDate);
    newDate.setFullYear(newYear);
    setCurrentDate(newDate);
    setShowYearPicker(false);
  };

  // Generate year range (current year ± 10 years)
  const currentYear = new Date().getFullYear();
  const yearRange = Array.from({ length: 21 }, (_, i) => currentYear - 10 + i);

  // Get events for a specific date
  const getEventsForDate = (day: number, checkMonth?: number, checkYear?: number) => {
    return events.filter(event => {
      const eventDate = new Date(event.date);
      return (
        eventDate.getDate() === day &&
        eventDate.getMonth() === (checkMonth ?? month) &&
        eventDate.getFullYear() === (checkYear ?? year)
      );
    });
  };

  // Get events for a specific Date object (for week view)
  const getEventsForDateObj = (date: Date) => {
    return events.filter(event => {
      const eventDate = new Date(event.date);
      return (
        eventDate.getDate() === date.getDate() &&
        eventDate.getMonth() === date.getMonth() &&
        eventDate.getFullYear() === date.getFullYear()
      );
    });
  };

  // Check if date is today
  const isToday = (day: number, checkMonth?: number, checkYear?: number) => {
    const today = new Date();
    return (
      day === today.getDate() &&
      (checkMonth ?? month) === today.getMonth() &&
      (checkYear ?? year) === today.getFullYear()
    );
  };

  // Check if Date object is today
  const isTodayDate = (date: Date) => {
    const today = new Date();
    return (
      date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear()
    );
  };

  // Thai month names
  const thaiMonths = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
  ];

  const thaiDays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

  // Generate calendar days
  const calendarDays = [];
  
  // Empty cells before first day
  for (let i = 0; i < startingDayOfWeek; i++) {
    calendarDays.push(null);
  }
  
  // Days of month
  for (let day = 1; day <= daysInMonth; day++) {
    calendarDays.push(day);
  }

  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
      {/* Calendar Header */}
      <div className="p-6 border-b border-gray-200 dark:border-gray-700">
        <div className="flex items-center justify-between mb-4">
          <div className="relative" ref={yearPickerRef}>
            <button
              onClick={() => setShowYearPicker(!showYearPicker)}
              className="group flex items-center gap-2 hover:bg-gray-100 dark:hover:bg-gray-700 px-3 py-2 rounded-lg transition-colors"
            >
              <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {thaiMonths[month]} {year + 543}
              </h2>
              <svg 
                className={`w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform ${showYearPicker ? 'rotate-180' : ''}`} 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            {/* Year Picker Dropdown */}
            {showYearPicker && (
              <div className="absolute top-full left-0 mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 w-64 max-h-80 overflow-y-auto">
                <div className="p-3 border-b border-gray-200 dark:border-gray-700">
                  <h3 className="font-semibold text-gray-900 dark:text-gray-100 text-sm">เลือกปี</h3>
                </div>
                <div className="p-2 grid grid-cols-3 gap-2">
                  {yearRange.map((y) => {
                    const thaiYear = y + 543;
                    const isCurrentYear = y === currentYear;
                    const isSelectedYear = y === year;
                    
                    return (
                      <button
                        key={y}
                        onClick={() => changeYear(y)}
                        className={`px-3 py-2 rounded-lg text-sm font-semibold transition-all ${
                          isSelectedYear
                            ? 'bg-[#A21D21] text-white shadow-md'
                            : isCurrentYear
                            ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/60'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        }`}
                      >
                        {thaiYear}
                      </button>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={goToToday}
              className="px-4 py-2 text-sm font-semibold text-[#A21D21] dark:text-[#C92828] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              วันนี้
            </button>
            <div className="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
              <button
                onClick={() => setView('month')}
                className={`px-3 py-1.5 text-sm font-semibold rounded-md transition-colors ${
                  view === 'month'
                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                }`}
              >
                เดือน
              </button>
              <button
                onClick={() => setView('week')}
                className={`px-3 py-1.5 text-sm font-semibold rounded-md transition-colors ${
                  view === 'week'
                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                }`}
              >
                สัปดาห์
              </button>
            </div>
          </div>
        </div>
        
        <div className="flex items-center justify-between">
          <button
            onClick={goToPrevious}
            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          >
            <svg className="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            onClick={goToNext}
            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          >
            <svg className="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      {/* Calendar Grid */}
      <div className="p-6">
        {view === 'month' ? (
          // Month View
          <>
        {/* Day Headers */}
        <div className="grid grid-cols-7 gap-2 mb-2">
          {thaiDays.map((day, index) => (
            <div
              key={day}
              className={`text-center text-sm font-bold py-2 ${
                index === 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'
              }`}
            >
              {day}
            </div>
          ))}
        </div>

        {/* Calendar Days */}
        <div className="grid grid-cols-7 gap-2">
          {calendarDays.map((day, index) => {
            if (day === null) {
              return <div key={`empty-${index}`} className="aspect-square" />;
            }

            const dayEvents = getEventsForDate(day);
            const today = isToday(day);

            return (
              <div
                key={day}
                className={`aspect-square border rounded-lg p-2 transition-all hover:shadow-md ${
                  today
                    ? 'bg-[#A21D21]/10 dark:bg-[#A21D21]/20 border-[#A21D21] dark:border-[#C92828]'
                    : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                }`}
              >
                <div className="flex flex-col h-full">
                  <div
                    className={`text-sm font-semibold mb-1 ${
                      today
                        ? 'text-[#A21D21] dark:text-[#C92828]'
                        : 'text-gray-700 dark:text-gray-300'
                    }`}
                  >
                    {day}
                  </div>
                  <div className="flex-1 overflow-hidden space-y-1">
                    {dayEvents.slice(0, 3).map((event) => (
                      <button
                        key={event.id}
                        onClick={() => onEventClick?.(event)}
                        className={`w-full text-left px-1.5 py-0.5 rounded text-xs font-medium truncate transition-all hover:shadow-sm ${event.color}`}
                      >
                        {event.startTime && (
                          <span className="mr-1">{event.startTime}</span>
                        )}
                        {event.title}
                      </button>
                    ))}
                    {dayEvents.length > 3 && (
                      <div className="text-xs text-gray-500 dark:text-gray-400 px-1.5">
                        +{dayEvents.length - 3} เพิ่มเติม
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
          </>
        ) : (
          // Week View
          <>
            {/* Week Day Headers */}
            <div className="grid grid-cols-7 gap-2 mb-2">
              {thaiDays.map((day, index) => (
                <div
                  key={day}
                  className={`text-center text-sm font-bold py-2 ${
                    index === 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'
                  }`}
                >
                  {day}
                </div>
              ))}
            </div>

            {/* Week Days */}
            <div className="grid grid-cols-7 gap-2">
              {weekDates.map((date, index) => {
                const dayEvents = getEventsForDateObj(date);
                const today = isTodayDate(date);
                const isCurrentMonth = date.getMonth() === month;

                return (
                  <div
                    key={index}
                    className={`min-h-[200px] border rounded-lg p-3 transition-all hover:shadow-md ${
                      today
                        ? 'bg-[#A21D21]/10 dark:bg-[#A21D21]/20 border-[#A21D21] dark:border-[#C92828]'
                        : isCurrentMonth
                        ? 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                        : 'bg-gray-100/50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-800'
                    }`}
                  >
                    <div className="flex flex-col h-full">
                      <div
                        className={`text-center font-semibold mb-2 pb-2 border-b ${
                          today
                            ? 'text-[#A21D21] dark:text-[#C92828] border-[#A21D21]/30 dark:border-[#C92828]/30'
                            : isCurrentMonth
                            ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600'
                            : 'text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700'
                        }`}
                      >
                        <div className="text-xl">{date.getDate()}</div>
                        <div className="text-xs">
                          {thaiMonths[date.getMonth()].substring(0, 3)}
                        </div>
                      </div>
                      <div className="flex-1 overflow-y-auto space-y-2">
                        {dayEvents.length === 0 ? (
                          <div className="text-center text-xs text-gray-400 dark:text-gray-600 mt-4">
                            ไม่มีกิจกรรม
                          </div>
                        ) : (
                          dayEvents.map((event) => (
                            <button
                              key={event.id}
                              onClick={() => onEventClick?.(event)}
                              className={`w-full text-left px-2 py-1.5 rounded text-xs font-medium transition-all hover:shadow-md ${event.color}`}
                            >
                              {event.startTime && (
                                <div className="font-bold mb-0.5">{event.startTime}</div>
                              )}
                              <div className="line-clamp-2">{event.title}</div>
                            </button>
                          ))
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}
      </div>

      {/* Legend */}
      <div className="px-6 pb-6">
        <div className="flex flex-wrap gap-4 text-xs">
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded bg-red-500"></div>
            <span className="text-gray-600 dark:text-gray-400">Live Session</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded bg-blue-500"></div>
            <span className="text-gray-600 dark:text-gray-400">Webinar</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded bg-orange-500"></div>
            <span className="text-gray-600 dark:text-gray-400">Deadline</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded bg-purple-500"></div>
            <span className="text-gray-600 dark:text-gray-400">Assignment</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-3 rounded bg-green-500"></div>
            <span className="text-gray-600 dark:text-gray-400">Quiz</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Calendar;
