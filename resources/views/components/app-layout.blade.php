<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Vehicle Registration System') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Poppins', 'sans-serif'],
                },
                extend: {
                    colors: {
                        primary: '#d00000',
                        'primary-dark': '#b00000',
                    }
                }
            }
        }
    </script>

    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            min-height: 100vh;
        }

        /* Modern Button Styles */
        .btn-primary {
            @apply bg-primary hover:bg-primary-dark text-white font-semibold py-2.5 px-6 rounded-lg transition duration-200 ease-in-out transform hover:scale-105 shadow-md hover:shadow-lg;
        }

        .btn-secondary {
            @apply bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 px-6 rounded-lg transition duration-200 ease-in-out;
        }

        /* Card Styles */
        .card {
            @apply bg-white rounded-xl shadow-md hover:shadow-lg transition duration-300 p-6 border border-gray-100;
        }

        .card-elevated {
            @apply card shadow-lg;
        }

        /* Input Styles */
        .input-field {
            @apply w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200 text-gray-900 placeholder-gray-400;
        }

        /* Alert Styles */
        .alert-success {
            @apply bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-4 rounded-r-lg;
        }

        .alert-error {
            @apply bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-4 rounded-r-lg;
        }

        .alert-warning {
            @apply bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 px-4 py-4 rounded-r-lg;
        }
    </style>

    {{ $styles ?? '' }}
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        @include('layouts.navigation')

        <!-- Page Header -->
        @if (isset($header))
            <header class="bg-white border-b border-gray-200 shadow-sm">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center">
                        <div>
                            {{ $header }}
                        </div>
                    </div>
                </div>
            </header>
        @endif

        <!-- Messages Section -->
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-4">
            <!-- Success Message -->
            @if (session('success'))
                <div class="alert-success mb-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-700 hover:text-green-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="alert-error mb-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-700 hover:text-red-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="alert-error mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 mt-0.5"></i>
                        <div>
                            <h3 class="font-semibold mb-2">Please fix the following errors:</h3>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <img class="h-8 w-auto" src="{{ asset('assets/images/AULogo.png') }}" alt="AU Logo">
                        <span class="text-gray-600 text-sm">Vehicle Registration System</span>
                    </div>
                    <p class="text-gray-500 text-sm">© {{ date('Y') }} Africa University. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
    {{ $scripts ?? '' }}
</body>
</html>
