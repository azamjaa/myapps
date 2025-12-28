<!DOCTYPE html>
<html lang="ms">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyApps KEDA - Enterprise Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
            <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #fbbf24 100%);
            min-height: 100vh;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
            </style>
    </head>
<body>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-6xl w-full">
            <!-- Main Card -->
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden">
                <!-- Hero Section -->
                <div class="relative bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 px-8 py-16 text-center overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent"></div>
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 0); background-size: 40px 40px;"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <!-- Logo -->
                        <div class="mb-8 float-animation">
                            <div class="inline-block bg-white p-6 rounded-2xl shadow-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20" viewBox="0 0 100 100" fill="none">
                                    <path d="M50 10 L80 25 L80 50 Q80 75 50 90 Q20 75 20 50 L20 25 Z" 
                                          fill="url(#kedaGradient)" 
                                          stroke="#1e3a8a" 
                                          stroke-width="2"/>
                                    <path d="M35 35 L35 65 M35 50 L50 35 M35 50 L50 65" 
                                          stroke="#fbbf24" 
                                          stroke-width="4" 
                                          stroke-linecap="round" 
                                          stroke-linejoin="round"/>
                                    <path d="M65 45 L67 51 L73 51 L68 55 L70 61 L65 57 L60 61 L62 55 L57 51 L63 51 Z" 
                                          fill="#fbbf24"/>
                                    <defs>
                                        <linearGradient id="kedaGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#1e3a8a;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </div>
                        </div>

                        <h1 class="text-5xl md:text-6xl font-black text-white mb-4 drop-shadow-lg">
                            MyApps KEDA
                        </h1>
                        <p class="text-xl md:text-2xl text-white/90 font-medium mb-2">
                            Portal Aplikasi Perusahaan
                        </p>
                        <p class="text-lg text-white/80">
                            Single Sign-On Hub & Single Source of Truth
                        </p>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="grid md:grid-cols-3 gap-6 p-8">
                    <!-- Feature 1 -->
                    <div class="group p-6 bg-gradient-to-br from-blue-50 to-white rounded-2xl border-2 border-blue-100 hover:border-blue-300 transition-all duration-300 hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-900 to-blue-700 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Single Sign-On</h3>
                        <p class="text-gray-600">Log masuk sekali untuk akses semua aplikasi dengan selamat.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="group p-6 bg-gradient-to-br from-amber-50 to-white rounded-2xl border-2 border-amber-100 hover:border-amber-300 transition-all duration-300 hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">SSOT API</h3>
                        <p class="text-gray-600">Sumber data staf yang sahih dan dikemaskini.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="group p-6 bg-gradient-to-br from-emerald-50 to-white rounded-2xl border-2 border-emerald-100 hover:border-emerald-300 transition-all duration-300 hover:shadow-xl">
                        <div class="w-14 h-14 bg-gradient-to-br from-emerald-600 to-emerald-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Pantas & Modern</h3>
                        <p class="text-gray-600">Dibina dengan Laravel 11 & FilamentPHP v3.</p>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-12 text-center border-t-2 border-gray-200">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Sedia Untuk Bermula?</h2>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/admin/login" 
                           class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-900 to-blue-700 hover:from-blue-800 hover:to-blue-600 text-white font-bold text-lg rounded-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Log Masuk
                        </a>
                        <a href="/admin" 
                           class="inline-flex items-center justify-center px-8 py-4 bg-white hover:bg-gray-50 text-blue-900 font-bold text-lg rounded-xl border-2 border-blue-900 transition-all duration-300 shadow-lg hover:shadow-2xl">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>
                    </div>
                    <p class="mt-8 text-gray-600">
                        <span class="font-semibold text-blue-900">Sokongan Teknikal:</span> 
                        <a href="mailto:support@keda.gov.my" class="text-amber-600 hover:text-amber-700 font-medium underline">
                            support@keda.gov.my
                        </a>
                    </p>
                </div>

                <!-- Footer -->
                <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-8 py-6">
                    <p class="text-center text-white/80 text-sm">
                        © {{ date('Y') }} KEDA. Hak Cipta Terpelihara. 
                        <span class="mx-2">•</span>
                        Dibina dengan 
                        <span class="text-amber-400">Laravel 11</span> & 
                        <span class="text-amber-400">FilamentPHP v3</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>
