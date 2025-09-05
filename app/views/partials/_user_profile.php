<!-- app/views/partials/_user_profile.php -->
<!-- User Dropdown -->
<div class="dropdown">
    <a href="#" class="user-dropdown-toggle dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="<?= $user_avatar ?>" alt="User" width="32" height="32" class="rounded-circle">
        <span><?= $user_name ?></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#">Profile</a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li>
            <form action="/logout" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item w-100 text-start">Sign Out</button>
            </form>
        </li>
    </ul>
</div>