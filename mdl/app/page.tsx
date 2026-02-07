'use client';

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import Link from 'next/link';

interface Course {
  id: number;
  title: string;
  image: string;
  category: string;
  level: string;
  instructor: string;
  rating: number;
  duration: string;
}

export default function HomePage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [currentSlide, setCurrentSlide] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [featuredCourses, setFeaturedCourses] = useState<Course[]>([
    {
      id: 1,
      title: 'ความปลอดภัยในการทำงาน (Safety First)',
      image: '/moodle-app/images/safe_7.png',
      category: 'ความปลอดภัย',
      level: 'เบื้องต้น',
      instructor: 'อ.สมชาย ใจดี',
      rating: 4.8,
      duration: '6 ชั่วโมง'
    },
    {
      id: 2,
      title: 'การใช้งานอุปกรณ์ป้องกันภัยส่วนบุคคล (PPE)',
      image: '/moodle-app/images/safe_7.png',
      category: 'ความปลอดภัย',
      level: 'เบื้องต้น',
      instructor: 'อ.สมศรี มีสุข',
      rating: 4.5,
      duration: '4 ชั่วโมง'
    },
    {
      id: 3,
      title: 'กฎหมายความปลอดภัยอาชีวอนามัย',
      image: '/moodle-app/images/safe_7.png',
      category: 'กฎหมาย',
      level: 'กลาง',
      instructor: 'ดร.วิชัย มั่นคง',
      rating: 4.9,
      duration: '8 ชั่วโมง'
    },
    {
      id: 4,
      title: 'การปฐมพยาบาลเบื้องต้น (First Aid)',
      image: '/moodle-app/images/safe_7.png',
      category: 'สุขภาพ',
      level: 'เบื้องต้น',
      instructor: 'พยาบาลสาวิตรี',
      rating: 4.7,
      duration: '6 ชั่วโมง'
    },
    {
      id: 5,
      title: 'การจัดการความปลอดภัยในองค์กร',
      image: '/moodle-app/images/safe_7.png',
      category: 'การจัดการ',
      level: 'กลาง',
      instructor: 'ดร.ธนา มั่งมี',
      rating: 4.6,
      duration: '10 ชั่วโมง'
    },
    {
      id: 6,
      title: 'เทคนิคการสอนออนไลน์',
      image: '/moodle-app/images/safe_7.png',
      category: 'เทคโนโลยี',
      level: 'กลาง',
      instructor: 'อ.วรรณ สอนดี',
      rating: 4.8,
      duration: '8 ชั่วโมง'
    },
    {
      id: 7,
      title: 'การสร้างเสริมวัฒนธรรมความปลอดภัย',
      image: '/moodle-app/images/safe_7.png',
      category: 'ความปลอดภัย',
      level: 'ขั้นสูง',
      instructor: 'ดร.สมคิด คิดดี',
      rating: 4.9,
      duration: '12 ชั่วโมง'
    },
    {
      id: 8,
      title: 'การประเมินความเสี่ยงในงาน',
      image: '/moodle-app/images/safe_7.png',
      category: 'ความปลอดภัย',
      level: 'กลาง',
      instructor: 'อ.ประเสริฐ รู้เรื่อง',
      rating: 4.7,
      duration: '6 ชั่วโมง'
    }
  ]);
  const [loading, setLoading] = useState(false);
  const [showSearch, setShowSearch] = useState(false);
  const coursesPerPage = 12;

  // Compute search results directly during rendering
  const computedSearchResults = searchQuery.trim()
    ? featuredCourses.filter(course =>
        course.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        course.category.toLowerCase().includes(searchQuery.toLowerCase()) ||
        course.instructor.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : [];

  // Close search on click outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      if (!target.closest('.search-container')) {
        setShowSearch(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Auto-advance slider
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % 4);
    }, 5000);
    return () => clearInterval(timer);
  }, []);

  // Slider data
  const slides = [
    {
      image: '/moodle-app/images/Landing.png',
      badge: 'หลักสูตรยอดนิยม'
    },
    {
      image: '/moodle-app/images/safe_7.png',
      badge: 'ประกันคุณภาพ'
    },
    {
      image: '/moodle-app/images/sec_1.png',
      badge: 'มั่นคงปลอดภัย'
    },
    {
      image: '/moodle-app/images/sub_1.png',
      badge: 'พัฒนาตนเอง'
    }
  ];



  // Calculate pagination
  const indexOfLastCourse = currentPage * coursesPerPage;
  const indexOfFirstCourse = indexOfLastCourse - coursesPerPage;
  const currentCourses = featuredCourses.slice(indexOfFirstCourse, indexOfLastCourse);
  const totalPages = Math.ceil(featuredCourses.length / coursesPerPage);

  // Categories
  const categories = [
    { name: 'ความปลอดภัย', icon: '🛡️', count: 45, color: 'bg-red-500' },
    { name: 'เทคโนโลยี', icon: '💻', count: 38, color: 'bg-blue-500' },
    { name: 'คุณภาพ', icon: '✅', count: 29, color: 'bg-green-500' },
    { name: 'การจัดการ', icon: '📊', count: 52, color: 'bg-purple-500' },
    { name: 'ทักษะอ่อน', icon: '🤝', count: 31, color: 'bg-yellow-500' },
    { name: 'Digital', icon: '🚀', count: 24, color: 'bg-indigo-500' }
  ];



  return (
    <div className="min-h-screen bg-gray-50" style={{ fontFamily: 'Plus Jakarta Sans, Kanit, sans-serif' }}>
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-8">
              <Link href="/" className="flex items-center space-x-3 group">
                <div className="w-10 h-10 bg-gradient-to-br from-[#A21D21] to-[#8A1919] rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-105">
                  <span className="text-white font-bold text-lg">E</span>
                </div>
                <div className="flex flex-col">
                  <span className="text-xl font-bold text-gray-900 group-hover:text-[#A21D21] transition-colors duration-300">E-learning</span>
                  <span className="text-xs text-gray-500 font-medium">Platform</span>
                </div>
              </Link>

              <nav className="hidden md:flex items-center space-x-1">
                <Link href="/catalog" className="relative px-4 py-2 text-gray-700 hover:text-[#A21D21] font-medium transition-all duration-300 group">
                  หลักสูตรทั้งหมด
                  <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#A21D21] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
                </Link>
                <Link href="/learning-paths" className="relative px-4 py-2 text-gray-700 hover:text-[#A21D21] font-medium transition-all duration-300 group">
                  เส้นทางการเรียน
                  <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#A21D21] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
                </Link>
                <Link href="/about" className="relative px-4 py-2 text-gray-700 hover:text-[#A21D21] font-medium transition-all duration-300 group">
                  เกี่ยวกับเรา
                  <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#A21D21] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
                </Link>
                <Link href="/contact" className="relative px-4 py-2 text-gray-700 hover:text-[#A21D21] font-medium transition-all duration-300 group">
                  ติดต่อ
                  <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#A21D21] transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
                </Link>
              </nav>
            </div>

            <div className="flex items-center space-x-4">
              <div className="relative hidden md:block search-container">
                <input
                  type="text"
                  placeholder="ค้นหาหลักสูตร..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onFocus={() => searchQuery.trim() && setShowSearch(true)}
                  className="w-64 pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#A21D21]/20 focus:border-[#A21D21] transition-all duration-300 bg-gray-50 hover:bg-white"
                />
                <svg className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                
                {/* Search Results Dropdown */}
                {showSearch && computedSearchResults.length > 0 && (
                  <div className="absolute top-full mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-200 max-h-80 overflow-y-auto z-50">
                    {computedSearchResults.map((course) => (
                      <Link
                        key={course.id}
                        href={`/learn/${course.id}`}
                        className="flex items-center p-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                        onClick={() => {
                          setSearchQuery('');
                          setShowSearch(false);
                        }}
                      >
                        <img src={course.image} alt={course.title} className="w-12 h-12 object-cover rounded-lg mr-3" />
                        <div className="flex-1">
                          <h4 className="font-semibold text-sm text-gray-900 line-clamp-1">{course.title}</h4>
                          <p className="text-xs text-gray-500">{course.instructor} • {course.duration}</p>
                        </div>
                        <span className="text-xs px-2 py-1 bg-[#A21D21] text-white rounded-full">
                          {course.level}
                        </span>
                      </Link>
                    ))}
                  </div>
                )}
              </div>
              <Link href="/login" className="px-5 py-2.5 text-[#A21D21] border border-[#A21D21] rounded-xl hover:bg-[#A21D21] hover:text-white transition-all duration-300 font-medium hover:shadow-lg hover:scale-105">
                เข้าสู่ระบบ
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Hero Slider */}
      <section className="relative overflow-hidden">
        <div className="relative h-[600px] w-full">
          <div className="relative h-full w-full">
            {slides.map((slide, index) => (
              <div
                key={index}
                className={`absolute inset-0 transition-opacity duration-1000 ${index === currentSlide ? 'opacity-100' : 'opacity-0'
                  }`}
              >
                <img src={slide.image} className="object-cover w-full h-full" />
              </div>
            ))}
          </div>

          {/* Slider indicators */}
          <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3">
            {slides.map((_, index) => (
              <button
                key={index}
                onClick={() => setCurrentSlide(index)}
                className={`h-3 rounded-full transition-all duration-300 ${index === currentSlide ? "w-12" : "w-3"
                  }`}
                style={{ backgroundColor: index === currentSlide ? "#A21D21" : "rgba(255,255,255,0.4)" }}
              />
            ))}
          </div>

          {/* Navigation arrows */}
          <button
            onClick={() => setCurrentSlide((prev) => (prev - 1 + 4) % 4)}
            className="absolute left-4 top-1/2 -translate-y-1/2 p-3 bg-white/20 backdrop-blur-md rounded-full hover:bg-white/30 transition-all"
          >
            <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            onClick={() => setCurrentSlide((prev) => (prev + 1) % 4)}
            className="absolute right-4 top-1/2 -translate-y-1/2 p-3 bg-white/20 backdrop-blur-md rounded-full hover:bg-white/30 transition-all"
          >
            <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-16 bg-white">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div className="group">
              <div className="w-16 h-16 bg-gradient-to-br from-[#A21D21] to-[#8A1919] rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h3 className="text-3xl font-bold text-gray-900 mb-2">{featuredCourses.length}+</h3>
              <p className="text-gray-600">หลักสูตรทั้งหมด</p>
            </div>
            <div className="group">
              <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <h3 className="text-3xl font-bold text-gray-900 mb-2">5,000+</h3>
              <p className="text-gray-600">ผู้เรียนทั้งหมด</p>
            </div>
            <div className="group">
              <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
              </div>
              <h3 className="text-3xl font-bold text-gray-900 mb-2">98%</h3>
              <p className="text-gray-600">ความพึงพอใจ</p>
            </div>
            <div className="group">
              <div className="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-3xl font-bold text-gray-900 mb-2">24/7</h3>
              <p className="text-gray-600">เรียนได้ตลอดเวลา</p>
            </div>
          </div>
        </div>
      </section>

      {/* Featured Courses */}
      <section className="py-16 bg-gray-50">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">หลักสูตรแนะนำ</h2>
            <p className="text-gray-600 text-lg">เลือกเรียนหลักสูตรยอดนิยมจากผู้เชี่ยวชาญ</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {currentCourses.map((course) => (
              <div key={course.id} className="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden group">
                <div className="relative">
                  <img src={course.image} alt={course.title} className="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500" />
                  <div className="absolute top-3 left-3">
                    <span className="font-consistent px-3 py-1 bg-[#A21D21] text-white text-xs font-semibold rounded-full shadow-lg">
                      {course.category}
                    </span>
                  </div>
                  <div className="absolute top-3 right-3">
                    <span className="font-consistent px-3 py-1 bg-white/90 backdrop-blur-sm text-gray-800 text-xs font-semibold rounded-full shadow-lg">
                      {course.level}
                    </span>
                  </div>
                </div>

                <div className="p-5">
                  <h3 className="font-consistent font-bold text-lg text-gray-900 mb-3 line-clamp-2 group-hover:text-[#A21D21] transition-colors">
                    {course.title}
                  </h3>
                  <p className="font-consistent text-sm text-gray-600 mb-4">{course.instructor}</p>

                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center">
                      <div className="flex text-yellow-400">
                        {[...Array(5)].map((_, i) => (
                          <svg key={i} className={`w-4 h-4 ${i < Math.floor(course.rating) ? 'fill-current' : 'fill-gray-300'}`} viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                        ))}
                      </div>
                      <span className="text-sm font-semibold text-gray-700 ml-1">{course.rating}</span>
                    </div>
                    <span className="text-sm text-gray-500">{course.duration}</span>
                  </div>

                  <div className="flex items-center justify-between pt-3 border-t border-gray-100">
                    <Link href={`/learn/${course.id}`} className="px-4 py-2 bg-[#A21D21] text-white text-sm rounded-lg hover:bg-[#8A1919] transition-colors font-medium hover:shadow-md">
                      รายละเอียด
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Enhanced Pagination */}
          <div className="mt-8 flex justify-center items-center gap-2">
            <button
              onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
              disabled={currentPage === 1}
              className="p-3 rounded-xl border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                onClick={() => setCurrentPage(page)}
                className={`px-4 py-2 rounded-xl font-semibold transition-all hover:scale-105 ${page === currentPage
                  ? "text-white shadow-lg"
                  : "border border-gray-300 hover:bg-gray-50 text-gray-700"
                  }`}
                style={page === currentPage ? { backgroundColor: "#A21D21" } : {}}
              >
                {page}
              </button>
            ))}
            <button
              onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
              disabled={currentPage === totalPages}
              className="p-3 rounded-xl border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

          <div className="text-center mt-8">
            <Link href="/catalog" className="px-8 py-3 bg-[#A21D21] text-white rounded-xl hover:bg-[#8A1919] transition-colors font-semibold hover:shadow-lg hover:scale-105">
              ดูหลักสูตรทั้งหมด ({featuredCourses.length} หลักสูตร)
            </Link>
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="py-16 bg-gray-100">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">หมวดหมู่หลักสูตร</h2>
            <p className="text-gray-600">เลือกเรียนตามหมวดหมู่ที่คุณสนใจ</p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {categories.map((category, index) => (
              <Link key={index} href={`/catalog?category=${category.name}`} className="bg-white rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
                <div className={`w-12 h-12 ${category.color} rounded-full flex items-center justify-center mx-auto mb-3`}>
                  <span className="text-2xl">{category.icon}</span>
                </div>
                <h3 className="font-semibold text-gray-900 mb-1">{category.name}</h3>
                <p className="text-sm text-gray-500">{category.count} หลักสูตร</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-16 bg-gradient-to-r from-[#A21D21] to-[#8A1919] text-white">
        <div className="container mx-auto px-4 text-center">
          <h2 className="text-3xl font-bold mb-4">เริ่มต้นการเรียนรู้วันนี้</h2>
          <p className="text-xl mb-8 text-gray-100">เข้าถึงหลักสูตรคุณภาพได้ทันที</p>
          <Link href="/catalog" className="px-8 py-3 bg-white text-[#A21D21] rounded-lg hover:bg-gray-100 transition-colors font-semibold">
            ดูหลักสูตรทั้งหมด
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center space-x-2 mb-4">
                <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                  <span className="text-[#A21D21] font-bold">E</span>
                </div>
                <span className="text-xl font-bold">E-Learning</span>
              </div>
              <p className="text-gray-400">แพลตฟอร์มการเรียนรู้ออนไลน์สำหรับพัฒนาบุคลากรภายในองค์กร</p>
            </div>

            <div>
              <h3 className="font-semibold mb-4">เมนูหลัก</h3>
              <ul className="space-y-2">
                <li><Link href="/catalog" className="text-gray-400 hover:text-white">หลักสูตรทั้งหมด</Link></li>
                <li><Link href="/learning-paths" className="text-gray-400 hover:text-white">เส้นทางการเรียน</Link></li>
                <li><Link href="/about" className="text-gray-400 hover:text-white">เกี่ยวกับเรา</Link></li>
                <li><Link href="/contact" className="text-gray-400 hover:text-white">ติดต่อ</Link></li>
              </ul>
            </div>

            <div>
              <h3 className="font-semibold mb-4">หมวดหมู่</h3>
              <ul className="space-y-2">
                <li><Link href="/catalog?category=ความปลอดภัย" className="text-gray-400 hover:text-white">ความปลอดภัย</Link></li>
                <li><Link href="/catalog?category=เทคโนโลยี" className="text-gray-400 hover:text-white">เทคโนโลยี</Link></li>
                <li><Link href="/catalog?category=คุณภาพ" className="text-gray-400 hover:text-white">คุณภาพ</Link></li>
                <li><Link href="/catalog?category=การจัดการ" className="text-gray-400 hover:text-white">การจัดการ</Link></li>
              </ul>
            </div>

            <div>
              <h3 className="font-semibold mb-4">ติดต่อเรา</h3>
              <ul className="space-y-2">
                <li className="text-gray-400">อีเมล: info@elearning.com</li>
                <li className="text-gray-400">โทร: 02-123-4567</li>
                <li className="text-gray-400">ที่อยู่: กรุงเทพมหานคร</li>
              </ul>
            </div>
          </div>

          <div className="border-t border-gray-800 mt-8 pt-8 text-center">
            <p className="text-gray-400">© 2024 E-Learning Platform. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
