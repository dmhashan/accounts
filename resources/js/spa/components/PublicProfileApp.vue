<template>
    <div class="min-h-screen bg-[#f5f5f5] flex flex-col">

        <!-- ══════════════════════════════════════════════════
             LOADING SCREEN
        ═══════════════════════════════════════════════════ -->
        <div v-if="screen === 'loading'" class="flex-1 flex items-center justify-center">
            <div class="flex flex-col items-center gap-3 text-gray-400">
                <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                </svg>
                <p class="text-sm">Loading&hellip;</p>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             IDENTIFY SCREEN — enter mobile number
        ═══════════════════════════════════════════════════ -->
        <div v-else-if="screen === 'identify'" class="flex-1 flex items-center justify-center px-5">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-900 mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Member Portal</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ tenantName }}</p>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 mb-5 text-center">Enter your registered mobile number to continue.</p>

                    <div v-if="error" class="mb-4 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-sm text-red-600">
                        {{ error }}
                    </div>

                    <form @submit.prevent="requestOtp" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Mobile Number</label>
                            <input
                                v-model="phone"
                                type="tel"
                                inputmode="tel"
                                placeholder="e.g. 0771234567"
                                class="w-full rounded-2xl border border-gray-200 px-4 py-3.5 text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                autocomplete="tel"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isLoading"
                            class="w-full py-3.5 rounded-2xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 active:bg-black disabled:opacity-60 transition-colors"
                        >
                            <span v-if="isLoading">Sending OTP&hellip;</span>
                            <span v-else>Send OTP</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             OTP SCREEN — enter the code
        ═══════════════════════════════════════════════════ -->
        <div v-else-if="screen === 'otp'" class="flex-1 flex items-center justify-center px-5">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-900 mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Verify OTP</h1>
                    <p class="text-sm text-gray-500 mt-1">Code sent to {{ phone }}</p>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-600 mb-5 text-center">Enter the 6-digit code we sent via SMS.</p>

                    <div v-if="error" class="mb-4 px-4 py-3 rounded-2xl bg-red-50 border border-red-100 text-sm text-red-600">
                        {{ error }}
                    </div>

                    <form @submit.prevent="verifyOtp" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Verification Code</label>
                            <input
                                v-model="otpCode"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                class="w-full rounded-2xl border border-gray-200 px-4 py-3.5 text-sm font-bold text-gray-900 text-center tracking-[0.5em] placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                autocomplete="one-time-code"
                                required
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isLoading || otpCode.length < 6"
                            class="w-full py-3.5 rounded-2xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800 active:bg-black disabled:opacity-60 transition-colors"
                        >
                            <span v-if="isLoading">Verifying&hellip;</span>
                            <span v-else>Verify &amp; Continue</span>
                        </button>
                        <button
                            type="button"
                            class="w-full py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors"
                            @click="backToIdentify"
                        >
                            Change number
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Scrollable body (profile screens) ──────────── -->
        <template v-else>
        <main class="flex-1 overflow-y-auto pb-28">
            <div class="max-w-lg mx-auto px-5">

                <!-- ═══════════════════════════════════════════
                     HOME VIEW
                ════════════════════════════════════════════ -->
                <div v-show="activeNav === 'home'">

                    <!-- Header -->
                    <div class="flex items-center justify-between pt-12 pb-6">
                        <div>
                            <p class="text-sm text-gray-400 leading-none mb-1">{{ greeting }},</p>
                            <h1 class="text-2xl font-bold text-gray-900 leading-tight tracking-tight">{{ firstName }}</h1>
                            <p class="text-xs text-gray-400 mt-0.5">Welcome to {{ meta.tenant_name }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-2xl bg-gray-900 flex items-center justify-center text-sm font-bold text-white select-none shadow-sm">
                            {{ initials }}
                        </div>
                    </div>

                    <!-- ── Outstanding balance card ────────── -->
                    <div v-if="parseFloat(meta.total_outstanding) > 0" class="mb-5 bg-white rounded-3xl px-6 py-5 shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-400 mb-1">Total outstanding</p>
                        <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ meta.total_outstanding }}</p>
                        <div class="mt-1 inline-flex items-center gap-1 bg-red-50 text-red-500 text-xs font-semibold px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                            Unpaid balance
                        </div>
                    </div>

                    <!-- ── Latest Workout Plan (card style) ── -->
                    <section v-if="workoutsData.length" class="mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold text-gray-900">Workout Plan</h2>
                            <button v-if="workoutsData.length > 1" type="button" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition-colors" @click="activeNav = 'workout'">
                                See all ({{ workoutsData.length }})
                            </button>
                        </div>
                        <!-- Debit-card style hero -->
                        <button
                            type="button"
                            class="w-full text-left rounded-3xl overflow-hidden focus:outline-none active:scale-[0.99] transition-transform"
                            @click="openWorkout(workoutsData[0])"
                        >
                            <!-- Card face — lime background with subtle pattern -->
                            <div class="relative px-6 pt-6 pb-5 overflow-hidden" style="background:#1a1a1a; min-height:160px;">
                                <!-- Decorative circles (like card texture) -->
                                <div class="absolute -bottom-10 -right-10 w-44 h-44 rounded-full opacity-20 bg-black pointer-events-none"></div>
                                <div class="absolute -top-6 -left-6 w-28 h-28 rounded-full opacity-10 bg-black pointer-events-none"></div>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-red-500 uppercase tracking-widest mb-2">Active Plan</p>
                                        <h3 class="text-xl font-bold text-white leading-tight max-w-[200px]">{{ workoutsData[0].title }}</h3>
                                        <p v-if="workoutsData[0].creator_name" class="text-xs text-gray-400 mt-1">by {{ workoutsData[0].creator_name }}</p>
                                    </div>
                                    <div class="w-10 h-10 rounded-2xl bg-red-500 flex items-center justify-center mt-0.5">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                </div>
                                <div class="flex items-center gap-5 mt-5">
                                    <div>
                                        <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Duration</p>
                                        <p class="text-sm font-bold text-white">{{ workoutsData[0].duration_weeks || '-' }} wks</p>
                                    </div>
                                    <div class="w-px h-6 bg-white/10"></div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Start</p>
                                        <p class="text-sm font-bold text-white">{{ workoutsData[0].effective_date || '-' }}</p>
                                    </div>
                                    <div class="w-px h-6 bg-white/10"></div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 mb-0.5 uppercase tracking-wider">Days</p>
                                        <p class="text-sm font-bold text-white">{{ workoutsData[0].days?.length || '-' }}</p>
                                    </div>
                                    <div class="ml-auto">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </section>

                    <!-- ── Transactions ─────────────────────── -->
                    <section v-if="salesData.length" class="mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold text-gray-900">Transactions</h2>
                            <button v-if="salesData.length > 10" type="button" class="text-xs font-semibold text-gray-500 hover:text-gray-800 transition-colors" @click="activeNav = 'transactions'">
                                See all
                            </button>
                        </div>
                        <div class="bg-white rounded-3xl overflow-hidden divide-y divide-gray-50 shadow-sm border border-gray-100">
                            <button
                                v-for="(sale, i) in salesData.slice(0, 10)"
                                :key="i"
                                type="button"
                                class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 active:bg-gray-100 transition-colors focus:outline-none text-left"
                                @click="openSale(sale)"
                            >
                                <div class="flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center bg-gray-100">
                                    <svg class="w-4.5 h-4.5 text-gray-500" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-6-8h6M5 8h.01M5 12h.01M5 16h.01M9 4H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1h-5"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">Invoice #{{ sale.id }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ sale.created_at }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold" :class="!sale.is_paid ? 'text-gray-900' : 'text-gray-900'">{{ sale.total_amount }}</p>
                                    <span v-if="!sale.is_paid" class="inline-block text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full mt-0.5">Unpaid</span>
                                    <span v-else class="inline-block text-[10px] font-bold text-green-700 bg-[#dcfce7] px-1.5 py-0.5 rounded-full mt-0.5">Paid</span>
                                </div>
                            </button>
                        </div>
                        <button v-if="salesData.length > 10" type="button" class="mt-3 w-full py-3.5 text-sm font-bold text-gray-900 bg-gray-900 text-white rounded-2xl hover:bg-gray-800 active:bg-black transition-colors" @click="activeNav = 'transactions'">
                            View all {{ salesData.length }} transactions
                        </button>
                    </section>

                    <!-- no data at all -->
                    <div v-if="!workoutsData.length && !salesData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p class="text-sm text-gray-400">No data yet</p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     WORKOUT VIEW
                ════════════════════════════════════════════ -->
                <div v-show="activeNav === 'workout'">
                    <div class="flex items-center gap-3 pt-12 pb-6">
                        <h1 class="text-xl font-bold text-gray-900">Workout Plans</h1>
                    </div>
                    <div v-if="!workoutsData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <p class="text-sm text-gray-400">No workout plans assigned</p>
                    </div>
                    <div v-else class="space-y-2.5 pb-4">
                        <button
                            v-for="(workout, i) in workoutsData"
                            :key="i"
                            type="button"
                            class="w-full text-left bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex items-center gap-4 hover:shadow-md active:scale-[0.99] transition-all focus:outline-none"
                            @click="openWorkout(workout)"
                        >
                            <div class="flex-shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center" :style="i === 0 ? 'background:#ef4444' : 'background:#f5f5f5'">
                                <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ workout.title }}</p>
                                    <span v-if="i === 0" class="shrink-0 text-[10px] font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">{{ workout.duration_weeks || '-' }} weeks · {{ workout.effective_date || '-' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     TRANSACTIONS VIEW
                ════════════════════════════════════════════ -->
                <div v-show="activeNav === 'transactions'">
                    <div class="flex items-center gap-3 pt-12 pb-6">
                        <h1 class="text-xl font-bold text-gray-900">Transactions</h1>
                        <span v-if="parseFloat(meta.total_outstanding) > 0" class="ml-auto shrink-0 text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-2.5 py-1 rounded-full">
                            Due: {{ meta.total_outstanding }}
                        </span>
                    </div>
                    <div v-if="!salesData.length" class="flex flex-col items-center justify-center py-20 gap-3 text-gray-300">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-sm text-gray-400">No transactions found</p>
                    </div>
                    <div v-else class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50 mb-4">
                        <button
                            v-for="(sale, i) in salesData"
                            :key="i"
                            type="button"
                            class="w-full flex items-center gap-4 px-5 py-4 hover:bg-gray-50 active:bg-gray-100 transition-colors focus:outline-none text-left"
                            @click="openSale(sale)"
                        >
                            <div class="flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center bg-gray-100">
                                <svg style="width:18px;height:18px" class="text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m-6-8h6M5 8h.01M5 12h.01M5 16h.01M9 4H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1h-5"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900">Invoice #{{ sale.id }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ sale.created_at }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-gray-900">{{ sale.total_amount }}</p>
                                <span v-if="!sale.is_paid" class="inline-block text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full mt-0.5">Unpaid</span>
                                <span v-else class="inline-block text-[10px] font-bold text-green-700 bg-[#dcfce7] px-1.5 py-0.5 rounded-full mt-0.5">Paid</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     PROFILE VIEW
                ════════════════════════════════════════════ -->
                <div v-show="activeNav === 'profile'">
                    <!-- Avatar block -->
                    <div class="flex flex-col items-center pb-6 pt-4">
                        <div class="w-24 h-24 rounded-full bg-gray-900 flex items-center justify-center text-3xl font-bold text-white select-none shadow-md border-4 border-white">
                            {{ initials }}
                        </div>
                        <h2 class="mt-4 text-xl font-bold text-gray-900 tracking-tight">{{ meta.name }}</h2>
                        <p class="text-sm text-gray-400 mt-0.5">@{{ meta.username }}</p>
                        <span v-if="meta.member_role" class="mt-3 text-xs font-semibold text-gray-600 bg-white border border-gray-200 px-3 py-1 rounded-full shadow-sm">
                            {{ meta.member_role }}
                        </span>
                    </div>

                    <!-- Personal info -->
                    <section class="mb-4">
                        <div class="flex items-center justify-between px-1 mb-3">
                            <h3 class="text-base font-bold text-gray-900">Personal info</h3>
                        </div>
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Name</p>
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.name }}</p>
                                </div>
                            </div>
                            <div v-if="meta.email" class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">E-mail</p>
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.email }}</p>
                                </div>
                            </div>
                            <div v-if="meta.phone_number" class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Phone number</p>
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ meta.phone_number }}</p>
                                </div>
                            </div>
                            <div v-if="meta.gender" class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8" fill="none"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12v8m-3-3h6"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Gender</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ capitalize(meta.gender) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8" fill="none"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="1.8"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Member since</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ meta.joined_date ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Account info quick stats -->
                    <section class="mb-4">
                        <h3 class="text-base font-bold text-gray-900 px-1 mb-3">Account info</h3>
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Workout Plans</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ workoutsData.length }} assigned</p>
                                </div>
                                <span v-if="workoutsData.length" class="text-xs font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">Active</span>
                            </div>
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-400 leading-none mb-0.5">Transactions</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ salesData.length }} total</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Logout -->
                    <section class="mb-6">
                        <button
                            type="button"
                            class="w-full py-3.5 rounded-2xl border border-red-200 bg-red-50 text-red-600 text-sm font-bold hover:bg-red-100 active:bg-red-200 transition-colors"
                            @click="logout"
                        >
                            Sign out
                        </button>
                    </section>
                </div>

            </div>
        </main>

        <!-- ── Bottom Navigation Bar ──────────────────────── -->
        <nav class="fixed bottom-0 inset-x-0 z-20 bg-white border-t border-gray-100 safe-area-bottom">
            <div class="max-w-lg mx-auto flex">
                <button
                    v-for="tab in navTabs"
                    :key="tab.key"
                    type="button"
                    class="flex-1 flex flex-col items-center justify-center gap-1 py-3 transition-colors focus:outline-none"
                    :class="activeNav === tab.key ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
                    @click="activeNav = tab.key"
                >
                    <component :is="tab.icon" class="w-5 h-5" />
                    <span class="text-[10px] font-semibold leading-none">{{ tab.label }}</span>
                    <span v-if="activeNav === tab.key" class="w-5 h-0.5 rounded-full bg-red-500 mt-0.5"></span>
                    <span v-else class="w-5 h-0.5 mt-0.5"></span>
                </button>
            </div>
        </nav>

        <!-- ── Workout Preview Modal ──────────────────────── -->
        <Teleport to="body">
            <div v-if="activeWorkout" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeWorkout">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeWorkout"></div>
                <div class="relative w-full max-w-4xl my-4">
                    <div class="flex justify-end mb-2">
                        <button type="button" class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors" @click="closeWorkout" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <WorkoutProgramPreviewCard :program="activeWorkout" />
                </div>
            </div>
        </Teleport>

        <!-- ── Sale Invoice Preview Modal ─────────────────── -->
        <Teleport to="body">
            <div v-if="activeSale" class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-6 overflow-y-auto" @keydown.escape.window="closeSale">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeSale"></div>
                <div class="relative w-full max-w-2xl my-4">
                    <div class="flex justify-end mb-2">
                        <button type="button" class="p-2 rounded-xl bg-white text-gray-500 hover:text-gray-700 shadow-md transition-colors" @click="closeSale" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <SaleInvoicePreviewCard :sale="activeSale" />
                </div>
            </div>
        </Teleport>
    </template>
    </div>
