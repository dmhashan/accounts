<x-app-layout>
    <x-slot name="title">Add Member - {{ app('tenant')->name }}</x-slot>
    
    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Add Member</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                        <form action="{{ route('members.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="profile_photo" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Profile Photo</label>
                                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('profile_photo')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">First Name *</label>
                                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required maxlength="100"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('first_name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Last Name *</label>
                                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required maxlength="100"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('last_name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="username" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">User Name *</label>
                                    <input type="text" name="username" id="username" value="{{ old('username') }}" required maxlength="50"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white"
                                        placeholder="e.g. john.doe">
                                    <p class="mt-1 text-xs text-secondary-500 dark:text-secondary-400">This username will be used to log in to member's account</p>
                                    @error('username')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="email" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Email *</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Mobile Number *</label>
                                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" required maxlength="20"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('phone_number')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="nic" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">NIC</label>
                                    <input type="text" name="nic" id="nic" value="{{ old('nic') }}" maxlength="50"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('nic')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Birthday *</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('date_of_birth')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="gender" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Gender *</label>
                                    <select name="gender" id="gender" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="">Select gender</option>
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    @error('gender')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="age" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Age *</label>
                                    <input type="number" name="age" id="age" value="{{ old('age') }}" min="1" max="120" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('age')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Address</label>
                                    <input type="text" name="address" id="address" value="{{ old('address') }}" maxlength="1000"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="member_id" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Member Id *</label>
                                    <input type="text" id="member_id" value="{{ old('member_id', $generatedMemberId) }}" readonly
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-50 dark:bg-secondary-800 dark:text-white">
                                </div>

                                <div>
                                    <label for="member_role" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Role *</label>
                                    <select name="member_role" id="member_role" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="member" {{ old('member_role', 'member') == 'member' ? 'selected' : '' }}>Member</option>
                                    </select>
                                    @error('member_role')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="admission_fee" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Admission Fee</label>
                                    <input type="number" step="0.01" min="0" name="admission_fee" id="admission_fee" value="{{ old('admission_fee') }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white"
                                        placeholder="Enter admission fee (optional)">
                                    @error('admission_fee')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="payment_plan" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Payment Plan *</label>
                                    <select name="payment_plan" id="payment_plan" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="">Select a payment plan</option>
                                        <option value="monthly" {{ old('payment_plan') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('payment_plan') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="yearly" {{ old('payment_plan') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                    @error('payment_plan')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="price" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Price *</label>
                                    <input type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price') }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-50 dark:bg-secondary-800 dark:text-white"
                                        placeholder="Auto-filled from plan">
                                    @error('price')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="joined_date" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Joined Date *</label>
                                    <input type="date" name="joined_date" id="joined_date" value="{{ old('joined_date', now()->toDateString()) }}" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('joined_date')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="comment" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Remark</label>
                                    <textarea name="comment" id="comment" rows="3"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>

                            <div class="mt-6 p-4 bg-secondary-50 dark:bg-secondary-800 rounded-lg">
                                <p class="text-sm text-secondary-600 dark:text-secondary-400">
                                    <strong>Note:</strong> A user account will be automatically created for this member. Member login is handled via Google/Apple SSO.
                                </p>
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-6">
                                <a href="{{ route('members.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                                    Create Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const birthdayInput = document.getElementById('date_of_birth');
        const ageInput = document.getElementById('age');
        const paymentPlanInput = document.getElementById('payment_plan');
        const priceInput = document.getElementById('price');

        const planPrices = {
            monthly: 5000,
            quarterly: 14000,
            yearly: 50000,
        };

        function setAgeFromBirthday() {
            if (!birthdayInput.value) {
                return;
            }

            const today = new Date();
            const birthday = new Date(birthdayInput.value);
            let age = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
                age -= 1;
            }

            if (age > 0) {
                ageInput.value = age;
            }
        }

        function setPriceFromPlan() {
            const selectedPlan = paymentPlanInput.value;
            if (planPrices[selectedPlan] !== undefined) {
                priceInput.value = planPrices[selectedPlan];
            }
        }

        birthdayInput?.addEventListener('change', setAgeFromBirthday);
        paymentPlanInput?.addEventListener('change', setPriceFromPlan);
    </script>
</x-app-layout>
