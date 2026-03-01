<?php

use Illuminate\Support\Facades\Route;

// App UI is served by the React frontend via Nginx.
// Keep a tiny route so default Laravel smoke tests remain valid.
Route::get('/', fn () => response('ok', 200));
