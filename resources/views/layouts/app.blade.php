<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light">
        <title>
            @yield('title', 'Inicio')
            · Créditos y Abonos
        </title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}" defer></script>
        <script src="{{ asset('js/app.js') }}" defer></script>
    </head>
    <body>
        <a class="visually-hidden-focusable skip-link" href="#contenido">Ir al contenido</a>
        <div class="app-shell {{ auth()->guest() ? 'guest-shell' : '' }}">
            @auth
                <aside class="sidebar no-print">
                    <a href="{{ route('home') }}" class="brand">
                        <span class="brand-mark">
                            c
                            <span>+</span></span>
                        <span>
                            Créditos
                            <br>
                            <small>y Abonos</small>
                        </span>
                    </a>
                    <button
                        class="btn btn-dark mobile-toggle"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navegacion"
                        aria-expanded="false"
                        aria-controls="navegacion"
                    >
                        <x-icon name="menu" />
                        Menú
                    </button>
                    <nav id="navegacion" class="collapse sidebar-nav" aria-label="Navegación principal">
                        <span class="nav-caption">ESPACIO DE TRABAJO</span>
                        @foreach([
                            ['home','Inicio','home'],
                            ['clientes.index','Clientes','users'],
                            ['creditos.index','Créditos','wallet'],
                            ['abonos.index','Abonos','receipt'],
                            ['consultas','Consultas e historiales','clock'],
                            ['catalogos.index','Catálogos','settings'],
                        ] as [$ruta,$texto,$icono])
                            <a
                                class="nav-item {{ request()->routeIs($ruta === 'home' || $ruta === 'consultas' ? $ruta : explode('.', $ruta)[0].'.*') ? 'active' : '' }}"
                                href="{{ route($ruta) }}"
                            >
                                <x-icon :name="$icono" />
                                <span>{{ $texto }}</span>
                                @if($ruta==='home')
                                    <span class="nav-dot"></span>
                                @endif
                            </a>
                        @endforeach
                        <div class="sidebar-bottom">
                            <div class="local-note">
                                <span class="online-dot"></span>
                                Tu espacio local
                                <small>Información en tu equipo</small>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="nav-item w-100" type="submit">
                                    <x-icon name="logout" />
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </nav>
                </aside>
            @endauth
            <div class="main-area">
                <header class="topbar no-print">
                    <div class="breadcrumb-label">
                        @auth
                            Administración
                            <span>/</span>
                            @yield('title','Inicio')
                        @else
                            <a class="brand" href="{{ url('/') }}">
                                <span class="brand-mark">
                                    c
                                    <span>+</span></span>
                                <span>Créditos y Abonos</span>
                            </a>
                        @endauth
                    </div>
                    @auth
                        <div class="d-flex align-items-center gap-3">
                            <span class="today-label">
                                {{ now()->format('d/m/Y') }}
                            </span>
                            <div class="dropdown">
                                <button
                                    class="account-button"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    aria-label="Abrir menú de cuenta"
                                >
                                    <span class="avatar">
                                        {{ mb_substr(auth()->user()->name,0,1) }}
                                    </span>
                                    <span class="account-name">
                                        {{ auth()->user()->name }}
                                    </span>
                                    <x-icon name="chevron" />
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <span class="dropdown-item-text small text-muted">
                                            {{ auth()->user()->username }}
                                        </span>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button class="dropdown-item" type="submit">Cerrar sesión</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <span class="local-pill">
                            <span class="online-dot"></span>
                            Administración local
                        </span>
                    @endauth
                </header>
                <main id="contenido" class="page-content" tabindex="-1">
                    <x-alerts />
                    @yield('content')
                </main>
                <footer class="page-footer no-print">
                    Créditos y Abonos
                    <span>Control claro. Cuentas en orden.</span>
                </footer>
            </div>
        </div>
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="confirmTitle">Confirmar operación</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p id="confirmText" class="mb-0"></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Volver</button>
                        <button class="btn btn-primary" id="confirmSubmit" type="button">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
