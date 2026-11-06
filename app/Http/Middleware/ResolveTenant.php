<?php

namespace App\Http\Middleware;

use App\Services\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private readonly Tenancy $tenancy) {}

    /**
     * Read the LPK or company from the subdomain and share it with every
     * view as $tenant. An unknown subdomain does not exist.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->tenancy->organizationForHost($request->getHost());

        abort_if($organization === false, 404);

        $this->tenancy->setCurrent($organization);
        View::share('tenant', $organization);

        return $next($request);
    }
}
