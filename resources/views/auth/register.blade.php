@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 transition-colors duration-300">
        
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Daftar Reseller</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mulai bisnis digital Anda bersama NSPremium</p>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 text-red-600 p-3 rounded-lg text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="space-y-4" action="{{ route('register') }}" method="POST">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                <input name="name" type="text" required class="mt-1 w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                <input name="email" type="email" required class="mt-1 w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input name="password" type="password" required class="mt-1 w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password</label>
                <input name="password_confirmation" type="password" required class="mt-1 w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-green-500 hover:bg-green-600 transition-colors">
                    Daftar Sekarang
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Sudah punya akun? 
                <a href="{{ route('login') }}" class="font-bold text-orange-500 hover:text-orange-600 transition">Login di sini</a>
            </p>
        </div>
    </div>
</div>
@endsection