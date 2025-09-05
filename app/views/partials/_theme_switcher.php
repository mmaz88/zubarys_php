<!-- app/views/partials/_theme_switcher.php -->
<div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm" type="button" id="theme-menu" data-bs-toggle="dropdown"
        aria-expanded="false">
        <ion-icon name="color-palette-outline"></ion-icon>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="theme-menu">
        <li>
            <a class="dropdown-item" href="#" data-set-theme="light">
                <ion-icon name="sunny-outline" class="me-2"></ion-icon> Light
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="dark">
                <ion-icon name="moon-outline" class="me-2"></ion-icon> Dark
            </a>
        </li>
        <li>
            <hr class="dropdown-divider">
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="red">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--red));"></ion-icon> Red
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="rose">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--rose));"></ion-icon> Rose
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="orange">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--orange));"></ion-icon> Orange
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="green">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--green));"></ion-icon> Green
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="blue">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--blue));"></ion-icon> Blue
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="yellow">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--yellow));"></ion-icon> Yellow
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-set-theme="violet">
                <ion-icon name="ellipse" class="me-2" style="color: hsl(var(--violet));"></ion-icon> Violet
            </a>
        </li>
    </ul>
</div>