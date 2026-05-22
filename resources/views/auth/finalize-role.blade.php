<x-app-layout>
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left: Google-only Card -->
        <div class="flex items-center justify-center bg-white py-12 px-6">
            <div class="max-w-md w-full space-y-8">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
                    <div class="flex justify-center mb-6">
                        <img class="h-16 w-16 rounded-full bg-white p-2 shadow-sm" src="{{ asset('assets/images/AULogo.png') }}" alt="AU Logo">
                    </div>
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-extrabold text-red-700">Welcome Back</h2>
                        <p class="mt-3 text-sm text-gray-600">Please log in to your account</p>
                    </div>

                    <!-- Google Authentication Form (only control on the card) -->
                    <form class="space-y-6" action="{{ route('auth.google.redirect') }}" method="GET">
                        <button type="submit"
                                class="w-full py-3 px-4 border border-gray-300 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition duration-200 flex items-center justify-center space-x-3 font-semibold text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span>Continue with Google</span>
                        </button>

                        <!-- Error Messages -->
                        @error('auth')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start space-x-3 mt-4">
                                <i class="fas fa-exclamation-triangle text-red-600 mt-0.5"></i>
                                <span class="text-red-700 text-sm font-medium">{{ $message }}</span>
                            </div>
                        @enderror
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Promo Panel -->
        <aside class="hidden md:flex items-center justify-center bg-red-700 bg-gradient-to-br from-red-700 to-red-900 text-white p-12">
            <div class="max-w-lg text-center">
                <h3 class="text-4xl font-extrabold">Vehicle Registration System</h3>
                <p class="mt-6 text-lg text-red-100 leading-relaxed">Manage your vehicle registrations efficiently and securely. Keep track of all your vehicles in one place.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
