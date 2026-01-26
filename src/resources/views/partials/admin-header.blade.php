<nav class="header__nav">
    <ul class="header__nav__ul">
        <li>
        <li class="nav__link"><a href="{{route('admin.index')}}" class="nav__btn">勤怠一覧</li>
        <li class="nav__link"><a href="{{route('admin.staff.index')}}" class="nav__btn">スタッフ一覧</a></li>
        <li class="nav__link"><a href="{{route('request.index')}}" class="nav__btn">申請一覧</a></li>

        <form action="{{route('logout')}}" method="post">
            @csrf
            <input type="hidden" name="logout_type" value="admin">
            <button type="submit" class="logout">ログアウト</button>
        </form>
        </li>
    </ul>
</nav>

