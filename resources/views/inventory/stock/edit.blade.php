<x-app-layout>
    <x-slot name="title">Edit Stock - {{ app('tenant')->name }}</x-slot>

    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Edit Stock Entry</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-3xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                        <form action="{{ route('inventory.stock.update', $stock) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="product_id" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Product *</label>
                                    <select name="product_id" id="product_id" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="">Select product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id', $stock->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="product_variation_id" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Variation *</label>
                                    <select name="product_variation_id" id="product_variation_id" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                        <option value="">Select variation</option>
                                        @foreach($variations as $variation)
                                            <option value="{{ $variation->id }}" data-product="{{ $variation->product_id }}" {{ old('product_variation_id', $stock->product_variation_id) == $variation->id ? 'selected' : '' }}>
                                                {{ $variation->product?->name }} - {{ $variation->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_variation_id')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="quantity" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Quantity *</label>
                                    <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $stock->quantity) }}" min="0" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('quantity')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="purchasing_price" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Purchasing Price *</label>
                                    <input type="number" step="0.01" name="purchasing_price" id="purchasing_price" value="{{ old('purchasing_price', $stock->purchasing_price) }}" min="0" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('purchasing_price')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="local_selling_price" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Local Selling Price *</label>
                                    <input type="number" step="0.01" name="local_selling_price" id="local_selling_price" value="{{ old('local_selling_price', $stock->local_selling_price) }}" min="0" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('local_selling_price')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="foreign_selling_price" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Foreign Selling Price *</label>
                                    <input type="number" step="0.01" name="foreign_selling_price" id="foreign_selling_price" value="{{ old('foreign_selling_price', $stock->foreign_selling_price) }}" min="0" required
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('foreign_selling_price')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="manufacturing_date" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Manufacturing Date</label>
                                    <input type="date" name="manufacturing_date" id="manufacturing_date" value="{{ old('manufacturing_date', optional($stock->manufacturing_date)->format('Y-m-d')) }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('manufacturing_date')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="expiry_date" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Expiry Date</label>
                                    <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', optional($stock->expiry_date)->format('Y-m-d')) }}"
                                        class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                    @error('expiry_date')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-6">
                                <a href="{{ route('inventory.stock.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">Cancel</a>
                                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Update Stock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const productSelect = document.getElementById('product_id');
        const variationSelect = document.getElementById('product_variation_id');

        function filterVariations() {
            const productId = productSelect.value;
            Array.from(variationSelect.options).forEach(option => {
                if (!option.value) return;
                option.hidden = option.dataset.product !== productId;
            });
        }

        productSelect.addEventListener('change', filterVariations);
        filterVariations();
    </script>
</x-app-layout>
