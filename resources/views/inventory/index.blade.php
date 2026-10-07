@extends('layouts.app')

@section('title', 'RAM-CIMS - Inventory')

@section('content')
    <div class="container mt-5">
        
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary fw-bold mb-0">RAM-CIMS Inventory Stock</h2>
                <small class="text-muted">APC Clinic Administration Module</small>
            </div>
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addItemModal">
                + Add New Item
            </button>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Code</th>
                            <th>Generic Name</th>
                            <th>Brand Name</th>
                            <th>Category</th>
                            <th>Quantity On Hand</th>
                            <th>Expiration Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplies as $item)
                            <tr>
                                <td><span class="fw-semibold text-secondary">{{ $item->ITEM_CODE }}</span></td>
                                <td class="fw-semibold text-secondary">{{ $item->GENERIC_NAME }}</td>
                                <td class="text-muted"><em>{{ $item->BRAND_NAME ?? 'N/A' }}</em></td>
                                <td><span class="fw-semibold text-secondary">{{ $item->ITEM_CATEGORY }}</span></td>
                                <td class="fw-bold {{ $item->ITEM_QUANTITY < 10 ? 'text-danger' : 'text-success' }}">
                                    {{ $item->ITEM_QUANTITY }} pcs
                                </td>
                                <td>{{ \Carbon\Carbon::parse($item->ITEM_EXPIRATION_DATE)->format('Y-M-d') }}</td>
                                <td class="text-center">
                                    <button type="button" 
                                        class="btn btn-sm btn-outline-primary fw-semibold edit-btn"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editItemModal"
                                        data-code="{{ $item->ITEM_CODE }}"
                                        data-generic="{{ $item->GENERIC_NAME }}"
                                        data-brand="{{ $item->BRAND_NAME }}"
                                        data-category="{{ $item->ITEM_CATEGORY }}"
                                        data-quantity="{{ $item->ITEM_QUANTITY }}"
                                        data-expiration="{{ $item->ITEM_EXPIRATION_DATE }}">
                                    Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No campus clinic supplies recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="addItemModalLabel">Register New Supply Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/inventory" method="POST">
                    @csrf
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label for="ITEM_CODE" class="form-label fw-semibold">Item Code</label>
                            <input type="text" class="form-control" id="ITEM_CODE" name="ITEM_CODE" placeholder="e.g., 1001" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ITEM_CATEGORY" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="ITEM_CATEGORY" name="ITEM_CATEGORY" required>
                                <option value="" disabled selected>Select category...</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Medical Supply">Medical Supply</option>
                                <option value="Equipment">Equipment</option>
                                <option value="First Aid">First Aid</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="GENERIC_NAME" class="form-label fw-semibold">Generic Name</label>
                            <input type="text" class="form-control" id="GENERIC_NAME" name="GENERIC_NAME" placeholder="e.g., Paracetamol" required>
                        </div>
                        <div class="col-12">
                            <label for="BRAND_NAME" class="form-label fw-semibold">Brand Name (Optional)</label>
                            <input type="text" class="form-control" id="BRAND_NAME" name="BRAND_NAME" placeholder="e.g., Biogesic">
                        </div>
                        <div class="col-md-6">
                            <label for="ITEM_QUANTITY" class="form-label fw-semibold">Initial Quantity</label>
                            <input type="number" class="form-control" id="ITEM_QUANTITY" name="ITEM_QUANTITY" min="0" placeholder="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ITEM_EXPIRATION_DATE" class="form-label fw-semibold">Expiration Date</label>
                            <input type="date" class="form-control" id="ITEM_EXPIRATION_DATE" name="ITEM_EXPIRATION_DATE" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-semibold">Save to Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal (ADDED) -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="editItemModalLabel">Edit Supply Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editItemForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label for="edit_ITEM_CODE" class="form-label fw-semibold">Item Code</label>
                            <input type="text" class="form-control" id="edit_ITEM_CODE" name="ITEM_CODE" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_ITEM_CATEGORY" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="edit_ITEM_CATEGORY" name="ITEM_CATEGORY" required>
                                <option value="Medicine">Medicine</option>
                                <option value="Medical Supply">Medical Supply</option>
                                <option value="Equipment">Equipment</option>
                                <option value="First Aid">First Aid</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_GENERIC_NAME" class="form-label fw-semibold">Generic Name</label>
                            <input type="text" class="form-control" id="edit_GENERIC_NAME" name="GENERIC_NAME" required>
                        </div>
                        <div class="col-12">
                            <label for="edit_BRAND_NAME" class="form-label fw-semibold">Brand Name (Optional)</label>
                            <input type="text" class="form-control" id="edit_BRAND_NAME" name="BRAND_NAME">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_ITEM_QUANTITY" class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" id="edit_ITEM_QUANTITY" name="ITEM_QUANTITY" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_ITEM_EXPIRATION_DATE" class="form-label fw-semibold">Expiration Date</label>
                           <input type="date" class="form-control" id="edit_ITEM_EXPIRATION_DATE" name="ITEM_EXPIRATION_DATE" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

    <!-- Scripts -->
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editItemModal');

            if (editModal) {
                // Bootstrap event triggered right when the edit modal opens
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget; // Button that triggered the modal
                
                    if (!button) return;

                    // Extract values directly from button attributes
                    const code = button.getAttribute('data-code') || '';
                    const generic = button.getAttribute('data-generic') || '';
                    const brand = button.getAttribute('data-brand') || '';
                    const category = button.getAttribute('data-category') || '';
                    const quantity = button.getAttribute('data-quantity') || '0';
                    const expiration = button.getAttribute('data-expiration') || '';

                    // Populate form input elements
                    document.getElementById('edit_ITEM_CODE').value = code;
                    document.getElementById('edit_GENERIC_NAME').value = generic;
                    document.getElementById('edit_BRAND_NAME').value = brand;
                    document.getElementById('edit_ITEM_CATEGORY').value = category;
                    document.getElementById('edit_ITEM_QUANTITY').value = quantity;

                    // Format date for <input type="date"> (YYYY-MM-DD)
                    if (expiration) {
                        const cleanDate = expiration.split(' ')[0];
                        document.getElementById('edit_ITEM_EXPIRATION_DATE').value = cleanDate;
                    } else {
                        document.getElementById('edit_ITEM_EXPIRATION_DATE').value = '';
                    }
                });
            }

            // Submit edit form via PUT AJAX request
            const editForm = document.getElementById('editItemForm');
            if (editForm) {
                editForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // Retrieve code directly from input or fallback
                    const codeInput = document.getElementById('edit_ITEM_CODE');
                    const code = codeInput ? codeInput.value : '';

                    if (!code) {
                        alert('Error: Item Code is missing.');
                        return;
                    }

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    // Explicit absolute origin URL construction
                    const targetUrl = `${window.location.origin}/inventory/${encodeURIComponent(code)}`;
                    fetch(targetUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify(data)
                    })
                    .then(async response => {
                        const result = await response.json();
                        if (response.status === 200) {
                            alert(result.message || 'Item updated successfully!');
                            location.reload();
                        } else {
                            const errorMsg = result.errors 
                                ? Object.values(result.errors).flat().join('\n') 
                                : (result.message || 'Failed to update item');
                            alert('Validation Error:\n' + errorMsg);
                        }
                    })
                    .catch(error => {
                        console.error('API Error:', error);
                        alert('Network error or server unreachable.');
                    });
                });
            }
        });
@endpush