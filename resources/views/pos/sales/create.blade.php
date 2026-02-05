<x-app-layout>
    <x-slot name="title">New Sale - {{ app('tenant')->name }}</x-slot>

    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">New Sale</x-slot>
            </x-header>

            <main id="salePage" class="flex-1 overflow-y-auto p-4 md:p-6">
                <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
                    @csrf

                    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">UI Mode</span>
                            <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden" id="uiModeToggle">
                                <button type="button" data-ui-mode="desktop" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Desktop</button>
                                <button type="button" data-ui-mode="touch" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Touch Friendly</button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Customer Type</span>
                            <div id="customerTypeToggle" class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                                <button type="button" data-customer-type="local" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Local</button>
                                <button type="button" data-customer-type="foreign" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Foreign</button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Customer</span>
                            <select name="customer_name" id="customer_name"
                                class="min-w-[220px] px-3 py-2 text-sm border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                <option value="">Walk-in (optional)</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->name }}" {{ old('customer_name') === $member->name ? 'selected' : '' }}>
                                        {{ $member->member_id }} - {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="customer_type" id="customer_type" value="{{ old('customer_type', 'local') }}" required>
                    @error('customer_type')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-secondary-900 text-white hover:bg-secondary-800 dark:bg-secondary-700 dark:hover:bg-secondary-600 rounded-lg transition-colors touch-btn">Sales History</a>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 touch-card">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">Sale Items</h3>
                                    <button type="button" id="addItemBtn" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors touch-btn">Add Item</button>
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
                            <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6 touch-card">
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

                                <button type="submit" class="mt-6 w-full px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors touch-btn">Complete Sale</button>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <style>
        #salePage.ui-touch .touch-card {
            padding: 1.75rem;
        }

        #salePage.ui-touch .touch-btn {
            padding: 0.75rem 1.25rem;
            font-size: 1rem;
        }

        #salePage.ui-touch input,
        #salePage.ui-touch select,
        #salePage.ui-touch button,
        #salePage.ui-touch textarea {
            font-size: 1rem;
        }

        #salePage.ui-touch table th,
        #salePage.ui-touch table td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
    </style>

    <script>
        const variations = @json($variations);
        const availableStock = @json($availableStock);
        const priceMap = @json($priceMap);

        const itemsTable = document.getElementById('itemsTable');
        const addItemBtn = document.getElementById('addItemBtn');
        const customerTypeInput = document.getElementById('customer_type');
        const customerTypeToggle = document.getElementById('customerTypeToggle');
        const uiModeToggle = document.getElementById('uiModeToggle');
        const salePage = document.getElementById('salePage');
        const totalAmountInput = document.getElementById('totalAmount');
        const paidAmountInput = document.getElementById('paid_amount');
        const balanceAmountInput = document.getElementById('balanceAmount');

        let rowIndex = 0;

        function getPrice(variationId) {
            const type = customerTypeInput.value;
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
            const unitPrice = parseFloat(unitPriceInput.value || '0');
            subtotalInput.value = (unitPrice * qty).toFixed(2);
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

        function setToggleActive(container, value, dataKey) {
            container.querySelectorAll('button').forEach(button => {
                const isActive = button.getAttribute(dataKey) === value;
                button.classList.toggle('bg-primary-600', isActive);
                button.classList.toggle('text-white', isActive);
                button.classList.toggle('text-secondary-700', !isActive);
                button.classList.toggle('dark:text-secondary-300', !isActive);
            });
        }

        function setCustomerType(type) {
            customerTypeInput.value = type;
            setToggleActive(customerTypeToggle, type, 'data-customer-type');
            itemsTable.querySelectorAll('tr').forEach(updateRowPrices);
        }

        function setUiMode(mode) {
            salePage.classList.toggle('ui-touch', mode === 'touch');
            setToggleActive(uiModeToggle, mode, 'data-ui-mode');
            localStorage.setItem('saleUiMode', mode);
        }

        addItemBtn.addEventListener('click', addRow);
        customerTypeToggle.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => setCustomerType(button.getAttribute('data-customer-type')));
        });
        uiModeToggle.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => setUiMode(button.getAttribute('data-ui-mode')));
        });
        paidAmountInput.addEventListener('input', recalcTotals);

        const initialCustomerType = customerTypeInput.value || 'local';
        setCustomerType(initialCustomerType);
        const savedUiMode = localStorage.getItem('saleUiMode') || 'desktop';
        setUiMode(savedUiMode);
        addRow();
        recalcTotals();
    </script>
</x-app-layout>
