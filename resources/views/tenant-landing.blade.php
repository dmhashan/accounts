<x-guest-layout>
    <x-slot name="title">{{ $tenant->name }} - Transform Your Body</x-slot>
    
    <!-- Hero Section -->
    <div class="relative overflow-hidden" style="background-image: url('{{ asset('images/background.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="relative py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto text-center">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 via-primary-300 to-primary-500 mb-6">
                    ELEVATE YOUR LIFE
                </h1>
                <p class="text-xl sm:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Welcome to your transformation journey. Where strength meets dedication, and fitness becomes a lifestyle.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('register.form') }}" 
                       class="inline-block px-8 py-4 text-lg font-bold rounded-full text-white bg-gradient-to-r from-primary-500 to-primary-700 hover:from-primary-600 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-500/50 transition-all duration-300 shadow-2xl hover:shadow-primary-500/50 transform hover:scale-105 uppercase tracking-wide">
                        Join Now
                    </a>
                    <a href="{{ route('login.form') }}" 
                       class="inline-block px-8 py-4 text-lg font-bold rounded-full text-white border-2 border-white/30 bg-white/10 backdrop-blur-sm hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/20 transition-all duration-300 uppercase tracking-wide">
                        Member Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="py-16 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">About Our Gym</h2>
                <div class="w-24 h-1 bg-primary-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
                    Our state-of-the-art fitness facility is designed to help you achieve your fitness goals in a supportive and motivating environment. 
                    With premium equipment, expert guidance, and a community of like-minded individuals, we provide everything you need to transform your body and mind. 
                    Whether you're a beginner or an experienced athlete, our gym offers the perfect space to push your limits and reach new heights.
                </p>
            </div>
        </div>
    </div>

    <!-- Instructor Section -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Meet Your Instructor</h2>
                <div class="w-24 h-1 bg-primary-500 mx-auto"></div>
            </div>
            <div class="max-w-2xl mx-auto">
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-xl p-8 border border-gray-200">
                    <div class="text-center">
                        <div class="w-32 h-32 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <span class="text-4xl font-bold text-white">BK</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Bihath Kavishka</h3>
                        <p class="text-primary-600 font-semibold mb-4">Certified Personal Trainer</p>
                        <p class="text-gray-700 leading-relaxed">
                            Expert fitness professional dedicated to helping you achieve your goals through personalized training programs and nutritional guidance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Section -->
    <div class="py-16 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">World-Class Equipment</h2>
                <div class="w-24 h-1 bg-primary-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-700 max-w-3xl mx-auto">
                    Train with the best equipment designed for maximum performance and safety
                </p>
            </div>

            <!-- Cardio Zone -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    Cardio Zone
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Treadmill -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/treadmill.jpg') }}" 
                                 alt="Treadmill" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Treadmill';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Treadmill</h4>
                            <p class="text-sm text-gray-600 mt-1">Professional cardio equipment for running and walking</p>
                        </div>
                    </div>

                    <!-- Spin Bike -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/spin-bike.jpg') }}" 
                                 alt="Spin Bike" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Spin+Bike';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Spin Bike</h4>
                            <p class="text-sm text-gray-600 mt-1">High-intensity cycling for endurance training</p>
                        </div>
                    </div>

                    <!-- Upright Bike -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/upright-bike.jpg') }}" 
                                 alt="Upright Bike" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Upright+Bike';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Upright Bike</h4>
                            <p class="text-sm text-gray-600 mt-1">Low-impact cardiovascular exercise bike</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chest & Shoulder Training -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    Chest & Shoulder Training
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Chest Press Machine -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/chest-press.jpg') }}" 
                                 alt="Chest Press Machine" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Chest+Press';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Chest Press Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Build chest strength with guided movement</p>
                        </div>
                    </div>

                    <!-- Incline Chest Press -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/incline-chest-press.jpg') }}" 
                                 alt="Incline Chest Press" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Incline+Press';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Incline Chest Press Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Target upper chest muscles effectively</p>
                        </div>
                    </div>

                    <!-- Pec Deck -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/pec-deck.jpg') }}" 
                                 alt="Pec Deck" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Pec+Deck';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Pec Deck / Rear Delt Fly</h4>
                            <p class="text-sm text-gray-600 mt-1">Isolate chest and rear deltoid muscles</p>
                        </div>
                    </div>

                    <!-- Shoulder Press -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/shoulder-press.jpg') }}" 
                                 alt="Shoulder Press" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Shoulder+Press';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Shoulder Press Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Develop strong, defined shoulders</p>
                        </div>
                    </div>

                    <!-- Flat Bench -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/flat-bench.jpg') }}" 
                                 alt="Flat Bench" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Flat+Bench';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Flat Bench</h4>
                            <p class="text-sm text-gray-600 mt-1">Essential for various pressing exercises</p>
                        </div>
                    </div>

                    <!-- Adjustable Bench -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/adjustable-bench.jpg') }}" 
                                 alt="Adjustable Bench" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Adjustable+Bench';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Adjustable Bench</h4>
                            <p class="text-sm text-gray-600 mt-1">Versatile bench for multiple angles</p>
                        </div>
                    </div>

                    <!-- Flat Bench with Rack -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/flat-bench-rack.jpg') }}" 
                                 alt="Flat Bench with Rack" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Flat+Bench+Rack';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Flat Bench with Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Safe barbell bench pressing station</p>
                        </div>
                    </div>

                    <!-- Incline Bench with Rack -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/incline-bench-rack.jpg') }}" 
                                 alt="Incline Bench with Rack" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Incline+Bench+Rack';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Incline Bench with Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Incline pressing with barbell support</p>
                        </div>
                    </div>

                    <!-- Decline Bench with Rack -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/decline-bench-rack.jpg') }}" 
                                 alt="Decline Bench with Rack" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Decline+Bench+Rack';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Decline Bench with Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Target lower chest with decline angle</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back & Pull Training -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                    </div>
                    Back & Pull Training
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Pull-Up Machine -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/pullup-machine.jpg') }}" 
                                 alt="Assisted Pull-Up Machine" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Pull-Up+Machine';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Assisted Pull-Up Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Build back strength with assistance</p>
                        </div>
                    </div>

                    <!-- Lat Pulldown -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/lat-pulldown.jpg') }}" 
                                 alt="Lat Pulldown" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Lat+Pulldown';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Lat Pulldown (Multi-Station)</h4>
                            <p class="text-sm text-gray-600 mt-1">Develop wide, strong lats</p>
                        </div>
                    </div>

                    <!-- Seated Row -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/seated-row.jpg') }}" 
                                 alt="Seated Row" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Seated+Row';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Seated Row</h4>
                            <p class="text-sm text-gray-600 mt-1">Build thick, powerful back muscles</p>
                        </div>
                    </div>

                    <!-- Cable Crossover -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/cable-crossover.jpg') }}" 
                                 alt="Cross-Over Cable System" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Cable+Crossover';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Cross-Over Cable System</h4>
                            <p class="text-sm text-gray-600 mt-1">Versatile cable system for all angles</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leg & Glute Training -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </div>
                    Leg & Glute Training
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Squat Rack -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/squat-rack.jpg') }}" 
                                 alt="Squat Rack" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Squat+Rack';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Squat Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Build powerful legs with free weight squats</p>
                        </div>
                    </div>

                    <!-- Smith Machine -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/smith-machine.jpg') }}" 
                                 alt="Smith Machine" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Smith+Machine';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Smith Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Guided barbell movements for safety</p>
                        </div>
                    </div>

                    <!-- Pendulum Squat -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/pendulum-squat.jpg') }}" 
                                 alt="Pendulum Squat" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Pendulum+Squat';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Pendulum Squat</h4>
                            <p class="text-sm text-gray-600 mt-1">Deep squat movement with natural arc</p>
                        </div>
                    </div>

                    <!-- Super Squat -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/super-squat.jpg') }}" 
                                 alt="Super Squat" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Super+Squat';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Super Squat</h4>
                            <p class="text-sm text-gray-600 mt-1">Advanced squat training machine</p>
                        </div>
                    </div>

                    <!-- Leg Press -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/leg-press.jpg') }}" 
                                 alt="Leg Press" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Leg+Press';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Leg Press</h4>
                            <p class="text-sm text-gray-600 mt-1">Heavy leg training with back support</p>
                        </div>
                    </div>

                    <!-- Leg Extension -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/leg-extension.jpg') }}" 
                                 alt="Leg Extension" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Leg+Extension';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Leg Extension</h4>
                            <p class="text-sm text-gray-600 mt-1">Isolate and strengthen quadriceps</p>
                        </div>
                    </div>

                    <!-- Leg Curl -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/leg-curl.jpg') }}" 
                                 alt="Leg Curl" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Leg+Curl';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Leg Curl</h4>
                            <p class="text-sm text-gray-600 mt-1">Target hamstrings effectively</p>
                        </div>
                    </div>

                    <!-- Hip Thrust Machine -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/hip-thrust.jpg') }}" 
                                 alt="Hip Thrust Machine" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Hip+Thrust';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Hip Thrust Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Build strong, powerful glutes</p>
                        </div>
                    </div>

                    <!-- Abductor -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/abductor.jpg') }}" 
                                 alt="Abductor" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Abductor';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Abductor</h4>
                            <p class="text-sm text-gray-600 mt-1">Strengthen outer thighs and hips</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Free Weights -->
            <div class="mb-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    Free Weights
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Dumbbells -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/dumbbells.jpg') }}" 
                                 alt="Dumbbells" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Dumbbells';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Dumbbells with Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Complete set from 2.5 kg to 30 kg</p>
                        </div>
                    </div>

                    <!-- Fixed Barbells -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/barbells.jpg') }}" 
                                 alt="Fixed Barbells" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Barbells';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Fixed Barbells with Rack</h4>
                            <p class="text-sm text-gray-600 mt-1">Pre-loaded barbells from 10 kg to 30 kg</p>
                        </div>
                    </div>

                    <!-- Weight Plates -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/weight-plates.jpg') }}" 
                                 alt="Olympic Weight Plates" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Weight+Plates';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Olympic Weight Plates</h4>
                            <p class="text-sm text-gray-600 mt-1">Premium plates for barbell training</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Core & Functional Training -->
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    Core & Functional Training
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Back Extension -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="aspect-w-16 aspect-h-12 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img src="{{ asset('images/equipment/back-extension.jpg') }}" 
                                 alt="Back Extension Machine" 
                                 class="w-full h-48 object-cover"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/6366f1/ffffff?text=Back+Extension';">
                        </div>
                        <div class="p-4">
                            <h4 class="text-lg font-bold text-gray-900">Back Extension Machine</h4>
                            <p class="text-sm text-gray-600 mt-1">Strengthen lower back and core</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div class="py-16 bg-gradient-to-b from-gray-900 to-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Membership Packages</h2>
                <div class="w-24 h-1 bg-primary-500 mx-auto mb-6"></div>
                <p class="text-xl text-gray-300">
                    No Registration or Admission Fee - Start Your Journey Today!
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Monthly Package -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 transform hover:scale-105 transition-transform duration-300 border-4 border-primary-500">
                    <div class="text-center">
                        <div class="bg-primary-500 text-white text-sm font-bold uppercase px-4 py-2 rounded-full inline-block mb-4">
                            Most Popular
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Monthly</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">3000</span>
                            <span class="text-gray-600 text-xl">/month</span>
                        </div>
                        <ul class="space-y-4 mb-8 text-left">
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Unlimited gym access
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                All equipment included
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                No hidden fees
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Cancel anytime
                            </li>
                        </ul>
                        <a href="{{ route('register.form') }}" 
                           class="block w-full px-6 py-4 text-lg font-bold rounded-xl text-white bg-gradient-to-r from-primary-500 to-primary-700 hover:from-primary-600 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-500/50 transition-all duration-300 text-center uppercase tracking-wide">
                            Get Started
                        </a>
                    </div>
                </div>

                <!-- Day Pass -->
                <div class="bg-white rounded-2xl shadow-xl p-8 transform hover:scale-105 transition-transform duration-300">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 mt-10">Day Pass</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">1000</span>
                            <span class="text-gray-600 text-xl">/day</span>
                        </div>
                        <ul class="space-y-4 mb-8 text-left">
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Full day access
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                All equipment included
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Perfect for visitors
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Try before you commit
                            </li>
                        </ul>
                        <a href="{{ route('register.form') }}" 
                           class="block w-full px-6 py-4 text-lg font-bold rounded-xl text-primary-600 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-300 transition-all duration-300 text-center uppercase tracking-wide">
                            Buy Day Pass
                        </a>
                    </div>
                </div>

                <!-- Personal Training -->
                <div class="bg-white rounded-2xl shadow-xl p-8 transform hover:scale-105 transition-transform duration-300">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 mt-10">Personal Training</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">1000</span>
                            <span class="text-gray-600 text-xl">/session</span>
                        </div>
                        <ul class="space-y-4 mb-8 text-left">
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                One-on-one training
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Customized workout plan
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Expert guidance
                            </li>
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Achieve goals faster
                            </li>
                        </ul>
                        <a href="{{ route('register.form') }}" 
                           class="block w-full px-6 py-4 text-lg font-bold rounded-xl text-primary-600 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-300 transition-all duration-300 text-center uppercase tracking-wide">
                            Book Session
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="py-16 bg-gradient-to-r from-primary-600 to-primary-800">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">Ready to Transform Your Life?</h2>
            <p class="text-xl text-primary-100 mb-8">Join our community today and start your fitness journey with expert guidance and world-class facilities.</p>
            <a href="{{ route('register.form') }}" 
               class="inline-block px-10 py-5 text-xl font-bold rounded-full text-primary-600 bg-white hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-white/50 transition-all duration-300 shadow-2xl transform hover:scale-105 uppercase tracking-wide">
                Join Now
            </a>
        </div>
    </div>
</x-guest-layout>
