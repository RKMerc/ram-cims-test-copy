@extends('layouts.app')

@section('title', 'RAM-CIMS - User Accounts')

@section('content')
<style>
@media (min-width: 992px) {
    .user-number-label { white-space: nowrap; }
}
</style>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1" style="color:#003B7A;">User Accounts</h1>
    <p class="text-muted mb-0">Update clinic accounts, roles, and medical-staff credentials.</p>
</div>

<form method="GET" action="{{ route('users.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <label for="account-search" class="form-label fw-semibold">Search accounts</label>
        <div class="row g-2">
            <div class="col-12 col-md-8 col-lg-6">
                <input type="search" name="q" id="account-search" value="{{ $search ?? '' }}" class="form-control" placeholder="Name, email, number, or role">
            </div>
            <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-semibold">Search</button>
                @if(($search ?? '') !== '')
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark text-uppercase" style="font-size:.78rem;">
                <tr>
                    <th class="ps-4">Name</th>
                    <th>Email</th>
                    <th>Number</th>
                    <th>Role</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $user->fullName() }}</td>
                        <td>{{ $user->EmailAddress }}</td>
                        <td>{{ $user->Student_Employee_No ?: '—' }}</td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold"
                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                data-id="{{ $user->Id }}"
                                data-first="{{ $user->FirstName }}"
                                data-middle="{{ $user->MiddleName }}"
                                data-last="{{ $user->LastName }}"
                                data-email="{{ $user->EmailAddress }}"
                                data-number="{{ $user->Student_Employee_No }}"
                                data-contact="{{ $user->ContactNo }}"
                                data-type="{{ $user->UserTypeId }}"
                                data-sub="{{ $user->medicalStaff?->SubUserTypeId }}"
                                data-license="{{ $user->medicalStaff?->LicenseNo }}">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">{{ ($search ?? '') !== '' ? 'No accounts match that search.' : 'No user accounts found.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="card-body border-top">{{ $users->links() }}</div>
    @endif
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" id="editUserDialog">
        <form method="POST" id="editUserForm" action="{{ url('/users/0') }}" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header text-white" style="background:#003B7A;">
                <h5 class="modal-title fw-bold">Edit User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="user_first">First Name</label>
                    <input type="text" name="FirstName" id="user_first" class="form-control" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="user_middle">Middle Name</label>
                    <input type="text" name="MiddleName" id="user_middle" class="form-control">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="user_last">Last Name</label>
                    <input type="text" name="LastName" id="user_last" class="form-control" required>
                </div>
                <div class="col-12 col-lg-5">
                    <label class="form-label fw-semibold" for="user_email">Email</label>
                    <input type="email" name="EmailAddress" id="user_email" class="form-control" required>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label fw-semibold user-number-label" for="user_number" id="user_number_label">Student/Employee Number</label>
                    <input type="text" name="Student_Employee_No" id="user_number" class="form-control">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label fw-semibold" for="user_contact">Contact</label>
                    <input type="text" name="ContactNo" id="user_contact" class="form-control">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold" for="user_type">User Type</label>
                    <select name="UserTypeId" id="user_type" class="form-select" required>
                        @foreach($types as $type)
                            <option value="{{ $type->Id }}">{{ $type->Name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" id="medicalStaffFields" hidden>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="user_sub">Medical Staff Role</label>
                            <select name="SubUserTypeId" id="user_sub" class="form-select">
                                <option value="">Select a role</option>
                                @foreach($subtypes as $subtype)
                                    @if(in_array($subtype->Name, ['Physician', 'Nurse', 'Dentist'], true))
                                        <option value="{{ $subtype->Id }}">{{ $subtype->Name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="user_license">License No.</label>
                            <input type="text" name="LicenseNo" id="user_license" class="form-control" maxlength="30">
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold">Save Account</button>
            </div>
        </form>
    </div>
</div>
@endsection

<style>
    #editUserDialog { width: min(720px, calc(100vw - 1.5rem)); max-width: calc(100vw - 1.5rem); margin-inline: auto; }
    #editUserModal .modal-content { overflow: hidden; }
    #editUserModal .modal-body { overflow-x: hidden; }
    #editUserModal .modal-footer { flex-wrap: wrap; gap: .5rem; }
    #editUserModal .modal-footer .btn { white-space: normal; }
</style>
@push('scripts')
<script>
const userTypeNames = @json($typeNames);
let openedAsClinicalAdmin = false;

function selectedUserTypeName() {
    return userTypeNames[document.getElementById('user_type').value] || '';
}

function syncMedicalStaffFields() {
    const typeName = selectedUserTypeName();
    const show = typeName === 'Medical Staff' || (typeName === 'Admin' && openedAsClinicalAdmin);
    const fields = document.getElementById('medicalStaffFields');
    const role = document.getElementById('user_sub');
    const license = document.getElementById('user_license');
    fields.hidden = !show;
    role.disabled = !show;
    license.disabled = !show;
    if (!show) {
        role.value = '';
        license.value = '';
    }
    document.getElementById('user_number_label').textContent = typeName === 'Medical Staff'
        ? 'Employee No.'
        : 'Student / Employee No.';
}

document.getElementById('user_type')?.addEventListener('change', syncMedicalStaffFields);
document.getElementById('editUserModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button) return;
    const form = document.getElementById('editUserForm');
    form.action = @json(url('/users')) + '/' + button.getAttribute('data-id');
    document.getElementById('user_first').value = button.getAttribute('data-first') || '';
    document.getElementById('user_middle').value = button.getAttribute('data-middle') || '';
    document.getElementById('user_last').value = button.getAttribute('data-last') || '';
    document.getElementById('user_email').value = button.getAttribute('data-email') || '';
    document.getElementById('user_number').value = button.getAttribute('data-number') || '';
    document.getElementById('user_contact').value = button.getAttribute('data-contact') || '';
    document.getElementById('user_type').value = button.getAttribute('data-type') || '';
    document.getElementById('user_sub').value = button.getAttribute('data-sub') || '';
    document.getElementById('user_license').value = button.getAttribute('data-license') || '';
    const originalType = userTypeNames[button.getAttribute('data-type')] || '';
    openedAsClinicalAdmin = originalType === 'Admin' && !!button.getAttribute('data-sub');
    syncMedicalStaffFields();
});
</script>
@endpush
