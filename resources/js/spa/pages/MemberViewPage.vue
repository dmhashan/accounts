<template>
    <section class="app-page-frame">
        <div class="app-page-scroll">
        <div class="max-w-4xl mx-auto px-0 pb-8 space-y-4">

        <!-- Alerts -->
        <div v-if="errorMessage" class="mx-4 mt-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-200">
            {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="mx-4 mt-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-200">
            {{ successMessage }}
        </div>

        <div v-if="loading" class="mt-8 p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">Loading...</div>

        <template v-else-if="member">

            <!-- ── Hero Card ── -->
            <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-lg overflow-hidden border border-secondary-200 dark:border-secondary-700 mx-0">

                <!-- Gradient banner -->
                <div class="relative bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-600 px-4 pt-4 pb-7">
                    <!-- Decorative shapes -->
                    <div class="absolute inset-0 overflow-hidden pointer-events-none">
                        <div class="absolute -top-8 -right-8 w-48 h-48 rounded-full bg-white/5"></div>
                        <div class="absolute -bottom-6 left-8 w-32 h-32 rounded-full bg-white/5"></div>
                        <div class="absolute top-4 right-28 w-16 h-16 rounded-full bg-white/5"></div>
                    </div>

                    <!-- Top bar: back + actions -->
                    <div class="relative flex items-center justify-between gap-2 mb-5">
                        <RouterLink to="/members" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/15 hover:bg-white/25 border border-white/20 text-white transition-colors" title="Back to Members">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </RouterLink>

                        <div v-if="permissions.edit || permissions.delete" class="flex flex-wrap items-center justify-end gap-1.5">
                            <RouterLink
                                v-if="permissions.edit"
                                :to="`/members/${member.id}/edit`"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </RouterLink>
                            <button
                                v-if="permissions.edit"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="Boolean(actionInProgress)"
                                @click="toggleStatus"
                            >{{ actionInProgress === 'status' ? '...' : activeActionLabel }}</button>
                            <button
                                v-if="permissions.edit"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 border border-white/25 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="Boolean(actionInProgress)"
                                @click="toggleVerification"
                            >{{ actionInProgress === 'verification' ? '...' : verificationActionLabel }}</button>
                            <button
                                v-if="permissions.delete"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500/30 hover:bg-red-500/50 border border-red-300/30 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="Boolean(actionInProgress)"
                                @click="removeMember"
                            >{{ actionInProgress === 'delete' ? 'Deleting...' : 'Delete' }}</button>
                        </div>
                    </div>

                    <!-- Avatar + name on banner -->
                    <div class="relative flex flex-col sm:flex-row sm:items-end gap-3">
                        <div class="h-20 w-20 shrink-0 rounded-2xl bg-white/20 border-2 border-white/35 flex items-center justify-center shadow-xl">
                            <span class="text-2xl font-bold text-white">{{ initials }}</span>
                        </div>
                        <div class="sm:pb-0.5 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-bold text-white leading-tight">{{ fullName }}</h1>
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-white/15 border border-white/25 text-white">{{ normalizedGender }}</span>
                            </div>
                            <p class="mt-1 text-xs text-primary-100/90 tracking-wide">
                                {{ member.member_id }}<span v-if="member.username" class="ml-2 opacity-70">@{{ member.username }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status badges row -->
                <div class="px-5 py-3 flex flex-wrap gap-1.5 bg-secondary-50 dark:bg-secondary-800/60 border-b border-secondary-100 dark:border-secondary-800">
                    <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full border"
                        :class="member.is_active
                            ? 'bg-green-50 dark:bg-green-900/25 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                            : 'bg-red-50 dark:bg-red-900/25 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'">
                        {{ member.is_active ? '● Active' : '● Inactive' }}
                    </span>
                    <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full border"
                        :class="member.is_verified
                            ? 'bg-blue-50 dark:bg-blue-900/25 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800'
                            : 'bg-amber-50 dark:bg-amber-900/25 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800'">
                        {{ member.is_verified ? '✓ Verified' : '! Unverified' }}
                    </span>
                    <span v-if="member.is_temp" class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-orange-50 dark:bg-orange-900/25 border border-orange-200 dark:border-orange-800 text-orange-700 dark:text-orange-400">Temp</span>
                    <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-secondary-100 dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300">{{ displayValue(member.member_role) }}</span>
                    <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-secondary-100 dark:bg-secondary-700 border border-secondary-200 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300">{{ displayValue(member.payment_plan) }}</span>
                </div>

                <!-- Wallet balance -->
                <div class="mx-4 my-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="shrink-0 w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-700/60 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Wallet Balance</p>
                            <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-300 leading-tight">{{ formatMoney(member.current_balance) }}</p>
                        </div>
                    </div>
                    <div v-if="permissions.edit" class="flex gap-2 sm:shrink-0">
                        <button
                            type="button"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white transition-colors shadow-sm"
                            @click="openTopupModal"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Top Up
                        </button>
                        <button
                            type="button"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl bg-violet-600 hover:bg-violet-700 text-white transition-colors shadow-sm"
                            @click="openRedeemModal"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            Redeem Voucher
                        </button>
                    </div>
                </div>

                <!-- Stats strip -->
                <div class="grid grid-cols-3 border-t border-secondary-100 dark:border-secondary-800">
                    <div class="px-4 py-3.5 text-center border-r border-secondary-100 dark:border-secondary-800">
                        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">Plan</p>
                        <p class="mt-0.5 text-sm font-bold text-secondary-900 dark:text-white">{{ formatMoney(member.price) }}<span class="text-xs font-normal text-secondary-400 dark:text-secondary-500">/mo</span></p>
                    </div>
                    <div class="px-4 py-3.5 text-center border-r border-secondary-100 dark:border-secondary-800">
                        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">Joined</p>
                        <p class="mt-0.5 text-sm font-semibold text-secondary-900 dark:text-white">{{ formatDate(member.joined_date) }}</p>
                    </div>
                    <div class="px-4 py-3.5 text-center">
                        <p class="text-[11px] text-secondary-400 dark:text-secondary-500 uppercase tracking-wide">Member Since</p>
                        <p class="mt-0.5 text-sm font-semibold text-secondary-900 dark:text-white">{{ formatDate(member.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Tabs ── -->
            <div class="border-b border-secondary-200 dark:border-secondary-700 mx-4">
                <nav class="-mb-px flex gap-4" aria-label="Member tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="activeTab === tab.id
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400'
                            : 'border-transparent text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200'"
                        @click="switchTab(tab.id)"
                    >{{ tab.label }}</button>
                </nav>
            </div>

            <!-- ── Overview Tab ── -->
            <template v-if="activeTab === 'overview'">
            <!-- ── Detail Cards ── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Personal Info -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Personal</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">First Name</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.first_name) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Last Name</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.last_name) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Gender</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ normalizedGender }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Date of Birth</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ formatDate(member.date_of_birth) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Age</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.age) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">NIC</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.nic) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contact & Access -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Contact & Access</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3 gap-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Email</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right break-all">{{ displayValue(member.email) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Phone</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.phone_number) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Username</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.username) }}</dd>
                        </div>
                        <div class="flex items-start justify-between px-5 py-3 gap-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28 pt-0.5">Address</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right whitespace-pre-line">{{ displayValue(member.address) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Plan & Billing -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Plan & Billing</h2>
                    </div>
                    <dl class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Role</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.member_role) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Payment Plan</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ displayValue(member.payment_plan) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Monthly Fee</dt>
                            <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">{{ formatMoney(member.price) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Admission Fee</dt>
                            <dd class="text-sm font-medium text-secondary-900 dark:text-white text-right">{{ formatMoney(member.admission_fee) }}</dd>
                        </div>
                        <div class="flex items-center justify-between px-5 py-3">
                            <dt class="text-xs text-secondary-500 dark:text-secondary-400 shrink-0 w-28">Balance</dt>
                            <dd class="text-sm font-semibold text-secondary-900 dark:text-white text-right">{{ formatMoney(member.current_balance) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Notes</h2>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-sm text-secondary-700 dark:text-secondary-300 whitespace-pre-line leading-relaxed">
                            {{ member.comment || 'No notes added for this member.' }}
                        </p>
                    </div>
                </div>

            </div>
            </template><!-- /overview -->

            <!-- ── Wallet History Tab ── -->
            <template v-if="activeTab === 'wallet'">
                <!-- Transaction History -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Transaction History</h2>
                    </div>
                    <div v-if="walletLoading" class="px-5 py-6 text-center text-sm text-secondary-400">Loading...</div>
                    <div v-else-if="walletTransactions.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">No wallet transactions yet.</div>
                    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div v-for="tx in walletTransactions" :key="tx.id" class="flex items-center justify-between px-5 py-3 gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ tx.label }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">
                                    {{ formatDate(tx.date) }}
                                    <span v-if="tx.reference"> &bull; Ref: {{ tx.reference }}</span>
                                </p>
                                <p v-if="tx.notes" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">{{ tx.notes }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold"
                                    :class="tx.direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ tx.direction === 'credit' ? '+' : '-' }}{{ formatMoney(tx.amount) }}
                                </p>
                                <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full"
                                    :class="tx.direction === 'credit' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'">
                                    {{ tx.direction }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination -->
                    <div v-if="txMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Page {{ txMeta.current_page }} of {{ txMeta.last_page }}</p>
                        <div class="flex gap-1">
                            <button type="button" class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40" :disabled="txMeta.current_page <= 1" @click="loadTransactions(txMeta.current_page - 1)">Prev</button>
                            <button type="button" class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40" :disabled="txMeta.current_page >= txMeta.last_page" @click="loadTransactions(txMeta.current_page + 1)">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Voucher Redemption History -->
                <div class="bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-200 dark:border-secondary-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-secondary-100 dark:border-secondary-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-secondary-400 dark:text-secondary-500">Voucher Redemptions</h2>
                    </div>
                    <div v-if="walletLoading" class="px-5 py-6 text-center text-sm text-secondary-400">Loading...</div>
                    <div v-else-if="voucherRedemptions.length === 0" class="px-5 py-6 text-center text-sm text-secondary-400 dark:text-secondary-500">No vouchers redeemed yet.</div>
                    <div v-else class="divide-y divide-secondary-100 dark:divide-secondary-800">
                        <div v-for="r in voucherRedemptions" :key="r.id" class="flex items-start justify-between px-5 py-3 gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-secondary-900 dark:text-white">{{ r.voucher?.name || 'Voucher' }}</p>
                                <p class="text-xs font-mono text-secondary-400 dark:text-secondary-500 break-all">{{ r.voucher?.uuid }}</p>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">
                                    {{ formatDate(r.redeemed_at) }}
                                    <span v-if="r.redeemed_by"> &bull; by {{ r.redeemed_by.name }}</span>
                                </p>
                                <p v-if="r.notes" class="text-xs text-secondary-400 dark:text-secondary-500 mt-0.5 truncate">{{ r.notes }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold text-violet-600 dark:text-violet-400">+{{ formatMoney(r.voucher?.amount ?? 0) }}</p>
                                <span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400">Voucher</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="voucherRedemptionMeta.last_page > 1" class="px-5 py-3 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between gap-2">
                        <p class="text-xs text-secondary-500 dark:text-secondary-400">Page {{ voucherRedemptionMeta.current_page }} of {{ voucherRedemptionMeta.last_page }}</p>
                        <div class="flex gap-1">
                            <button type="button" class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40" :disabled="voucherRedemptionMeta.current_page <= 1" @click="loadVoucherRedemptions(voucherRedemptionMeta.current_page - 1)">Prev</button>
                            <button type="button" class="px-2 py-1 text-xs rounded border border-secondary-200 dark:border-secondary-700 disabled:opacity-40" :disabled="voucherRedemptionMeta.current_page >= voucherRedemptionMeta.last_page" @click="loadVoucherRedemptions(voucherRedemptionMeta.current_page + 1)">Next</button>
                        </div>
                    </div>
                </div>
            </template><!-- /wallet -->

        </template>

        <div v-else-if="!loading" class="p-8 text-center text-sm text-secondary-400 dark:text-secondary-500">Member details are unavailable.</div>

        </div><!-- max-w-4xl -->
        </div><!-- app-page-scroll -->

        <!-- ── Wallet Top-up Modal ── -->
        <div v-if="topupModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" @click="closeTopupModal"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Top Up Wallet</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">Current balance: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(member.current_balance) }}</span></p>
                    </div>
                    <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeTopupModal">✕</button>
                </div>

                <div v-if="topupError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">{{ topupError }}</div>

                <form class="space-y-3" @submit.prevent="submitTopup">
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Amount <span class="text-red-500">*</span></label>
                        <input v-model="topupForm.amount" type="number" min="0.01" step="0.01" required
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="0.00" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Company Account <span class="text-red-500">*</span></label>
                        <select v-model.number="topupForm.company_account_id" required
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option :value="null">Select account...</option>
                            <option v-for="acc in walletAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} — {{ formatMoney(acc.current_balance ?? 0) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Date <span class="text-red-500">*</span></label>
                        <input v-model="topupForm.topup_date" type="date" required
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Reference <span class="text-xs text-secondary-400">(optional)</span></label>
                        <input v-model="topupForm.reference_number" type="text" maxlength="255"
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Receipt or reference number" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-xs text-secondary-400">(optional)</span></label>
                        <textarea v-model="topupForm.notes" rows="2" maxlength="1000"
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeTopupModal">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
                            :disabled="topupSubmitting || !topupForm.amount || !topupForm.company_account_id">
                            {{ topupSubmitting ? 'Processing...' : 'Top Up' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Redeem Voucher Modal ── -->
        <div v-if="redeemModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/45" @click="closeRedeemModal"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Redeem Voucher</h3>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-0.5">Current balance: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(member.current_balance) }}</span></p>
                    </div>
                    <button type="button" class="text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200" @click="closeRedeemModal">✕</button>
                </div>

                <div v-if="redeemError" class="mb-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm text-red-700 dark:text-red-200">{{ redeemError }}</div>

                <form class="space-y-3" @submit.prevent="submitRedeem">
                    <!-- Hidden canvas for QR decoding (always present) -->
                    <canvas ref="qrCanvasRef" class="hidden" />
                    <!-- Hidden file input for image upload -->
                    <input ref="qrFileInputRef" type="file" accept="image/*" class="hidden" @change="onQrFileChange" />

                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Voucher UUID <span class="text-red-500">*</span></label>
                        <input v-model="redeemForm.uuid" type="text" required maxlength="36"
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm font-mono text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                            autocomplete="off"
                            spellcheck="false" />

                        <!-- QR scan buttons -->
                        <div class="flex gap-2 mt-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors"
                                :class="qrScanMode === 'camera'
                                    ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300'
                                    : 'border-secondary-300 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800'"
                                @click="qrScanMode === 'camera' ? stopCameraScan() : startCameraScan()"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ qrScanMode === 'camera' ? 'Stop Camera' : 'Scan QR' }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-600 dark:text-secondary-300 hover:bg-secondary-50 dark:hover:bg-secondary-800 transition-colors"
                                @click="triggerFileInput"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Upload Image
                            </button>
                        </div>

                        <!-- QR error -->
                        <p v-if="qrError" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ qrError }}</p>

                        <!-- Camera preview -->
                        <div v-if="qrScanMode === 'camera'" class="mt-3 rounded-xl overflow-hidden bg-black relative">
                            <video ref="qrVideoRef" autoplay playsinline muted class="w-full max-h-52 object-cover" />
                            <!-- corner frame overlay -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="relative w-40 h-40">
                                    <span class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-violet-400 rounded-tl-sm"></span>
                                    <span class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-violet-400 rounded-tr-sm"></span>
                                    <span class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-violet-400 rounded-bl-sm"></span>
                                    <span class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-violet-400 rounded-br-sm"></span>
                                </div>
                            </div>
                            <p class="absolute bottom-2 inset-x-0 text-center text-[11px] text-white/70">Point camera at the voucher QR code</p>
                        </div>

                        <p v-if="qrScanMode === 'off' && !qrError" class="mt-1 text-xs text-secondary-400 dark:text-secondary-500">Enter the UUID manually or scan the voucher QR code.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary-700 dark:text-secondary-300 mb-1">Notes <span class="text-xs text-secondary-400">(optional)</span></label>
                        <textarea v-model="redeemForm.notes" rows="2" maxlength="1000"
                            class="w-full rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-800 px-3 py-2 text-sm text-secondary-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" class="px-4 py-2 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-800" @click="closeRedeemModal">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold rounded-lg bg-violet-600 hover:bg-violet-700 text-white disabled:opacity-50"
                            :disabled="redeemSubmitting || !redeemForm.uuid.trim()">
                            {{ redeemSubmitting ? 'Redeeming...' : 'Redeem Voucher' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import jsQR from 'jsqr';
import { apiRequest } from '../composables/useApiClient';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const actionInProgress = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const member = ref(null);
const permissions = ref({ edit: false, delete: false });

// ── Tabs ──
const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'wallet', label: 'Wallet History' },
];
const activeTab = ref('overview');

function switchTab(id) {
    activeTab.value = id;
    if (id === 'wallet' && member.value) {
        loadWalletData();
    }
}

// ── Voucher redemption history ──
const voucherRedemptions = ref([]);
const voucherRedemptionMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

async function loadVoucherRedemptions(page = 1) {
    try {
        const res = await apiRequest(`/api/members/${route.params.id}/wallet/voucher-redemptions?page=${page}&per_page=10`);
        voucherRedemptions.value = res.data || [];
        voucherRedemptionMeta.value = res.meta || voucherRedemptionMeta.value;
    } catch (_) { /* ignore */ }
}

// ── Wallet state ──
const walletLoading = ref(false);
const topupHistory = ref([]);
const topupMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const walletTransactions = ref([]);
const txMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const walletAccounts = ref([]);

// Topup modal
const topupModalOpen = ref(false);
const topupSubmitting = ref(false);
const topupError = ref('');
const topupForm = ref({ amount: '', company_account_id: null, topup_date: todayString(), reference_number: '', notes: '' });

// Redeem voucher modal
const redeemModalOpen = ref(false);
const redeemSubmitting = ref(false);
const redeemError = ref('');
const redeemForm = ref({ uuid: '', notes: '' });

// QR scanner
const qrScanMode = ref('off'); // 'off' | 'camera'
const qrError = ref('');
const qrVideoRef = ref(null);
const qrCanvasRef = ref(null);
const qrFileInputRef = ref(null);
let qrStream = null;
let qrAnimFrame = null;

async function startCameraScan() {
    qrError.value = '';
    qrScanMode.value = 'camera';
    await nextTick();
    try {
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        qrVideoRef.value.srcObject = qrStream;
        qrVideoRef.value.onloadedmetadata = () => scanVideoFrame();
    } catch {
        qrError.value = 'Camera access denied. Please allow camera permission or upload an image.';
        qrScanMode.value = 'off';
    }
}

function stopCameraScan() {
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
    cancelAnimationFrame(qrAnimFrame);
    qrAnimFrame = null;
    qrScanMode.value = 'off';
}

function scanVideoFrame() {
    if (qrScanMode.value !== 'camera' || !qrVideoRef.value || !qrCanvasRef.value) return;
    const video = qrVideoRef.value;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        const canvas = qrCanvasRef.value;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code?.data) {
            onQrDetected(code.data);
            return;
        }
    }
    qrAnimFrame = requestAnimationFrame(scanVideoFrame);
}

function triggerFileInput() {
    qrFileInputRef.value?.click();
}

function onQrFileChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    qrError.value = '';
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
        const canvas = qrCanvasRef.value;
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        URL.revokeObjectURL(url);
        if (code?.data) {
            onQrDetected(code.data);
        } else {
            qrError.value = 'No QR code found in image. Try a clearer image.';
        }
        event.target.value = '';
    };
    img.src = url;
}

function onQrDetected(data) {
    const match = data.match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
    if (match) {
        redeemForm.value.uuid = match[0];
        stopCameraScan();
        qrError.value = '';
    } else {
        qrError.value = 'QR code does not contain a valid UUID.';
    }
}

function openRedeemModal() {
    redeemForm.value = { uuid: '', notes: '' };
    redeemError.value = '';
    qrScanMode.value = 'off';
    qrError.value = '';
    redeemModalOpen.value = true;
}

function closeRedeemModal() {
    stopCameraScan();
    redeemModalOpen.value = false;
}

async function submitRedeem() {
    redeemSubmitting.value = true;
    redeemError.value = '';
    try {
        const res = await apiRequest(`/api/members/${member.value.id}/wallet/redeem-voucher`, {
            method: 'post',
            data: {
                uuid: redeemForm.value.uuid.trim(),
                notes: redeemForm.value.notes || null,
            },
        });
        member.value = { ...member.value, current_balance: res.current_balance };
        closeRedeemModal();
        await Promise.all([loadVoucherRedemptions(1), loadTransactions(1)]);
        successMessage.value = 'Voucher redeemed successfully. Wallet credited.';
        setTimeout(() => { successMessage.value = ''; }, 4000);
    } catch (err) {
        redeemError.value = err?.response?.data?.message || 'Failed to redeem voucher.';
    } finally {
        redeemSubmitting.value = false;
    }
}

function todayString() {
    return new Date().toISOString().slice(0, 10);
}

function openTopupModal() {
    topupForm.value = { amount: '', company_account_id: null, topup_date: todayString(), reference_number: '', notes: '' };
    topupError.value = '';
    topupModalOpen.value = true;
    loadWalletMeta();
}

function closeTopupModal() {
    topupModalOpen.value = false;
}

async function loadWalletMeta() {
    try {
        const res = await apiRequest('/api/wallet/meta');
        walletAccounts.value = res.accounts || [];
    } catch (_) { /* ignore */ }
}

async function loadWalletData() {
    walletLoading.value = true;
    try {
        await Promise.all([
            loadTopupHistory(1),
            loadTransactions(1),
            loadVoucherRedemptions(1),
        ]);
        if (walletAccounts.value.length === 0) loadWalletMeta();
    } finally {
        walletLoading.value = false;
    }
}

async function loadTopupHistory(page = 1) {
    try {
        const res = await apiRequest(`/api/members/${route.params.id}/wallet/topup-history?page=${page}&per_page=10`);
        topupHistory.value = res.data || [];
        topupMeta.value = res.meta || topupMeta.value;
    } catch (_) { /* ignore */ }
}

async function loadTransactions(page = 1) {
    try {
        const res = await apiRequest(`/api/members/${route.params.id}/wallet/transactions?page=${page}&per_page=15`);
        walletTransactions.value = res.data || [];
        txMeta.value = res.meta || txMeta.value;
    } catch (_) { /* ignore */ }
}

async function submitTopup() {
    topupSubmitting.value = true;
    topupError.value = '';
    try {
        const res = await apiRequest(`/api/members/${member.value.id}/wallet/topup`, {
            method: 'post',
            data: topupForm.value,
        });
        member.value = { ...member.value, current_balance: res.current_balance };
        closeTopupModal();
        await Promise.all([loadTopupHistory(1), loadTransactions(1), loadWalletMeta()]);
        successMessage.value = 'Wallet topped up successfully.';
        setTimeout(() => { successMessage.value = ''; }, 4000);
    } catch (err) {
        topupError.value = err?.response?.data?.message || 'Failed to process top-up.';
    } finally {
        topupSubmitting.value = false;
    }
}


const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const fullName = computed(() => {
    if (!member.value) {
        return 'Member';
    }

    const firstName = (member.value.first_name || '').trim();
    const lastName = (member.value.last_name || '').trim();

    if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
    }

    return member.value.name || 'Member';
});

const initials = computed(() => {
    const value = fullName.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return value || 'MB';
});

const normalizedGender = computed(() => {
    if (!member.value?.gender) {
        return 'Not provided';
    }

    return capitalize(member.value.gender);
});

const activeActionLabel = computed(() => (member.value?.is_active ? 'Deactivate' : 'Activate'));
const verificationActionLabel = computed(() => (member.value?.is_verified ? 'Unverify' : 'Verify'));

function capitalize(value = '') {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

function displayValue(value) {
    return value === null || value === undefined || value === '' ? 'Not provided' : value;
}

function formatDate(value) {
    if (!value) {
        return 'Not provided';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return 'Not provided';
    }

    return moneyFormatter.format(Number(value));
}

async function loadMember() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${route.params.id}`);
        member.value = response.data || null;
        permissions.value = response.permissions || permissions.value;
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to load member details.';
    } finally {
        loading.value = false;
    }
}

async function toggleStatus() {
    if (!member.value) {
        return;
    }

    actionInProgress.value = 'status';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-status`, { method: 'patch' });
        member.value = {
            ...member.value,
            is_active: response.is_active,
        };
        successMessage.value = response.message || 'Member status updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member status.';
    } finally {
        actionInProgress.value = '';
    }
}

async function toggleVerification() {
    if (!member.value) {
        return;
    }

    actionInProgress.value = 'verification';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await apiRequest(`/api/members/${member.value.id}/toggle-verification`, { method: 'patch' });
        member.value = {
            ...member.value,
            is_verified: response.is_verified,
        };
        successMessage.value = response.message || 'Member verification updated successfully.';
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to update member verification.';
    } finally {
        actionInProgress.value = '';
    }
}

async function removeMember() {
    if (!member.value || !window.confirm('Are you sure you want to delete this member?')) {
        return;
    }

    actionInProgress.value = 'delete';
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await apiRequest(`/api/members/${member.value.id}`, { method: 'delete' });
        router.push('/members');
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Failed to delete member.';
    } finally {
        actionInProgress.value = '';
    }
}

onMounted(() => {
    loadMember();
});

onUnmounted(() => {
    stopCameraScan();
});
</script>