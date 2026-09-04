<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\CartService;
use App\Support\CatalogLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    private function lang(): Language
    {
        return CatalogLocale::current();
    }

    public function index(): View
    {
        $lang = $this->lang();
        $summary = $this->cart->summary($lang);

        return view('catalog.cart', [
            'lang' => $lang,
            'products' => $summary['products'],
            'totals' => $summary['totals'],
            'total' => $summary['total'],
        ]);
    }

    /** JSON для всплывающей корзины (как module/cart в OC). */
    public function info(): JsonResponse
    {
        $lang = $this->lang();
        $summary = $this->cart->summary($lang);

        return response()->json([
            'count' => $summary['count'],
            'total' => number_format($summary['total'], 2, '.', ' ').' ₽',
            'total_raw' => $summary['total'],
            'products' => $summary['products']->map(fn ($p) => [
                'cart_id' => $p['cart_id'],
                'name' => $p['name'],
                'model' => $p['model'],
                'quantity' => $p['quantity'],
                'price' => number_format($p['price'], 2, '.', ' ').' ₽',
                'total' => number_format($p['total'], 2, '.', ' ').' ₽',
                'image' => $p['image'],
                'url' => $p['url'] ?? null,
                'option' => $p['option'],
            ])->values(),
            'totals' => collect($summary['totals'])->map(fn ($t) => [
                'title' => $t['title'],
                'text' => number_format($t['value'], 2, '.', ' ').' ₽',
            ])->values(),
            'cart_url' => localized_route('catalog.cart'),
            'checkout_url' => localized_route('catalog.checkout'),
        ]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'option' => ['nullable', 'array'],
        ]);

        $this->cart->add(
            (int) $data['product_id'],
            (int) ($data['quantity'] ?? 1),
            $data['option'] ?? []
        );

        $summary = $this->cart->summary($this->lang());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => 'Товар добавлен в корзину',
                'count' => $summary['count'],
                'total' => number_format($summary['total'], 2, '.', ' ').' ₽',
            ]);
        }

        return redirect()
            ->to(localized_route('catalog.cart'))
            ->with('success', 'Товар добавлен в корзину');
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'array'],
            'quantity.*' => ['integer', 'min:0'],
        ]);

        foreach ($data['quantity'] as $cartId => $qty) {
            $this->cart->update((int) $cartId, (int) $qty);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $summary = $this->cart->summary($this->lang());

            return response()->json([
                'success' => 'Корзина обновлена',
                'count' => $summary['count'],
                'total' => $summary['total'],
            ]);
        }

        return redirect()->to(localized_route('catalog.cart'))->with('success', 'Корзина обновлена');
    }

    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $cartId = (int) $request->route('cartId');
        $this->cart->remove($cartId);

        if ($request->expectsJson() || $request->ajax()) {
            $summary = $this->cart->summary($this->lang());

            return response()->json([
                'success' => 'Товар удалён',
                'count' => $summary['count'],
                'total' => number_format($summary['total'], 2, '.', ' ').' ₽',
            ]);
        }

        return redirect()->to(localized_route('catalog.cart'))->with('success', 'Товар удалён');
    }
}
