<nav class="bg-gradient-to-r from-red-600 to-red-700 shadow-lg border-b border-red-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center space-x-3">
                    <a href="{{ url('/') }}" class="flex items-center hover:opacity-90 transition duration-200">
                        <img class="h-10 w-auto" src="{{ asset('assets/images/AULogo.png') }}" alt="AU Logo">
                        <div>
                            <span class="ml-2 text-white font-bold text-lg">Vehicle Registration</span>
                            <p class="ml-2 text-red-100 text-xs">System</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                @if(session('logged_in'))
                    <!-- User Nav -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <p class="text-white font-semibold text-sm">{{ session('user_name') ?? 'User' }}</p>
                            <p class="text-red-100 text-xs">Student</p>
                        </div>
                        <a href="{{ route('auth.logout') }}" class="bg-red-800 hover:bg-red-900 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                @elseif(session('is_admin'))
                    <!-- Admin Nav -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <p class="text-white font-semibold text-sm">{{ session('admin_username') ?? 'Admin' }}</p>
                            <p class="text-red-100 text-xs">Administrator</p>
                        </div>
                        <a href="{{ route('auth.admin.logout') }}" class="bg-red-800 hover:bg-red-900 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                @else
                    <!-- Guest Links -->
                    <a href="{{ route('auth.login') }}" class="text-white hover:bg-red-500 px-4 py-2.5 rounded-lg text-sm font-medium transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                    <a href="{{ route('auth.admin.login') }}" class="bg-red-800 hover:bg-red-900 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>











