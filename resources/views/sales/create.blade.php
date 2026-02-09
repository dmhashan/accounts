<x-app-layout>
    <x-slot name="title">New Sale - {{ app('tenant')->name }}</x-slot>

    @php
    $uiMode = in_array(request('ui'), ['desktop', 'touch']) ? request('ui') : 'touch';
    @endphp

    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">New Sale</x-slot>
            </x-header>

            <main id="salePage" class="flex-1 overflow-y-auto p-4 md:p-6 pb-20" data-ui-mode="{{ $uiMode }}">
                <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
                    @csrf

                    <div class="flex flex-nowrap items-center gap-4 mb-6 overflow-x-auto overflow-y-visible">
                        <div class="flex flex-nowrap items-center gap-4">
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden" id="uiModeToggle">
                                    <button type="button" data-ui-mode="desktop" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 inline-flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="12" rx="2" />
                                            <path d="M8 20h8M12 16v4" />
                                        </svg>
                                    </button>
                                    <button type="button" data-ui-mode="touch" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 inline-flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 12V5a2 2 0 1 1 4 0v7" />
                                            <path d="M8 12V6a2 2 0 1 0-4 0v8a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4v-2a2 2 0 1 0-4 0v2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div id="customerTypeToggle" class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden">
                                    <button type="button" data-customer-type="local" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 inline-flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 10.5 12 4l9 6.5" />
                                            <path d="M5 9.5V20h14V9.5" />
                                            <path d="M9 20v-6h6v6" />
                                        </svg>
                                    </button>
                                    <button type="button" data-customer-type="foreign" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 inline-flex items-center gap-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M3 12h18" />
                                            <path d="M12 3a15 15 0 0 1 0 18" />
                                            <path d="M12 3a15 15 0 0 0 0 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0 relative z-40">
                                <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300 whitespace-nowrap">Customer</span>
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

                        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-secondary-900 text-white hover:bg-secondary-800 dark:bg-secondary-700 dark:hover:bg-secondary-600 rounded-lg transition-colors whitespace-nowrap shrink-0">Sales History</a>
                    </div>

                    <div class="sale-mode" data-mode="desktop">
                        @include('sales.desktop')
                    </div>
                    <div class="sale-mode" data-mode="touch">
                        @include('sales.touch')
                    </div>

                    <div class="fixed bottom-0 left-0 right-0 z-30">
                        <div class="bg-white/95 dark:bg-secondary-900/95 backdrop-blur border-t border-secondary-200 dark:border-secondary-700 px-4 md:px-6 py-3">
                            <div class="flex flex-nowrap items-center gap-3 overflow-x-auto">
                                <div class="flex items-center gap-3 shrink-0 min-w-[180px]">
                                    <label class="text-xs font-medium text-secondary-700 dark:text-secondary-300 whitespace-nowrap">Total</label>
                                    <input type="text" data-role="total-amount" class="w-full h-9 px-3 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 min-w-[200px]">
                                    <label for="paid_amount" class="text-xs font-medium text-secondary-700 dark:text-secondary-300 whitespace-nowrap">Paid</label>
                                    <div class="w-full">
                                        <input type="number" step="0.01" min="0" name="paid_amount" data-role="paid-amount" value="{{ old('paid_amount', '0.00') }}"
                                            class="w-full h-9 px-3 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        @error('paid_amount')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 min-w-[190px]">
                                    <label class="text-xs font-medium text-secondary-700 dark:text-secondary-300 whitespace-nowrap">Balance</label>
                                    <input type="text" data-role="balance-amount" class="w-full h-9 px-3 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-secondary-100 dark:bg-secondary-800 text-secondary-900 dark:text-white" value="0.00" readonly>
                                </div>
                                <div class="shrink-0">
                                    <button type="submit" class="w-auto h-9 px-4 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors whitespace-nowrap">Complete Sale</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
        .choices__list--dropdown,
        .choices__list[aria-expanded] {
            z-index: 50;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const variations = @json($variations);
        const availableStock = @json($availableStock);
        const priceMap = @json($priceMap);

        let itemsTable = null;
        let addItemBtn = null;
        let totalAmountInput = null;
        let paidAmountInput = null;
        let balanceAmountInput = null;
        const customerTypeInput = document.getElementById('customer_type');
        const customerTypeToggle = document.getElementById('customerTypeToggle');
        const uiModeToggle = document.getElementById('uiModeToggle');
        const salePage = document.getElementById('salePage');

        let rowIndex = 0;

        function initSearchableSelect(select) {
            if (!select || select.dataset.choicesInitialized === 'true') return;
            if (typeof window.Choices === 'undefined') {
                return;
            }
            const instance = new Choices(select, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                allowHTML: false
            });
            select.dataset.choicesInitialized = 'true';
            select.dataset.choicesInstanceId = instance._baseId;
        }

        function getPrice(variationId) {
            const type = customerTypeInput.value;
            if (!type || !priceMap[variationId]) return 0;
            const raw = type === 'local' ? priceMap[variationId].local : priceMap[variationId].foreign;
            const parsed = parseFloat(raw);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function recalcTotals() {
            if (!itemsTable || !totalAmountInput || !paidAmountInput || !balanceAmountInput) return;
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
            if (!itemsTable) return;
            const row = document.createElement('tr');
            row.className = 'hover:bg-secondary-50 dark:hover:bg-secondary-800/50';
            row.innerHTML = `
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
            initSearchableSelect(variationSelect);
            variationSelect.addEventListener('change', () => {
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
            localStorage.setItem('saleCustomerType', type);
            setToggleActive(customerTypeToggle, type, 'data-customer-type');
            if (itemsTable) {
                itemsTable.querySelectorAll('tr').forEach(updateRowPrices);
            }
        }

        function setActiveMode(mode) {
            salePage.querySelectorAll('.sale-mode').forEach(wrapper => {
                wrapper.classList.toggle('hidden', wrapper.dataset.mode !== mode);
            });
            setToggleActive(uiModeToggle, mode, 'data-ui-mode');
            const activeWrapper = salePage.querySelector(`.sale-mode[data-mode="${mode}"]`);
            if (!activeWrapper) return;

            itemsTable = activeWrapper.querySelector('[data-role="items-table"]');
            addItemBtn = activeWrapper.querySelector('[data-role="add-item"]');
            totalAmountInput = salePage.querySelector('[data-role="total-amount"]');
            paidAmountInput = salePage.querySelector('[data-role="paid-amount"]');
            balanceAmountInput = salePage.querySelector('[data-role="balance-amount"]');

            if (addItemBtn) {
                addItemBtn.onclick = addRow;
            }

            if (paidAmountInput) {
                paidAmountInput.oninput = recalcTotals;
            }

            if (itemsTable && itemsTable.children.length === 0) {
                addRow();
            } else {
                recalcTotals();
            }
        }

        const customerSelect = document.getElementById('customer_name');
        if (customerSelect) {
            initSearchableSelect(customerSelect);
        }

        customerTypeToggle.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => setCustomerType(button.getAttribute('data-customer-type')));
        });
        uiModeToggle.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => {
                const mode = button.getAttribute('data-ui-mode');
                localStorage.setItem('saleUiMode', mode);
                setActiveMode(mode);
            });
        });

        const storedUiMode = localStorage.getItem('saleUiMode');
        const initialUiMode = ['desktop', 'touch'].includes(storedUiMode) ?
            storedUiMode :
            (salePage.dataset.uiMode || 'touch');
        setActiveMode(initialUiMode);

        const storedCustomerType = localStorage.getItem('saleCustomerType');
        const initialCustomerType = ['local', 'foreign'].includes(storedCustomerType) ?
            storedCustomerType :
            (customerTypeInput.value || 'local');
        setCustomerType(initialCustomerType);
    </script>
</x-app-layout>