<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $pageTitle ?? 'Admin Panel' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Подключаем jQuery (обязательно перед Summernote) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Подключаем Summernote -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/all.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/select2.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/select2-bootstrap4.min.css') }}"> 
	<link rel="stylesheet" href="{{ asset('assets/admin/css/adminlte.min.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/admin.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/myadmin.css') }}?v=16">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/wt_filter_admin.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/admin/css/filemanager.css') }}">
    <style>
        .nav-sidebar .has-treeview.menu-open > .nav-treeview {
            display: block !important;
        }
    </style>

</head>

<body class="hold-transition sidebar-mini admin-theme-modern">

    
<!-- Site wrapper -->
<div class="wrapper">


    
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('admin.index') }}" class="nav-link">Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('catalog.index') }}" class="nav-link" target="_blank">Storefront</a>
            </li>
        </ul>

        <!-- SEARCH FORM -->
        <form class="form-inline ml-3" id="admin-product-search" action="{{ route('admin.products.index') }}" method="get" autocomplete="off" style="position:relative;">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="text" name="search"
                       id="admin-search-q" placeholder="Товар (от 2 букв)…" aria-label="Поиск товаров"
                       value="{{ request('search') }}" autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Messages Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-comments"></i>
                    <span class="badge badge-danger navbar-badge">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="{{ asset('assets/admin/img/user1-128x128.jpg') }}" alt="User Avatar"
                                 class="img-size-50 mr-3 img-circle">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">Call me whenever you can...</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="{{ asset('assets/admin/img/user8-128x128.jpg') }}" alt="User Avatar"
                                 class="img-size-50 img-circle mr-3">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    John Pierce
                                    <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">I got your message bro</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="{{ asset('assets/admin/img/user3-128x128.jpg') }}" alt="User Avatar"
                                 class="img-size-50 img-circle mr-3">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Nora Silvester
                                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">The subject goes here</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                </div>
            </li>
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">15</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">15 Notifications</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-envelope mr-2"></i> 4 new messages
                        <span class="float-right text-muted text-sm">3 mins</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-users mr-2"></i> 8 friend requests
                        <span class="float-right text-muted text-sm">12 hours</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-file mr-2"></i> 3 new reports
                        <span class="float-right text-muted text-sm">2 days</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" target="_blank" class="brand-link">
            <img src="{{ asset('assets/admin/img/AdminLTELogo.png') }}"
                 alt="AdminLTE Logo"
                 class="brand-image img-circle elevation-3"
                 style="opacity: 0;">
            <span class="brand-text font-weight-light">PRO1 Admin</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ asset('assets/admin/img/user2-160x160.jpg') }}" class="img-circle elevation-2"
                         alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block">Admin</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            @php
                $catalogOpen = request()->routeIs([
                    'admin.categories.*',
                    'admin.products.*',
                    'admin.attribute-groups.*',
                    'admin.attributes.*',
                    'admin.options.*',
                    'admin.manufacturers.*',
                ]);
                $wtFilterOpen = request()->routeIs([
                    'admin.wt-filter.*',
                ]);
                $blogOpen = request()->routeIs([
                    'admin.articles.*',
                    'admin.blog-categories.*',
                    'admin.article-reviews.*',
                    'admin.blog-settings.*',
                ]);
            @endphp
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <li class="nav-item">
                        <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item has-treeview {{ $catalogOpen ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $catalogOpen ? 'active' : '' }}">
                            <i class="nav-icon fas fa-folder-open"></i>
                            <p>Catalog <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview" @if($catalogOpen) style="display:block;" @endif>
                            <li class="nav-item">
                                <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Categories</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Products</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.attribute-groups.index') }}" class="nav-link {{ request()->routeIs('admin.attribute-groups.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Attribute Groups</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.attributes.index') }}" class="nav-link {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Attributes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.options.index') }}" class="nav-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Options</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.manufacturers.index') }}" class="nav-link {{ request()->routeIs('admin.manufacturers.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Manufacturers</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item has-treeview {{ $blogOpen ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $blogOpen ? 'active' : '' }}">
                            <i class="nav-icon fas fa-newspaper"></i>
                            <p>Blog <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview" @if($blogOpen) style="display:block;" @endif>
                            <li class="nav-item">
                                <a href="{{ route('admin.articles.index') }}" class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Статьи</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.blog-categories.index') }}" class="nav-link {{ request()->routeIs('admin.blog-categories.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Категории</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.article-reviews.index') }}" class="nav-link {{ request()->routeIs('admin.article-reviews.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Отзывы</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.blog-settings.edit') }}" class="nav-link {{ request()->routeIs('admin.blog-settings.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Настройки</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item has-treeview {{ $wtFilterOpen ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $wtFilterOpen ? 'active' : '' }}">
                            <i class="nav-icon fas fa-filter"></i>
                            <p>WT Filter <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview" @if($wtFilterOpen) style="display:block;" @endif>
                            <li class="nav-item">
                                <a href="{{ route('admin.wt-filter.options') }}" class="nav-link {{ request()->routeIs('admin.wt-filter.options*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Опции фильтра</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.wt-filter.settings') }}" class="nav-link {{ request()->routeIs('admin.wt-filter.settings*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Настройки</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.wt-filter.copy-attributes') }}" class="nav-link {{ request()->routeIs('admin.wt-filter.copy-attributes*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Копирование фильтров</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('catalog.index') }}" class="nav-link" target="_blank">
                            <i class="nav-icon fas fa-store"></i>
                            <p>Vitrina</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.languages.index') }}" class="nav-link {{ request()->routeIs('admin.languages.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-language"></i>
                            <p>Languages</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Roles</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper zip">

        <div class="container mt-2">
            <div class="row">
                <div class="col-12">
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger">
                            <ul class="list-unstyled">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session()->has('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @yield('content')
    </div>
    <!-- /.content-wrapper -->
    <script src="{{ asset('assets/admin/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/select2.full.js') }}"></script>
    <script src="{{ asset('assets/admin/js/adminlte.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/demo.js') }}"></script>
    <script src="{{ asset('assets/admin/js/admin.js') }}"></script>
    <script src="{{ asset('assets/admin/js/myadmin.js') }}?v=2"></script>
    <script>
      window.ocFilemanager = {
        index: @json(route('admin.filemanager.index', [], false)),
        editorUpload: @json(route('admin.filemanager.editor-upload', [], false))
      };
    </script>
    <script src="{{ asset('assets/admin/js/filemanager.js') }}?v=2"></script>
    
    <script src="{{ asset('assets/admin/js/wysiwyg.js') }}?v=4"></script>
                         


    <footer class="main-footer">
        <strong>PRO1</strong> Admin Panel
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
<div id="modal-image" class="modal fade" tabindex="-1" role="dialog"></div>

@stack('admin_scripts')
@stack('scripts')
<script src="{{ asset('js/admin/admin-search-suggest.js') }}?v=1" defer></script>

</body>
</html>
