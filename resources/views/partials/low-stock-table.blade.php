<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase" style="font-size:.78rem;">
            <tr>
                <th class="ps-4">Item Code</th>
                <th>Generic Name</th>
                <th>Brand Name</th>
                <th>Dosage</th>
                <th>Category</th>
                <th>Expiration Date</th>
                <th class="text-end pe-4">Quantity Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td class="ps-4 fw-semibold text-secondary">#{{ $item->ITEM_CODE }}</td>
                    <td class="fw-medium">{{ $item->GENERIC_NAME }}</td>
                    <td>{{ $item->BRAND_NAME ?: 'N/A' }}</td>
                    <td>{{ $item->dosageLabel() }}</td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $item->ITEM_CATEGORY }}</span></td>
                    <td class="text-muted">{{ $item->ITEM_EXPIRATION_DATE ? \Carbon\Carbon::parse($item->ITEM_EXPIRATION_DATE)->format('Y-m-d') : 'N/A' }}</td>
                    <td class="text-end pe-4">
                        <span class="badge bg-danger px-2 py-1">{{ $item->ITEM_QUANTITY }} units</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">All inventory stock levels are normal.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
