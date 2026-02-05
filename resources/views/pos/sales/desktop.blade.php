<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Sale Items</h3>
                <button type="button" data-role="add-item" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Add Item</button>
            </div>

            @error('items')
                <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-secondary-500 dark:text-secondary-400">
                        <tr>
                            <th class="py-2 text-left">Item Code</th>
                            <th class="py-2 text-left">Product</th>
                            <th class="py-2 text-left">Variation</th>
                            <th class="py-2 text-left">Available</th>
                            <th class="py-2 text-left">Qty</th>
                            <th class="py-2 text-left">Unit Price</th>
                            <th class="py-2 text-left">Subtotal</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody data-role="items-table" class="divide-y divide-secondary-200 dark:divide-secondary-700"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
            <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">Billing</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Total Amount</label>
                    <input type="text" data-role="total-amount" class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                </div>
                <div>
                    <label for="paid_amount" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Paid Amount</label>
                    <input type="number" step="0.01" min="0" name="paid_amount" data-role="paid-amount" value="{{ old('paid_amount', '0.00') }}"
                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                    @error('paid_amount')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Balance</label>
                    <input type="text" data-role="balance-amount" class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                </div>
            </div>

            <button type="submit" class="mt-6 w-full px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Complete Sale</button>
        </div>
    </div>
</div>
