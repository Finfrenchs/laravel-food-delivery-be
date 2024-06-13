<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="index.html">Food Delivery Dashboard</a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="index.html">FDD</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Dashboard</li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link has-dropdown"><i class="fas fa-fire"></i><span>Dashboard</span></a>
                <ul class="dropdown-menu">
                    <li class='{{ Request::is('dashboard-general-dashboard') ? 'active' : '' }}'>
                        <a class="nav-link" href="{{ url('dashboard-general-dashboard') }}">General Dashboard</a>
                    </li>

                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#"
                    class="nav-link has-dropdown"><i class="fa-solid fa-users"></i><span>Users</span></a>
                <ul class="dropdown-menu">
                    <li >
                        <a class="nav-link"
                            href="{{ route('user.index') }}">User List</a>
                    </li>
                </ul>
            </li>

           {{-- <li class="nav-item dropdown">
                <a href="#"
                    class="nav-link has-dropdown"><i class="fa-solid fa-box"></i><span>Category</span></a>
                <ul class="dropdown-menu">
                    <li >
                        <a class="nav-link"
                            href="{{ route('categories.index') }}">Categories</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#"
                    class="nav-link has-dropdown"><i class="fa-solid fa-box"></i><span>Products</span></a>
                <ul class="dropdown-menu">
                    <li >
                        <a class="nav-link"
                            href="{{ route('products.index') }}">Product List</a>
                    </li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#"
                    class="nav-link has-dropdown"><i class="fa-solid fa-cart-flatbed"></i><span>Orders</span></a>
                <ul class="dropdown-menu">
                    <li >
                        <a class="nav-link"
                            href="{{ route('orders.index') }}">Order List</a>
                    </li>
                </ul>
            </li> --}}

            {{-- <li class="nav-item dropdown">
                <a href="#"
                    class="nav-link has-dropdown"><i class="fa-solid fa-chart-line"></i><span>Sales Report</span></a>
                <ul class="dropdown-menu">
                    <li >
                        <a class="nav-link"
                            href="{{ route('report.index') }}">Sales Range Report Data</a>
                    </li>
                    <li >
                        <a class="nav-link"
                            href="{{ route('report.sales.report.data') }}">Sales Report Data</a>
                    </li>
                </ul>
            </li> --}}

            {{-- <li class="menu-header">Report</li>

            <li class={{ Request::is('report*') ? 'active' : '' }}>
                <a class="nav-link" href="{{ route('report.index') }}">
                <i class="fas fa-book"></i> <span>Report</span></a>
            </li> --}}

    </aside>
</div>
