<nav class="header__nav">
    <ul class="header__nav__ul">
        <li>
            <li class="nav__link"><a href="{{route('user.attendance')}}" class="nav__btn">勤怠</li>
            <li class="nav__link"><a href="{{route('user.index')}}" class="nav__btn">勤怠一覧</a></li>
            <li class="nav__link"><a href="{{route('request.index')}}" class="nav__btn">申請</a></li>

            <form action="{{route('logout')}}" method="post">
                @csrf
                <button type="submit" class="logout">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
