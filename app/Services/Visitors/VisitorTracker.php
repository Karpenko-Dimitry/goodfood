<?php

namespace App\Services\Visitors;

use App\Models\Visitor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Records guests and signed-in users in the `visitors` table: one row per browser cookie,
 * first/last seen, page views. Used by the TrackVisitor middleware.
 */
class VisitorTracker
{
    public function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson() || $request->ajax() || $request->prefetch()) {
            return false;
        }

        if ($request->is(...config('visitors.ignore_paths'))) {
            return false;
        }

        $agent = (string) $request->userAgent();

        return $agent !== '' && ! preg_match(config('visitors.ignore_user_agents'), $agent);
    }

    /**
     * Upsert the visitor for this request. Returns the visitor and whether it is new (cookie must be set).
     *
     * @return array{0: Visitor, 1: bool}
     */
    public function track(Request $request): array
    {
        $id = $request->cookie(config('visitors.cookie'));
        $isNew = ! is_string($id) || ! Str::isUuid($id);
        $id = $isNew ? (string) Str::uuid() : $id;

        $visitor = Visitor::firstWhere('visitor_id', $id) ?? $this->create($id, $request);

        $visitor->forceFill([
            // A guest who signs in becomes a user; signing out does not turn them back into a guest.
            'user_id' => $request->user()?->getAuthIdentifier() ?? $visitor->user_id,
            'ip' => $this->ip($request),
            'locale' => app()->getLocale(),
            'last_path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'last_seen_at' => now(),
            'page_views' => $visitor->page_views + 1,
        ])->save();

        return [$visitor, $isNew];
    }

    public function cookie(string $visitorId): Cookie
    {
        return cookie(config('visitors.cookie'), $visitorId, config('visitors.cookie_days') * 24 * 60, httpOnly: true, sameSite: 'lax');
    }

    private function create(string $id, Request $request): Visitor
    {
        try {
            return Visitor::create([
                'visitor_id' => $id,
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'device' => $this->device((string) $request->userAgent()),
                'referrer' => $this->referrerHost($request),
                'landing_path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two parallel first requests of the same browser.
            return Visitor::where('visitor_id', $id)->firstOrFail();
        }
    }

    private function ip(Request $request): ?string
    {
        return $request->ip() ? IpUtils::anonymize($request->ip()) : null;
    }

    private function device(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet/i', $agent) => 'tablet',
            (bool) preg_match('/Mobi|Android|iPhone/i', $agent) => 'mobile',
            default => 'desktop',
        };
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host && $host !== $request->getHost() ? Str::limit($host, 250, '') : null;
    }
}
