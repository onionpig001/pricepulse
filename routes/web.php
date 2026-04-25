<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/llms.txt', [PublicController::class, 'llmsTxt']);
Route::get('/tool/{slug}.md', [PublicController::class, 'showMarkdown'])->where('slug', '[a-z0-9-]+');
Route::get('/tool/{slug}', [PublicController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('tool');
Route::get('/compare/{pair}.md', [PublicController::class, 'compareMarkdown'])->where('pair', '[a-z0-9-]+');
Route::get('/compare/{pair}', [PublicController::class, 'compare'])->where('pair', '[a-z0-9-]+')->name('compare');
