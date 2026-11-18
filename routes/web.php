<?php

use App\Http\Controllers\Admin\ChooseThemeController;
use App\Http\Controllers\Admin\IdgiReplyController as AdminIdgiReplyController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PronunciationUploadController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\TermController as AdminTermController;
use App\Http\Controllers\Admin\TermVersionController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\ThemePreviewController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\TermApiController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GlossaryController;
use App\Http\Controllers\IdgiController;
use App\Http\Controllers\IdgiReplyController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public glossary
|--------------------------------------------------------------------------
|
| These URLs are unchanged from the original site so existing links,
| bookmarks and search-engine results keep working.
|
*/

Route::get('/', [GlossaryController::class, 'home'])->name('home');
Route::get('listing/{letter}', [GlossaryController::class, 'listing'])->name('listing');
Route::get('term', [GlossaryController::class, 'term'])->name('term.none');
Route::get('term/{term}', [GlossaryController::class, 'term'])->where('term', '.*')->name('term');

Route::get('search', fn () => redirect('/'));
Route::post('search', [GlossaryController::class, 'searchRedirect'])->name('search.submit');
Route::get('search/{query}', [GlossaryController::class, 'search'])->where('query', '.*')->name('search');

Route::get('about/top-ten', [GlossaryController::class, 'topTen'])->name('top-ten');
Route::get('about/{slug}', [PageController::class, 'show'])->name('about');
Route::get('the-api/{slug}', [PageController::class, 'show'])->name('the-api');
Route::get('get-in-touch/{slug}', [PageController::class, 'contact'])->name('get-in-touch');

Route::middleware('throttle:6,1')->group(function () {
    Route::post('contact/send_feedback', [ContactController::class, 'feedback'])->name('contact.feedback');
    Route::post('contact/suggest_term', [ContactController::class, 'suggestTerm'])->name('contact.suggest-term');
    Route::post('contact/suggest_translation', [ContactController::class, 'suggestTranslation'])->name('contact.suggest-translation');
});

Route::post('idgi/{term}', IdgiController::class)->middleware('throttle:10,1')->name('idgi');
Route::post('idgi/{term}/reply', IdgiReplyController::class)->middleware('throttle:6,1')->name('idgi.reply');

Route::get('sitemap', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Embeds and the v1 API
|--------------------------------------------------------------------------
|
| Called from other websites (the WordPress plugin, iframes). Their
| addresses and response formats must not change.
|
*/

Route::get('widget', WidgetController::class)->name('widget');
Route::get('v1/terms', [TermApiController::class, 'index']);
Route::get('v1/terms/{term}', [TermApiController::class, 'show'])->where('term', '.*');
Route::get('v1/hit/{query}', [TermApiController::class, 'hit'])->where('query', '.*');
Route::get('v1/terms-list', [TermApiController::class, 'list']);

// Suggestions for the site's own search box.
Route::get('ajax/terms', [TermApiController::class, 'suggest'])->name('ajax.terms');

/*
|--------------------------------------------------------------------------
| Signed-in users
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('ajax/autocomplete', [TermApiController::class, 'names'])->name('ajax.autocomplete');

    Route::get('comments/{term}', [CommentController::class, 'thread'])->where('term', '.*')->name('comments.thread');
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Terms: administrators manage all of them, editors the ones assigned to them.
    Route::get('terms', [AdminTermController::class, 'index'])->name('terms.index');
    Route::middleware('can:admin')->group(function () {
        Route::get('terms/create', [AdminTermController::class, 'create'])->name('terms.create');
        Route::post('terms', [AdminTermController::class, 'store'])->name('terms.store');
    });
    Route::get('terms/{master}/version/{version}', [TermVersionController::class, 'show'])
        ->whereNumber(['master', 'version'])->name('terms.version');
    Route::post('terms/{term}/rollback', [TermVersionController::class, 'rollback'])->name('terms.rollback');
    Route::get('terms/{term}', [AdminTermController::class, 'show'])->name('terms.show');
    Route::get('terms/{term}/edit', [AdminTermController::class, 'edit'])->name('terms.edit');
    Route::put('terms/{term}', [AdminTermController::class, 'update'])->name('terms.update');

    // What readers said after pressing "I don't get it" (IdgiReplyPolicy decides who).
    Route::patch('idgi-replies/{reply}', [AdminIdgiReplyController::class, 'update'])->name('idgi-replies.update');
    Route::delete('idgi-replies/{reply}', [AdminIdgiReplyController::class, 'destroy'])->name('idgi-replies.destroy');

    Route::post('upload', UploadController::class)->middleware('can:upload')->name('upload');
    Route::post('upload/pronunciation', PronunciationUploadController::class)->middleware('can:upload')->name('upload.pronunciation');

    Route::middleware('can:admin')->group(function () {
        Route::resource('pages', AdminPageController::class);
        Route::resource('users', AdminUserController::class)->except('show');

        // The site's details, and its look.
        Route::get('site', [SiteController::class, 'edit'])->name('site.edit');
        Route::put('site', [SiteController::class, 'update'])->name('site.update');
        Route::put('site/theme', ChooseThemeController::class)->name('site.theme');

        Route::post('themes/preview', ThemePreviewController::class)->name('themes.preview');
        Route::resource('themes', ThemeController::class)->except(['create', 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Old addresses
|--------------------------------------------------------------------------
*/

Route::redirect('home', '/');
Route::redirect('auth/login', '/login');
Route::redirect('auth/register', '/register');
Route::redirect('auth/logout', '/');
Route::redirect('password/email', '/forgot-password');
Route::get('password/reset/{token}', fn (string $token) => redirect('/reset-password/'.$token));

require __DIR__.'/settings.php';
