<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ArticleReviewController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeGroupController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogSettingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Admin\ManufacturerController;
use App\Http\Controllers\Admin\OptionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WtFilterController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Catalog\BlogController;
use App\Http\Controllers\Catalog\CartController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Catalog\CheckoutController;
use App\Support\CatalogLocale;
use App\Support\CatalogUrl;
use Illuminate\Support\Facades\Route;

$registerCatalogCore = function (): void {
    Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/search', [CatalogController::class, 'search'])->name('catalog.search');
    Route::get('/search/suggest', [CatalogController::class, 'suggest'])->name('catalog.search.suggest');

    Route::post('/wt-filter/callback', [\App\Http\Controllers\Catalog\WtFilterController::class, 'callback'])
        ->name('catalog.wt-filter.callback');
    Route::post('/wt-filter/refresh', [\App\Http\Controllers\Catalog\WtFilterController::class, 'refresh'])
        ->name('catalog.wt-filter.refresh');

    Route::get('/cart', [CartController::class, 'index'])->name('catalog.cart');
    Route::get('/cart/info', [CartController::class, 'info'])->name('catalog.cart.info');
    Route::post('/cart/add', [CartController::class, 'add'])->name('catalog.cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('catalog.cart.update');
    Route::delete('/cart/remove/{cartId}', [CartController::class, 'remove'])->name('catalog.cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('catalog.checkout');
    Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('catalog.checkout.confirm');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('catalog.checkout.success');
    Route::get('/checkout/zones', [CheckoutController::class, 'zones'])->name('catalog.checkout.zones');

    Route::get('/blog', [BlogController::class, 'index'])->name('catalog.blog.index');
    Route::post('/blog/{articleSlug}/review', [BlogController::class, 'review'])
        ->where('articleSlug', CatalogUrl::SLUG_SEGMENT)
        ->name('catalog.blog.review');
    Route::get('/blog/{categorySlug}/{articleSlug}', [BlogController::class, 'articleInCategory'])
        ->where(['categorySlug' => CatalogUrl::SLUG_SEGMENT, 'articleSlug' => CatalogUrl::SLUG_SEGMENT])
        ->name('catalog.blog.article');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])
        ->where('slug', CatalogUrl::SLUG_SEGMENT)
        ->name('catalog.blog.show');
};

$registerCatalogSeo = function (): void {
    Route::get('/{seoA}/{seoB}', [CatalogController::class, 'seoTwo'])
        ->where(['seoA' => CatalogUrl::slugPattern(), 'seoB' => CatalogUrl::SLUG_SEGMENT])
        ->name('catalog.seo.two');
    Route::get('/{seoA}', [CatalogController::class, 'seoOne'])
        ->where('seoA', CatalogUrl::slugPattern())
        ->name('catalog.seo.one');
};

// 1) Дефолтный язык: фиксированные пути
Route::middleware('catalog.locale')->group($registerCatalogCore);

// 2) Недефолтные языки: /{locale}/… (раньше SEO, чтобы /en/shirts не считался категорией)
Route::prefix('{locale}')
    ->where(['locale' => CatalogLocale::localePattern()])
    ->middleware('catalog.locale')
    ->name('localized.')
    ->group(function () use ($registerCatalogCore, $registerCatalogSeo) {
        $registerCatalogCore();
        $registerCatalogSeo();
    });

// --- АВТОРИЗАЦИЯ / АДМИНКА ---
Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminLoginController::class, 'login'])->name('login.post');
Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [MainController::class, 'index'])->name('index');

        Route::get('slug-preview', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'slug' => \Illuminate\Support\Str::slug($request->query('text', '')),
            ]);
        })->name('slug.preview');

        Route::post('categories/destroy-selected', [CategoryController::class, 'destroySelected'])
            ->name('categories.destroy-selected');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('languages', LanguageController::class)->except(['show']);
        Route::get('products/suggest', [ProductController::class, 'suggest'])
            ->name('products.suggest');
        Route::post('products/destroy-selected', [ProductController::class, 'destroySelected'])
            ->name('products.destroy-selected');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('products/{product}/quick-update', [ProductController::class, 'quickUpdate'])
            ->name('products.quick-update');
        Route::resource('manufacturers', ManufacturerController::class)->except(['show']);
        Route::resource('attribute-groups', AttributeGroupController::class)->except(['show']);
        Route::resource('attributes', AttributeController::class)->except(['show']);
        Route::resource('options', OptionController::class)->except(['show']);

        Route::get('articles/suggest', [ArticleController::class, 'suggest'])->name('articles.suggest');
        Route::get('articles/suggest-products', [ArticleController::class, 'suggestProducts'])->name('articles.suggest-products');
        Route::post('articles/translate', [ArticleController::class, 'translate'])->name('articles.translate');
        Route::resource('articles', ArticleController::class)->except(['show']);
        Route::resource('blog-categories', BlogCategoryController::class)->except(['show']);
        Route::resource('article-reviews', ArticleReviewController::class)->except(['show', 'create', 'store']);
        Route::get('blog-settings', [BlogSettingController::class, 'edit'])->name('blog-settings.edit');
        Route::post('blog-settings', [BlogSettingController::class, 'update'])->name('blog-settings.update');

        Route::get('filemanager', [\App\Http\Controllers\Admin\FilemanagerController::class, 'index'])
            ->name('filemanager.index');
        Route::post('filemanager/upload', [\App\Http\Controllers\Admin\FilemanagerController::class, 'upload'])
            ->name('filemanager.upload');
        Route::post('filemanager/editor-upload', [\App\Http\Controllers\Admin\FilemanagerController::class, 'editorUpload'])
            ->name('filemanager.editor-upload');
        Route::post('filemanager/folder', [\App\Http\Controllers\Admin\FilemanagerController::class, 'folder'])
            ->name('filemanager.folder');
        Route::post('filemanager/delete', [\App\Http\Controllers\Admin\FilemanagerController::class, 'delete'])
            ->name('filemanager.delete');

        Route::get('wt-filter/options', [WtFilterController::class, 'options'])
            ->name('wt-filter.options');
        Route::post('wt-filter/options/update', [WtFilterController::class, 'optionsUpdate'])
            ->name('wt-filter.options.update');
        Route::get('wt-filter/options/{option}', [WtFilterController::class, 'optionEdit'])
            ->whereNumber('option')
            ->name('wt-filter.options.edit');
        Route::put('wt-filter/options/{option}', [WtFilterController::class, 'optionSave'])
            ->whereNumber('option')
            ->name('wt-filter.options.save');
        Route::get('wt-filter/settings', [WtFilterController::class, 'settings'])
            ->name('wt-filter.settings');
        Route::post('wt-filter/settings', [WtFilterController::class, 'settingsSave'])
            ->name('wt-filter.settings.save');
        Route::get('wt-filter/copy-attributes', [WtFilterController::class, 'copyAttributes'])
            ->name('wt-filter.copy-attributes');
        Route::post('wt-filter/copy-attributes', [WtFilterController::class, 'copyAttributesRun'])
            ->name('wt-filter.copy-attributes.run');
        Route::get('wt-filter/product-form', [WtFilterController::class, 'productFormCallback'])
            ->name('wt-filter.product-form');

        Route::middleware(['admin'])->group(function () {
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::resource('users', UserController::class)->except(['show']);
        });
    });

// 3) SEO дефолтного языка в конце (коды языков исключены из первого сегмента)
Route::middleware('catalog.locale')->group($registerCatalogSeo);
