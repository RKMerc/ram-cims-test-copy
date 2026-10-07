<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'RAM-CIMS')</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/dashboard') }}">RAM-CIMS</a>
            <!-- Added flex-row to force horizontal alignment -->
            <div class="navbar-nav flex-row ms-auto">
                <a class="nav-link px-2" href="{{ url('/dashboard') }}">Dashboard</a>
                <a class="nav-link px-2" href="{{ url('/appointments') }}">Appointments</a>
                <a class="nav-link px-2" href="{{ url('/inventory') }}">Inventory</a>
                <a class="nav-link px-2" href="{{ url('/medical-records') }}">Medical Records</a>
                <!--<a class="nav-link px-2" href="{{ url('/user-management') }}">User Management</a>-->
            </div>
        </div>
    </nav>

    <main class="container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @yield('content')
    </main>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>