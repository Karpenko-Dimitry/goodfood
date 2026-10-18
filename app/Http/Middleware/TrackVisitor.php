<?php

namespace App\Http\Middleware;

use App\Services\Visitors\VisitorTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackVisitor
{
    public function __construct(private VisitorTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400 || ! $this->tracker->shouldTrack($request)) {
            return $response;
        }

        try {
            [$visitor, $isNew] = $this->tracker->track($request);

            if ($isNew) {
                $response->headers->setCookie($this->tracker->cookie($visitor->visitor_id));
            }
        } catch (Throwable $e) {
            report($e);   // analytics must never break a page
        }

        return $response;
    }
}
