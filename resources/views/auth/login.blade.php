@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 transition-colors duration-300">
        
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center text-white font-extrabold text-2xl shadow-lg mb-4 rotate-3">
                NS
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Selamat Datang</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Masuk ke akun NSPremium Anda</p>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                <div class="mt-1 relative">
                    <input id="email" name="email" type="email" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl placeholder-gray-400 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 transition-shadow" placeholder="nama@email.com">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <a href="#" class="text-sm font-medium text-orange-500 hover:text-orange-400">Lupa sandi?</a>
                </div>
                <div class="mt-1 relative">
                    <input id="password" name="password" type="password" required class="appearance-none block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl placeholder-gray-400 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 transition-shadow" placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center">
                <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-orange-500 focus:ring-orange-500 border-gray-300 rounded dark:bg-gray-700">
                <label for="remember_me" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Ingat Saya</label>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                    Masuk ke Dashboard
                </button>
            </div>
        </form>

        <!-- Link Daftar Reseller -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Belum memiliki akun? 
                <a href="{{ route('register') }}" class="font-bold text-orange-500 hover:text-orange-600 transition">Daftar Reseller</a>
            </p>
        </div>

    </div>
</div>
@endsection