<?php
// This file contains the API routes for the Page module.

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\Backend\PageController;

// Route to get all pages with auth middleware

Route::get('pages', [PageController::class, 'index']);