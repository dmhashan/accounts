<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Coming Soon</x-slot>
    
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 relative overflow-hidden" style="background-image: url('{{ asset('images/background.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <!-- Dark Overlay -->
        <div class="absolute inset-0 bg-black/60"></div>
        
        <!-- Main Content -->
        <div class="relative max-w-4xl w-full text-center">            
            <!-- Tagline -->
            <div class="mb-8">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 via-primary-300 to-primary-500 animate-pulse">
                    ELEVATE YOUR LIFE.
                </h2>
            </div>
            
            <!-- Coming Soon Badge -->
            <div class="inline-block mb-6">
                <span class="px-6 py-2 bg-primary-500/20 border border-primary-400 rounded-full text-primary-300 text-sm font-semibold uppercase tracking-wider backdrop-blur-sm">
                    Coming Soon
                </span>
            </div>
            
            <!-- Description -->
            <p class="text-lg sm:text-xl md:text-2xl text-gray-300 mb-6 max-w-3xl mx-auto leading-relaxed">
                Something powerful is coming to your town.
            </p>
            <p class="text-base sm:text-lg md:text-xl text-gray-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                A new standard of strength, community, and personal growth is just around the corner.
            </p>
            
            <!-- Main CTA -->
            <div class="mb-8">
                <a href="{{ route('register.form') }}" 
                   class="inline-block px-8 sm:px-12 py-4 sm:py-5 text-lg sm:text-xl font-bold rounded-full text-white bg-gradient-to-r from-primary-500 to-primary-700 hover:from-primary-600 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-500/50 transition-all duration-300 shadow-2xl hover:shadow-primary-500/50 transform hover:scale-105 hover:-translate-y-1 uppercase tracking-wide">
                    Join With Us For Early Access
                </a>
            </div>
            
            <!-- Secondary CTAs -->
            <div class="max-w-md mx-auto mt-8">
                <div class="bg-white/5 backdrop-blur-md rounded-2xl px-6 py-4 border border-white/10 shadow-xl">
                    <p class="text-gray-300 text-base">
                        Are you already a member? 
                        <a href="{{ route('login.form') }}" class="text-primary-400 hover:text-primary-300 font-bold ml-1 transition-all duration-200 hover:underline underline-offset-4">
                            Login here →
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
        }
    </style>
</x-guest-layout>
