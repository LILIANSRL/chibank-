<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Enterprise Security Headers Middleware
 * Adds comprehensive security headers to protect against common web vulnerabilities
 */
class EnterpriseSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' data: https://fonts.gstatic.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'self';"
        );

        // Strict Transport Security
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), ' .
            'microphone=(), ' .
            'camera=(), ' .
            'payment=(self), ' .
            'usb=()'
        );

        // Remove X-Powered-By header to hide technology stack
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
