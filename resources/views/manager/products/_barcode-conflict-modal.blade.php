@if(session('barcode_conflict_product_name'))
    <x-modal-info id="barcode-conflict" title="Code-barres deja existant chez vous">
        <div class="space-y-3 text-sm text-gray-700">
            <p>Code-barres deja existant chez vous.</p>
            <p>
                Produit associe :
                <span class="font-semibold text-gray-900">{{ session('barcode_conflict_product_name') }}</span>
            </p>
        </div>
    </x-modal-info>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('open-modal-barcode-conflict'));
        });
    </script>
@endif
