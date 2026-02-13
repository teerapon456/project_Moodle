<?php
// Modules/YearlyActivity/Views/reports.php
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="ri-file-chart-line text-primary"></i>
                Reports Center
            </h1>
            <p class="text-gray-500 mt-1">Generate and export reports for your yearly activities.</p>
        </div>
    </div>

    <!-- Report Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Activity Report Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
            <div class="h-2 bg-red-500"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-red-50 text-primary rounded-lg flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                    <i class="ri-calendar-event-line"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Activities Report</h3>
                <p class="text-gray-500 text-sm mb-6"> Comprehensive list of all planned activities, their status, progress, and timelines.</p>

                <div class="flex flex-col gap-2">
                    <a href="?action=pdf_activities" target="_blank" class="flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition">
                        <i class="ri-file-pdf-line"></i> View PDF Report
                    </a>
                    <a href="?action=export_activities" class="flex items-center justify-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        <i class="ri-file-excel-line"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- RASCI Matrix Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
            <div class="h-2 bg-purple-500"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                    <i class="ri-group-line"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">RASCI Matrix</h3>
                <p class="text-gray-500 text-sm mb-6">Detailed breakdown of roles and responsibilities (Responsible, Accountable, etc.) for each activity.</p>

                <div class="flex flex-col gap-2">
                    <a href="?action=pdf_rasci" target="_blank" class="flex items-center justify-center gap-2 px-4 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-100 transition">
                        <i class="ri-file-pdf-line"></i> View PDF Report
                    </a>
                    <a href="?action=export_rasci" class="flex items-center justify-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        <i class="ri-file-excel-line"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Risk Assessment Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
            <div class="h-2 bg-orange-500"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                    <i class="ri-alert-line"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Risk Register</h3>
                <p class="text-gray-500 text-sm mb-6">Overview of identified risks, their impact, probability, and mitigation plans.</p>

                <div class="flex flex-col gap-2">
                    <a href="?action=pdf_risks" target="_blank" class="flex items-center justify-center gap-2 px-4 py-2 bg-orange-50 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-100 transition">
                        <i class="ri-file-pdf-line"></i> View PDF Report
                    </a>
                    <a href="?action=export_risks" class="flex items-center justify-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        <i class="ri-file-excel-line"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

    </div>


</div>