<ul class="metismenu" id="menu">
    @foreach ($menus as $menu)
        @php
            $hasChildren = $menu->children->isNotEmpty();

            $isActive =
                Route::currentRouteName() === $menu->route ||
                ($hasChildren && $menu->children->pluck('route')->contains(Route::currentRouteName()));
        @endphp

        <li class="{{ $isActive ? 'mm-active' : '' }}">
            <a href="{{ $hasChildren ? 'javascript:void(0);' : route($menu->route) ?? '#' }}"
                class="{{ $hasChildren ? 'has-arrow' : '' }}">
                <div class="parent-icon">
                    <i class="{{ $menu->icon }}"></i>
                </div>
                <div class="menu-title">{{ $menu->text }}</div>
            </a>

            @if ($hasChildren)
                <ul>
                    @foreach ($menu->children as $child)
                        <li class="{{ Route::currentRouteName() === $child->route ? 'mm-active' : '' }}">
                            <a href="{{ route($child->route) ?? '#' }}">
                                <i class='bx bx-radio-circle'></i>{{ $child->text }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
    <li>
        <a href="https://themeforest.net/user/codervent" target="_blank">
            <div class="parent-icon"><i class="bx bx-support"></i>
            </div>
            <div class="menu-title">Bantuan</div>
        </a>
    </li>
</ul>
