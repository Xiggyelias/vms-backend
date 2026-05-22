<x-app-layout>
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 -mt-8">
        <div class="max-w-md w-full">
            <!-- Card Container -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-block bg-primary bg-opacity-10 p-4 rounded-xl mb-6">
                        <img class="h-12 w-auto" src="{{ asset('assets/images/AULogo.png') }}" alt="AU Logo">
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-gray-900">
                        Admin Login
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Access the administrative dashboard
                    </p>
                </div>

                <!-- Form -->
                <form class="space-y-6" action="{{ route('auth.admin.login.post') }}" method="POST">
                    @csrf

                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input id="username" name="username" type="text" autocomplete="username" required
                                   class="input-field pl-10"
                                   placeholder="Enter your username"
                                   value="{{ old('username') }}">
                        </div>
                        @error('username')
                            <p class="mt-1 text-sm text-red-600 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   class="input-field pl-10"
                                   placeholder="Enter your password">
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary w-full flex items-center justify-center space-x-2 mt-8">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Sign in as Administrator</span>
                    </button>

                    <!-- Error Alert -->
                    @if ($errors->any() && !$errors->has('username') && !$errors->has('password'))
                        <div class="alert-error">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span>Login failed. Please check your credentials.</span>
                        </div>
                    @endif

                    <!-- Back Link -->
                    <div class="text-center pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Not an admin?
                            <a href="{{ route('auth.login') }}" class="font-semibold text-primary hover:text-primary-dark transition duration-200">
                                User Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Footer Info -->
            <p class="text-center text-xs text-gray-500 mt-6">
                🔒 Secure Admin Access | Vehicle Registration System
            </p>
        </div>
    </div>
</x-app-layout>











