<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Language;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\CatalogLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
    ) {}

    private function lang(): Language
    {
        return CatalogLocale::current();
    }

    public function index(): View|RedirectResponse
    {
        $lang = $this->lang();
        $summary = $this->cart->summary($lang);

        if ($summary['products']->isEmpty()) {
            return redirect()->to(localized_route('catalog.cart'));
        }

        $countries = Country::query()->where('status', true)->orderBy('name')->get();
        $countryId = (int) old('country_id', 176);
        $zones = Zone::query()
            ->where('country_id', $countryId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('catalog.checkout', [
            'lang' => $lang,
            'products' => $summary['products'],
            'totals' => $summary['totals'],
            'total' => $summary['total'],
            'countries' => $countries,
            'zones' => $zones,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:32'],
            'lastname' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:96'],
            'telephone' => ['required', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:60'],
            'address_1' => ['required', 'string', 'max:128'],
            'address_2' => ['nullable', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:128'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'country_id' => ['required', 'integer', 'exists:country,country_id'],
            'zone_id' => ['required', 'integer', 'exists:zone,zone_id'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->checkout->placeOrder($data, $request, $this->lang());

        return redirect()
            ->to(localized_route('catalog.checkout.success', ['order_id' => $order->order_id]))
            ->with('order_id', $order->order_id);
    }

    public function success(Request $request): View
    {
        $orderId = (int) ($request->query('order_id') ?: session('order_id'));

        return view('catalog.checkout-success', [
            'orderId' => $orderId,
        ]);
    }

    public function zones(Request $request)
    {
        $countryId = (int) $request->query('country_id', 0);
        $zones = Zone::query()
            ->where('country_id', $countryId)
            ->where('status', true)
            ->orderBy('name')
            ->get(['zone_id', 'name']);

        return response()->json(['zones' => $zones]);
    }
}
