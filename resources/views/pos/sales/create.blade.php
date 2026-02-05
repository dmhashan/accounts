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

            <main id="salePage" class="flex-1 overflow-y-auto p-4 md:p-6" data-ui-mode="{{ $uiMode }}">
                <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
                    @csrf

                    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex rounded-lg border border-secondary-200 dark:border-secondary-700 overflow-hidden" id="uiModeToggle">
                                    <button type="button" data-ui-mode="desktop" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Desktop</button>
                                    <button type="button" data-ui-mode="touch" class="px-3 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">Touch</button>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
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

                        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-secondary-900 text-white hover:bg-secondary-800 dark:bg-secondary-700 dark:hover:bg-secondary-600 rounded-lg transition-colors">Sales History</a>
                    </div>

                    <div class="sale-mode" data-mode="desktop">
                        @include('pos.sales.desktop')
                    </div>
                    <div class="sale-mode" data-mode="touch">
                        @include('pos.sales.touch')
                    </div>
                </form>
            </main>
        </div>
    </div>

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
            totalAmountInput = activeWrapper.querySelector('[data-role="total-amount"]');
            paidAmountInput = activeWrapper.querySelector('[data-role="paid-amount"]');
            balanceAmountInput = activeWrapper.querySelector('[data-role="balance-amount"]');

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
        const initialUiMode = ['desktop', 'touch'].includes(storedUiMode)
            ? storedUiMode
            : (salePage.dataset.uiMode || 'touch');
        setActiveMode(initialUiMode);

        const storedCustomerType = localStorage.getItem('saleCustomerType');
        const initialCustomerType = ['local', 'foreign'].includes(storedCustomerType)
            ? storedCustomerType
            : (customerTypeInput.value || 'local');
        setCustomerType(initialCustomerType);
    </script>
</x-app-layout>
