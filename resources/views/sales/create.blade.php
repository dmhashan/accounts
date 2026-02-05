<x-app-layout>
    <x-slot name="title">New Sale - {{ app('tenant')->name }}</x-slot>

    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">New Sale</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="flex flex-wrap justify-end items-center gap-4 mb-6">
                    <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-secondary-900 text-white hover:bg-secondary-800 dark:bg-secondary-700 dark:hover:bg-secondary-600 rounded-lg transition-colors">Sales History</a>
                </div>

                <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                                <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-4">Customer Info</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="customer_name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Customer (optional)</label>
                                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}"
                                            class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    </div>

                                    <div>
                                        <label for="customer_type" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Customer Type *</label>
                                        <select name="customer_type" id="customer_type" required
                                            class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                            <option value="">Select type</option>
                                            <option value="local" {{ old('customer_type') === 'local' ? 'selected' : '' }}>Local</option>
                                            <option value="foreign" {{ old('customer_type') === 'foreign' ? 'selected' : '' }}>Foreign</option>
                                        </select>
                                        @error('customer_type')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Sale Items</h3>
                                    <button type="button" id="addItemBtn" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Add Item</button>
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
                                        <tbody id="itemsTable" class="divide-y divide-secondary-200 dark:divide-secondary-700"></tbody>
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
                                        <input type="text" id="totalAmount" class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                                    </div>
                                    <div>
                                        <label for="paid_amount" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Paid Amount</label>
                                        <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', '0.00') }}"
                                            class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        @error('paid_amount')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Balance</label>
                                        <input type="text" id="balanceAmount" class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                                    </div>
                                </div>

                                <button type="submit" class="mt-6 w-full px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Complete Sale</button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script>
        const variations = @json($variations);
        const availableStock = @json($availableStock);
        const priceMap = @json($priceMap);

        const itemsTable = document.getElementById('itemsTable');
        const addItemBtn = document.getElementById('addItemBtn');
        const customerTypeSelect = document.getElementById('customer_type');
        const totalAmountInput = document.getElementById('totalAmount');
        const paidAmountInput = document.getElementById('paid_amount');
        const balanceAmountInput = document.getElementById('balanceAmount');

        let rowIndex = 0;

        function getPrice(variationId) {
            const type = customerTypeSelect.value;
            if (!type || !priceMap[variationId]) return 0;
            const raw = type === 'local' ? priceMap[variationId].local : priceMap[variationId].foreign;
            const parsed = parseFloat(raw);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function recalcTotals() {
            let total = 0;
            itemsTable.querySelectorAll('tr').forEach(row => {
                const subtotal = parseFloat(row.querySelector('[data-subtotal]').value || '0');
                total += subtotal;
            });
            totalAmountInput.value = total.toFixed(2);
            const paid = parseFloat(paidAmountInput.value || '0');
            const balance = paid - total;
            balanceAmountInput.value = balance.toFixed(2);
        }

        function updateRowPrices(row) {
            const variationSelect = row.querySelector('[data-variation]');
            const qtyInput = row.querySelector('[data-qty]');
            const unitPriceInput = row.querySelector('[data-unit-price]');
            const subtotalInput = row.querySelector('[data-subtotal]');
            const availableLabel = row.querySelector('[data-available]');

            const variationId = variationSelect.value;
            const available = variationId ? (availableStock[variationId] || 0) : 0;
            availableLabel.textContent = variationId ? available : '-';
            qtyInput.max = variationId ? available : '';

            const price = variationId ? getPrice(variationId) : 0;
            unitPriceInput.value = price.toFixed(2);
            const qty = parseFloat(qtyInput.value || '0');
            subtotalInput.value = (price * qty).toFixed(2);
            recalcTotals();
        }

        function addRow() {
            const row = document.createElement('tr');
            row.className = 'hover:bg-secondary-50 dark:hover:bg-secondary-800/50';
            row.innerHTML = `
                <td class="py-3 text-secondary-700 dark:text-secondary-300" data-code>-</td>
                <td class="py-3 text-secondary-700 dark:text-secondary-300" data-product>-</td>
                <td class="py-3">
                    <select name="items[${rowIndex}][product_variation_id]" data-variation required
                        class="w-full px-2 py-1 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                        <option value="">Select</option>
                        ${variations.map(v => `<option value="${v.id}">${v.product?.name ?? ''} - ${v.name}</option>`).join('')}
                    </select>
                </td>
                <td class="py-3 text-secondary-700 dark:text-secondary-300" data-available>-</td>
                <td class="py-3">
                    <input type="number" name="items[${rowIndex}][quantity]" data-qty min="1" value="1" required
                        class="w-20 px-2 py-1 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                </td>
                <td class="py-3">
                    <input type="text" data-unit-price class="w-24 px-2 py-1 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                </td>
                <td class="py-3">
                    <input type="text" data-subtotal class="w-24 px-2 py-1 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                </td>
                <td class="py-3 text-right">
                    <button type="button" class="text-red-600 hover:text-red-900 dark:text-red-400" data-remove>Remove</button>
                </td>
            `;

            itemsTable.appendChild(row);
            rowIndex += 1;

            const variationSelect = row.querySelector('[data-variation]');
            const qtyInput = row.querySelector('[data-qty]');
            const codeCell = row.querySelector('[data-code]');
            const productCell = row.querySelector('[data-product]');

            variationSelect.addEventListener('change', () => {
                const selected = variations.find(v => String(v.id) === variationSelect.value);
                codeCell.textContent = selected ? selected.id : '-';
                productCell.textContent = selected ? selected.product?.name ?? '-' : '-';
                updateRowPrices(row);
            });

            qtyInput.addEventListener('input', () => updateRowPrices(row));

            row.querySelector('[data-remove]').addEventListener('click', () => {
                row.remove();
                recalcTotals();
            });

            updateRowPrices(row);
        }

        addItemBtn.addEventListener('click', addRow);
        customerTypeSelect.addEventListener('change', () => {
            itemsTable.querySelectorAll('tr').forEach(updateRowPrices);
        });
        paidAmountInput.addEventListener('input', recalcTotals);

        addRow();
        recalcTotals();
    </script>
</x-app-layout>