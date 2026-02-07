'use client';

import React from 'react';
import Link from 'next/link';
import { GraduationCap, Mail, Phone, MapPin } from 'lucide-react';

export const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();

  const footerLinks = {
    menu: [
      { label: 'หลักสูตรทั้งหมด', href: '/catalog' },
      { label: 'เส้นทางการเรียน', href: '/learning-paths' },
      { label: 'เกี่ยวกับเรา', href: '/about' },
      { label: 'ติดต่อ', href: '/contact' },
    ],
    categories: [
      { label: 'ความปลอดภัย', href: '/catalog?category=ความปลอดภัย' },
      { label: 'เทคโนโลยี', href: '/catalog?category=เทคโนโลยี' },
      { label: 'คุณภาพ', href: '/catalog?category=คุณภาพ' },
      { label: 'การจัดการ', href: '/catalog?category=การจัดการ' },
    ],
    support: [
      { label: 'ความช่วยเหลือ', href: '/help' },
      { label: 'คำถามที่พบบ่อย', href: '/faq' },
      { label: 'นโยบายความเป็นส่วนตัว', href: '/privacy' },
      { label: 'ข้อกำหนดการใช้งาน', href: '/terms' },
    ],
  };

  return (
    <footer className="bg-gray-900 text-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Brand */}
          <div className="lg:col-span-1">
            <Link href="/" className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 bg-maroon-700 rounded-lg flex items-center justify-center">
                <GraduationCap className="w-6 h-6 text-white" />
              </div>
              <span className="text-xl font-bold">E-Learning</span>
            </Link>
            <p className="text-gray-400 text-sm mb-4">
              แพลตฟอร์มการเรียนรู้ออนไลน์สำหรับพัฒนาบุคลากรภายในองค์กร
            </p>
            <div className="space-y-2">
              <a href="mailto:info@elearning.com" className="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                <Mail className="w-4 h-4" />
                info@elearning.com
              </a>
              <a href="tel:021234567" className="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                <Phone className="w-4 h-4" />
                02-123-4567
              </a>
              <div className="flex items-center gap-2 text-sm text-gray-400">
                <MapPin className="w-4 h-4" />
                กรุงเทพมหานคร
              </div>
            </div>
          </div>

          {/* Menu Links */}
          <div>
            <h3 className="font-semibold mb-4">เมนูหลัก</h3>
            <ul className="space-y-2">
              {footerLinks.menu.map((link) => (
                <li key={link.href}>
                  <Link href={link.href} className="text-sm text-gray-400 hover:text-white transition-colors">
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Categories */}
          <div>
            <h3 className="font-semibold mb-4">หมวดหมู่</h3>
            <ul className="space-y-2">
              {footerLinks.categories.map((link) => (
                <li key={link.href}>
                  <Link href={link.href} className="text-sm text-gray-400 hover:text-white transition-colors">
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Support */}
          <div>
            <h3 className="font-semibold mb-4">ช่วยเหลือ</h3>
            <ul className="space-y-2">
              {footerLinks.support.map((link) => (
                <li key={link.href}>
                  <Link href={link.href} className="text-sm text-gray-400 hover:text-white transition-colors">
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom */}
        <div className="border-t border-gray-800 mt-12 pt-8 text-center">
          <p className="text-sm text-gray-400">
            © {currentYear} E-Learning Platform. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