</template>

<script setup>
import { ref, computed, h, onMounted } from 'vue';
import WorkoutProgramPreviewCard from './WorkoutProgramPreviewCard.vue';
import SaleInvoicePreviewCard from './SaleInvoicePreviewCard.vue';

const MEMBER_ID_KEY = 'public_profile_member_id';

// ── Identification state ───────────────────────────────────
const screen    = ref('loading'); // 'loading' | 'identify' | 'otp' | 'profile'
const phone     = ref('');
const otpCode   = ref('');
const error     = ref('');
const isLoading = ref(false);

// ── Profile data ───────────────────────────────────────────
const workoutsData = ref([]);
const salesData    = ref([]);
const meta         = ref({});

const tenantName = computed(() => window.__tenantName || '');

// ── Nav state ──────────────────────────────────────────────
const activeNav     = ref('home');
const activeWorkout = ref(null);
const activeSale    = ref(null);

// ── Bootstrap ──────────────────────────────────────────────
onMounted(async () => {
    const memberId = localStorage.getItem(MEMBER_ID_KEY);
    if (memberId) {
        await loadProfile(memberId);
    } else {
        screen.value = 'identify';
    }
});

// ── CSRF helper ────────────────────────────────────────────
function getCsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// ── Identification actions ─────────────────────────────────
async function requestOtp() {
    error.value = '';
    isLoading.value = true;
    try {
        const res = await fetch('/api/public/request-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ phone_number: phone.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.message || 'Something went wrong.';
            return;
        }
        error.value = '';
        screen.value = 'otp';
    } catch {
        error.value = 'Network error. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

async function verifyOtp() {
    error.value = '';
    isLoading.value = true;
    try {
        const res = await fetch('/api/public/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ phone_number: phone.value, otp: otpCode.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.message || 'Invalid OTP.';
            return;
        }
        localStorage.setItem(MEMBER_ID_KEY, data.member_id);
        await loadProfile(data.member_id);
    } catch {
        error.value = 'Network error. Please try again.';
    } finally {
        isLoading.value = false;
    }
}

async function loadProfile(memberId) {
    isLoading.value = true;
    try {
        const res = await fetch(`/api/public/member-profile/${memberId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
            localStorage.removeItem(MEMBER_ID_KEY);
            screen.value = 'identify';
            return;
        }
        const data = await res.json();
        meta.value         = data.meta;
        workoutsData.value = data.workouts;
        salesData.value    = data.sales;
        screen.value       = 'profile';
    } catch {
        localStorage.removeItem(MEMBER_ID_KEY);
        screen.value = 'identify';
    } finally {
        isLoading.value = false;
    }
}

function backToIdentify() {
    otpCode.value = '';
    error.value   = '';
    screen.value  = 'identify';
}

// ── Computed ───────────────────────────────────────────────
const initials = computed(() => {
    const parts = (meta.value.name || '').trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return (parts[0]?.[0] ?? '?').toUpperCase();
});

const firstName = computed(() => {
    return (meta.value.name || '').trim().split(/\s+/)[0] || meta.value.name || '';
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h >= 5  && h < 12) return 'Good morning';
    if (h >= 12 && h < 18) return 'Good afternoon';
    if (h >= 18 && h < 22) return 'Good evening';
    return 'Hello';
});

// ── Nav icon components ────────────────────────────────────
const IconHome = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' }),
    ]),
};
const IconWorkout = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M13 10V3L4 14h7v7l9-11h-7z' }),
    ]),
};
const IconTransactions = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }),
    ]),
};
const IconProfile = {
    render: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-5 h-5' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.8', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }),
    ]),
};

const navTabs = [
    { key: 'home',         label: 'Home',        icon: IconHome },
    { key: 'workout',      label: 'Workout',      icon: IconWorkout },
    { key: 'transactions', label: 'Transactions', icon: IconTransactions },
    { key: 'profile',      label: 'Profile',      icon: IconProfile },
];

// ── Actions ────────────────────────────────────────────────
function openWorkout(workout)  { activeWorkout.value = workout; }
function closeWorkout()        { activeWorkout.value = null; }
function openSale(sale)        { activeSale.value = sale; }
function closeSale()           { activeSale.value = null; }

function capitalize(val) {
    if (!val) return '-';
    return val.charAt(0).toUpperCase() + val.slice(1);
}

function logout() {
    localStorage.removeItem(MEMBER_ID_KEY);
    meta.value         = {};
    workoutsData.value = [];
    salesData.value    = [];
    phone.value        = '';
    otpCode.value      = '';
    error.value        = '';
    activeNav.value    = 'home';
    screen.value       = 'identify';
}
</script>
