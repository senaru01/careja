<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaymentDemoKey
{
    /**
     * Only open the payment demo for the secret key in the URL. The page is
     * hidden (404) when no key is configured or the key is wrong.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) config('careja.payment_demo_key');

        abort_unless($key !== '' && hash_equals($key, (string) $request->route('demoKey')), 404);

        $request->route()->forgetParameter('demoKey');

        return $next($request);
    }
}
