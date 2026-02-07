import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";


const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "E-Learning Platform | ระบบการเรียนรู้ออนไลน์",
  description: "ระบบ E-Learning สำหรับพัฒนาบุคลากรภายในองค์กร - เรียนรู้ พัฒนาทักษะ เติบโตไปกับเรา",
  keywords: "e-learning, การเรียนรู้, พัฒนาบุคลากร, อบรมออนไลน์, หลักสูตรออนไลน์",
  authors: [{ name: "E-Learning Team" }],
  viewport: "width=device-width, initial-scale=1",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="th">
      <body
        className={`${inter.variable} antialiased font-sans`}
      >
        {children}
      </body>
    </html>
  );
}