<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Str;

/**
 * Which LPK or company the current request is for, read from the subdomain
 * (oki.careja.id). On the root domain there is no tenant: that is Careja itself.
 */
class Tenancy
{
    private ?Organization $current = null;

    public function current(): ?Organization
    {
        return $this->current;
    }

    public function setCurrent(?Organization $organization): void
    {
        $this->current = $organization;
    }

    public function isRootDomain(): bool
    {
        return $this->current === null;
    }

    /**
     * The subdomain part of a host ("oki" for oki.careja.id), or null for the
     * root domain, www and hosts outside the root domain (IP, tunnels).
     */
    public function subdomainOf(string $host): ?string
    {
        $host = Str::lower($host);
        $root = Str::lower((string) config('careja.root_domain'));

        if ($root === '' || ! Str::endsWith($host, '.'.$root)) {
            return null;
        }

        $subdomain = Str::beforeLast($host, '.'.$root);

        return $subdomain === 'www' ? null : $subdomain;
    }

    /**
     * Find the organization of a host. Returns false for a subdomain no
     * organization uses, so the caller can answer 404.
     */
    public function organizationForHost(string $host): Organization|false|null
    {
        $subdomain = $this->subdomainOf($host);

        if ($subdomain === null) {
            return null;
        }

        return Organization::where('subdomain', $subdomain)->first() ?? false;
    }

    /**
     * Absolute URL on an organization's subdomain, or on the root domain for null.
     */
    public function url(?Organization $organization, string $path = '/'): string
    {
        $base = parse_url((string) config('app.url'));
        $scheme = $base['scheme'] ?? 'https';
        $port = isset($base['port']) ? ':'.$base['port'] : '';
        $root = (string) config('careja.root_domain');
        $host = $organization?->subdomain ? $organization->subdomain.'.'.$root : $root;

        return $scheme.'://'.$host.$port.'/'.ltrim($path, '/');
    }

    /**
     * Address shown to people, e.g. "oki.careja.id".
     */
    public function hostFor(?Organization $organization): string
    {
        $root = (string) config('careja.root_domain');

        return $organization?->subdomain ? $organization->subdomain.'.'.$root : $root;
    }
}
