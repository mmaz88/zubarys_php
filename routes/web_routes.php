<?php /**
  * routes/web.php - Web Routes
  *
  * REFACTORED: Now only contains authenticated application routes.
  * Public routes are in routes/public.php.
  */
declare(strict_types=1);



// --- Application Routes (Authenticated Area) ---
// Note: A middleware should wrap these in a real application.
// For now, AuthMiddleware is applied per-route group in other files.

get('/dashboard', function () {
    return view('dashboard', [
        'title' => 'Dashboard',
        'page_title' => 'Dashboard Overview',
    ], 'layout.main');
});