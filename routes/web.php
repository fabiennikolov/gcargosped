<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WhatsappClickController;
use Illuminate\Support\Facades\Route;

/*
 * Public URLs deliberately mirror the old Webflow site one-for-one, so every
 * page Google already has indexed keeps resolving with no redirect hop:
 *   /about  /services  /contact  /blog  /blog/{slug}
 *   /service/{slug}        (3 headline services)
 *   /sub-services/{slug}   (17 specialised services)
 */

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/services', [ServiceController::class, 'index'])->name('services');
Route::get('/service/{service:slug}', [ServiceController::class, 'showMain'])->name('service.show');
Route::get('/sub-services/{service:slug}', [ServiceController::class, 'showSub'])->name('subservice.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::post('/inquiries', [InquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('inquiries.store');

/*
 * Taps on the floating WhatsApp button. The conversation itself happens on the
 * owner's phone where we see nothing, so this counter is the only measure of
 * what the button is worth.
 */
Route::post('/track/whatsapp', WhatsappClickController::class)
    ->middleware('throttle:20,1')
    ->name('track.whatsapp');

Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

/*
 * The starter kit's own login, registration and profile screens have been
 * removed: the site is a public brochure with no customer area, and a second
 * login next to Filament's was only a way in for people who should not have
 * one. Authentication lives entirely in the Filament panel — /admin/login.
 */
