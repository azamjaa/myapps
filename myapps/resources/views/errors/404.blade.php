<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Dijumpai | MyApps KEDA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #fbbf24 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header with KEDA Colors -->
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 px-8 py-12 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent"></div>
                <div class="relative z-10">
                    <h1 class="text-9xl font-black text-white mb-4 drop-shadow-lg">404</h1>
                    <p class="text-2xl text-white/90 font-semibold">Halaman Tidak Dijumpai</p>
                </div>
            </div>

            <!-- Content -->
            <div class="px-8 py-12 text-center">
                <div class="mb-8">
                    <svg class="w-32 h-32 mx-auto text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-bold text-gray-900 mb-4">Oops! Kami Tidak Jumpa Halaman Ini</h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    Halaman yang anda cari mungkin telah dipindahkan, dihapus, atau tidak pernah wujud.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="javascript:history.back()" 
                       class="inline-flex items-center justify-center px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali
                    </a>
                    <a href="/admin" 
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-900 to-blue-700 hover:from-blue-800 hover:to-blue-600 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Ke Dashboard
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 border-t border-gray-200">
                <p class="text-center text-gray-600 text-sm">
                    <span class="font-semibold text-blue-900">MyApps KEDA</span> - Portal Aplikasi Perusahaan
                </p>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="mt-8 text-center">
            <p class="text-white/90 text-sm">
                Jika anda menghadapi masalah, sila hubungi 
                <a href="mailto:support@keda.gov.my" class="font-semibold underline hover:text-amber-300 transition-colors">
                    Support KEDA
                </a>
            </p>
        </div>
    </div>
</body>
</html>

