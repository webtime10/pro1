<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Support\CatalogLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class SetCatalogLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->route('locale');

        if ($code) {
            $lang = CatalogLocale::findActiveByCode((string) $code);

            if (! $lang) {
                abort(404);
            }

            // Язык по умолчанию — всегда без префикса (/fr → / если fr default)
            if ($lang->is_default) {
                $params = $request->route()?->parameters() ?? [];
                unset($params['locale']);
                $name = preg_replace('/^localized\./', '', (string) $request->route()?->getName());

                if ($name && Route::has($name)) {
                    return redirect()->to(route($name, $params), 301);
                }

                $path = '/'.ltrim(
                    preg_replace('#^/'.preg_quote((string) $code, '#').'(/|$)#', '/', $request->getPathInfo()) ?: '/',
                    '/'
                );

                return redirect()->to(
                    ($path === '' ? '/' : $path).($request->getQueryString() ? '?'.$request->getQueryString() : ''),
                    301
                );
            }

            CatalogLocale::set($lang);
        } else {
            $lang = Language::getDefault();
            abort_if(! $lang, 503, 'Не задан язык по умолчанию в админке.');
            CatalogLocale::set($lang);
        }

        return $next($request);
    }
}
