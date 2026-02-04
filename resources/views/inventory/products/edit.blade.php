<x-app-layout>
    <x-slot name="title">Edit Product - {{ app('tenant')->name }}</x-slot>

    <div class="flex h-screen bg-background-light dark:bg-background-dark">
        <x-sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">
            <x-header>
                <x-slot name="title">Edit Product</x-slot>
            </x-header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <div class="max-w-2xl mx-auto">
                    <div class="bg-white dark:bg-secondary-900 rounded-xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                        <form action="{{ route('inventory.products.update', $product) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="name" class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2">Product Name *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                                    class="w-full px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300">Variations</label>
                                    <button type="button" id="addVariationBtn" class="px-3 py-1.5 text-sm bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 rounded-lg hover:bg-secondary-200 dark:hover:bg-secondary-700">Add Variation</button>
                                </div>
                                <div id="variationsList" class="space-y-3">
                                    @foreach($product->variations as $index => $variation)
                                        <div class="flex items-center gap-3">
                                            <input type="hidden" name="variations[{{ $index }}][id]" value="{{ $variation->id }}">
                                            <input type="text" name="variations[{{ $index }}][name]" value="{{ old('variations.' . $index . '.name', $variation->name) }}" placeholder="Variation name"
                                                class="flex-1 px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                                            <button type="button" class="text-red-600 hover:text-red-900 dark:text-red-400" data-remove>Remove</button>
                                        </div>
                                    @endforeach
                                </div>
                                @error('variations')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-6">
                                <a href="{{ route('inventory.products.index') }}" class="px-4 py-2 text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white">Cancel</a>
                                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const variationsList = document.getElementById('variationsList');
        const addVariationBtn = document.getElementById('addVariationBtn');
        let variationIndex = {{ $product->variations->count() }};

        function addVariationRow(value = '') {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3';
            row.innerHTML = `
                <input type="text" name="variations[${variationIndex}][name]" value="${value}" placeholder="Variation name"
                    class="flex-1 px-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-secondary-700 dark:text-white">
                <button type="button" class="text-red-600 hover:text-red-900 dark:text-red-400" data-remove>Remove</button>
            `;
            variationsList.appendChild(row);
            variationIndex += 1;

            row.querySelector('[data-remove]').addEventListener('click', () => row.remove());
        }

        addVariationBtn.addEventListener('click', () => addVariationRow());
        variationsList.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', event => event.currentTarget.closest('div').remove());
        });
    </script>
</x-app-layout>
