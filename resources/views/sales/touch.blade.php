@php
    $touchPriceMap = collect($priceMap ?? []);

    $touchProductCards = collect($variations ?? [])
        ->map(function ($variation) use ($touchPriceMap) {
            $product = $variation->product ?? null;
            if (!$product) {
                return null;
            }

            $prices = $touchPriceMap->get($variation->id);
            $localPrice = $prices['local'] ?? $variation->price_local ?? 0;
            $foreignPrice = $prices['foreign'] ?? $variation->price_foreign ?? 0;

            return [
                'product_name' => $product->name,
                'variation_id' => $variation->id,
                'variation_name' => $variation->name,
                'local_price' => is_numeric($localPrice) ? (float) $localPrice : 0,
                'foreign_price' => is_numeric($foreignPrice) ? (float) $foreignPrice : 0,
            ];
        })
        ->filter()
        ->values();
@endphp

<div class="touch-sale-ui bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-3 sm:p-4 lg:p-5">
    @error('items')
        <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">

        <section class="touch-product-panel rounded-xl border border-secondary-200 dark:border-secondary-700 p-2 bg-secondary-50/60 dark:bg-secondary-800/30">
            <div class="mb-2.5">
                <input
                    type="text"
                    data-role="touch-product-search"
                    placeholder="Search product or variation"
                    class="w-full h-9 px-3 text-sm rounded-lg border border-secondary-300 dark:border-secondary-600 bg-white dark:bg-secondary-900 text-secondary-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                >
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 max-h-[68vh] overflow-y-auto pr-1">
                @forelse($touchProductCards as $card)
                    <button
                        type="button"
                        data-role="touch-product-card"
                        data-search-text="{{ \Illuminate\Support\Str::lower($card['product_name'].' '.$card['variation_name']) }}"
                        data-variation-id="{{ $card['variation_id'] }}"
                        data-local-price="{{ number_format($card['local_price'], 2, '.', '') }}"
                        data-foreign-price="{{ number_format($card['foreign_price'], 2, '.', '') }}"
                        class="group text-left rounded-md border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 hover:border-primary-400 dark:hover:border-primary-500 transition-colors overflow-hidden"
                    >
                        <div class="aspect-[16/9] bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-secondary-400 dark:text-secondary-500 text-[11px] font-medium">
                            {{ \Illuminate\Support\Str::of($card['product_name'])->limit(2, '')->upper() }}
                        </div>
                        <div class="p-1.5">
                            <p class="text-[11px] font-semibold text-secondary-900 dark:text-white truncate">{{ $card['product_name'] }}</p>
                            <p class="text-[10px] text-secondary-500 dark:text-secondary-400 mt-0.5 truncate">{{ $card['variation_name'] }}</p>
                            <p data-role="touch-product-price" class="text-xs font-bold text-secondary-900 dark:text-white mt-1">${{ number_format($card['local_price'], 2) }}</p>
                        </div>
                    </button>
                @empty
                    <div class="col-span-2 sm:col-span-3 lg:col-span-4 h-40 rounded-xl border border-dashed border-secondary-300 dark:border-secondary-700 flex items-center justify-center text-sm text-secondary-500 dark:text-secondary-400 bg-white dark:bg-secondary-900">
                        No products available.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="touch-cart-panel rounded-xl border border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/60 p-2.5 sm:p-3">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-secondary-900 dark:text-white">Cart Items</h3>
                <button type="button" data-role="add-item" class="h-10 px-4 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition-colors">Add Item</button>
            </div>

            <div class="max-h-[68vh] overflow-y-auto pr-1">
                <table class="w-full text-sm">
                    <tbody data-role="items-table" class="touch-cart-list"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .touch-sale-ui .touch-cart-list {
        display: block;
    }

    .touch-sale-ui .touch-cart-list tr {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.625rem 0.75rem;
        padding: 0.75rem;
        margin-bottom: 0.625rem;
        border-radius: 0.75rem;
        border: 1px solid;
    }

    .touch-sale-ui .touch-cart-list td {
        padding: 0;
    }

    .touch-sale-ui .touch-cart-list td:nth-child(1) {
        grid-column: 1 / 2;
    }

    .touch-sale-ui .touch-cart-list td:nth-child(2),
    .touch-sale-ui .touch-cart-list td:nth-child(3),
    .touch-sale-ui .touch-cart-list td:nth-child(4),
    .touch-sale-ui .touch-cart-list td:nth-child(5) {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .touch-sale-ui .touch-cart-list td:nth-child(2)::before,
    .touch-sale-ui .touch-cart-list td:nth-child(3)::before,
    .touch-sale-ui .touch-cart-list td:nth-child(4)::before,
    .touch-sale-ui .touch-cart-list td:nth-child(5)::before {
        font-size: 0.7rem;
        font-weight: 600;
        opacity: 0.75;
    }

    .touch-sale-ui .touch-cart-list td:nth-child(2)::before {
        content: 'Stock';
    }

    .touch-sale-ui .touch-cart-list td:nth-child(3)::before {
        content: 'Qty';
    }

    .touch-sale-ui .touch-cart-list td:nth-child(4)::before {
        content: 'Price';
    }

    .touch-sale-ui .touch-cart-list td:nth-child(5)::before {
        content: 'Subtotal';
    }

    .touch-sale-ui .touch-cart-list td:nth-child(2),
    .touch-sale-ui .touch-cart-list td:nth-child(3),
    .touch-sale-ui .touch-cart-list td:nth-child(4),
    .touch-sale-ui .touch-cart-list td:nth-child(5) {
        grid-column: 1 / 2;
    }

    .touch-sale-ui .touch-cart-list td:nth-child(6) {
        grid-column: 2 / 3;
        grid-row: 1 / 2;
        align-self: start;
        justify-self: end;
    }

    .touch-sale-ui .touch-cart-list input[data-qty],
    .touch-sale-ui .touch-cart-list input[data-unit-price],
    .touch-sale-ui .touch-cart-list input[data-subtotal],
    .touch-sale-ui .touch-cart-list select[data-variation] {
        width: 100%;
    }

    .touch-sale-ui .touch-cart-list [data-remove] {
        font-size: 0;
        line-height: 1;
    }

    .touch-sale-ui .touch-cart-list [data-remove]::after {
        content: '✕';
        font-size: 0.95rem;
        font-weight: 700;
    }

    @media (max-width: 1023px) {
        .touch-sale-ui .touch-cart-list tr {
            margin-bottom: 0.5rem;
        }
    }

    @media (max-width: 1024px) {
        .touch-sale-ui .touch-product-panel,
        .touch-sale-ui .touch-cart-panel {
            min-height: 44vh;
        }
    }

    @media (max-width: 767px) {
        .touch-sale-ui .touch-product-panel,
        .touch-sale-ui .touch-cart-panel {
            min-height: auto;
        }

        .touch-sale-ui .touch-product-panel > div:last-child,
        .touch-sale-ui .touch-cart-panel > div:last-child {
            max-height: 50vh;
        }
    }
</style>

<script>
    (function() {
        function formatMoney(value) {
            return '$' + Number(value || 0).toFixed(2);
        }

        function getCurrentCustomerType() {
            const customerTypeInput = document.getElementById('customer_type');
            const inputType = customerTypeInput?.value;
            if (inputType === 'local' || inputType === 'foreign') {
                return inputType;
            }

            const activeToggle = document.querySelector('#customerTypeToggle button.bg-primary-600');
            const toggleType = activeToggle?.getAttribute('data-customer-type');
            return toggleType === 'foreign' ? 'foreign' : 'local';
        }

        let lastCustomerType = null;
        let hooksInstalled = false;

        function syncProductPricesIfChanged() {
            const currentType = getCurrentCustomerType();
            if (currentType === lastCustomerType) return;
            lastCustomerType = currentType;
            syncProductPrices();
        }

        function installGlobalHooks() {
            if (hooksInstalled) return;

            const hasSetCustomerType = typeof window.setCustomerType === 'function';
            const hasSetActiveMode = typeof window.setActiveMode === 'function';

            if (!hasSetCustomerType && !hasSetActiveMode) return;

            if (hasSetCustomerType && !window.__touchPriceHookedSetCustomerType) {
                const originalSetCustomerType = window.setCustomerType;
                window.setCustomerType = function(type) {
                    originalSetCustomerType(type);
                    requestAnimationFrame(syncProductPrices);
                    requestAnimationFrame(() => requestAnimationFrame(syncProductPrices));
                };
                window.__touchPriceHookedSetCustomerType = true;
            }

            if (hasSetActiveMode && !window.__touchPriceHookedSetActiveMode) {
                const originalSetActiveMode = window.setActiveMode;
                window.setActiveMode = function(mode) {
                    originalSetActiveMode(mode);
                    requestAnimationFrame(syncProductPrices);
                };
                window.__touchPriceHookedSetActiveMode = true;
            }

            hooksInstalled = true;
        }

        function filterTouchProducts() {
            const input = document.querySelector('[data-role="touch-product-search"]');
            if (!input) return;

            const keyword = (input.value || '').trim().toLowerCase();
            document.querySelectorAll('[data-role="touch-product-card"]').forEach(card => {
                const haystack = card.dataset.searchText || '';
                const matched = keyword === '' || haystack.includes(keyword);
                card.classList.toggle('hidden', !matched);
            });
        }

        function syncProductPrices() {
            const currentType = getCurrentCustomerType();

            document.querySelectorAll('[data-role="touch-product-card"]').forEach(card => {
                const priceTarget = card.querySelector('[data-role="touch-product-price"]');
                if (!priceTarget) return;
                const priceValue = currentType === 'foreign' ? card.dataset.foreignPrice : card.dataset.localPrice;
                priceTarget.textContent = formatMoney(priceValue);
            });
        }

        document.addEventListener('click', function(event) {
            const productCard = event.target.closest('[data-role="touch-product-card"]');
            if (productCard) {
                const touchWrapper = productCard.closest('.sale-mode[data-mode="touch"]');
                const addItemButton = touchWrapper?.querySelector('[data-role="add-item"]');
                const tableBody = touchWrapper?.querySelector('[data-role="items-table"]');
                const selectedVariationId = productCard.dataset.variationId;

                if (!addItemButton || !tableBody || !selectedVariationId) return;

                addItemButton.click();

                requestAnimationFrame(() => {
                    const rows = tableBody.querySelectorAll('tr');
                    const newestRow = rows[rows.length - 1];
                    const variationSelect = newestRow?.querySelector('[data-variation]');
                    if (!variationSelect) return;

                    variationSelect.value = selectedVariationId;
                    variationSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });

                return;
            }

            if (event.target.closest('#customerTypeToggle button')) {
                requestAnimationFrame(syncProductPrices);
                requestAnimationFrame(() => requestAnimationFrame(syncProductPrices));
            }

        });

        document.addEventListener('DOMContentLoaded', syncProductPrices);
        document.addEventListener('input', function(event) {
            if (event.target.matches('#customer_type')) {
                syncProductPrices();
            }

            if (event.target.matches('[data-role="touch-product-search"]')) {
                filterTouchProducts();
            }
        });
        document.addEventListener('change', function(event) {
            if (event.target.matches('#customer_type')) {
                syncProductPrices();
            }
        });
        const customerTypeToggle = document.getElementById('customerTypeToggle');
        if (customerTypeToggle) {
            const observer = new MutationObserver(() => {
                requestAnimationFrame(syncProductPricesIfChanged);
            });
            observer.observe(customerTypeToggle, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });
        }

        requestAnimationFrame(syncProductPrices);
        setTimeout(syncProductPrices, 120);
        setTimeout(installGlobalHooks, 50);
        setTimeout(installGlobalHooks, 250);
        setTimeout(installGlobalHooks, 700);
        setInterval(syncProductPricesIfChanged, 350);
        requestAnimationFrame(filterTouchProducts);
    })();
</script>
