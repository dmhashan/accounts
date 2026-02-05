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
                    <th class="py-2 text-left">Product</th>
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
