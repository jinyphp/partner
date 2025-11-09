<!-- Collapse -->
<div class="collapse navbar-collapse" id="navbar-default">
    <ul class="navbar-nav mx-auto">
        @foreach(($menuItems ?? Site::menuItems('second')) as $menuItem)
            @include('jiny-subscribe::partials.navs.second.menu-item', ['item' => $menuItem])
        @endforeach
    </ul>
</div>
