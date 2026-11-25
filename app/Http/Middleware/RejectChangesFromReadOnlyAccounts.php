<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only accounts (the public demo login) see every button and form in Nova,
 * but nothing they submit is saved: each request that would change data is
 * answered with an explanation instead.
 *
 * The check is by request, not by ability, so it also covers what no policy
 * guards: the shared password, notifications, reordering and anything added to
 * Nova later. It runs on Nova's pages and on its API (config/nova.php).
 */
class RejectChangesFromReadOnlyAccounts
{
    /**
     * Requests Nova sends with PATCH only recalculate a form's dependent fields.
     *
     * @var list<string>
     */
    private const array FIELD_SYNC_PATHS = [
        'nova-api/*/creation-fields',
        'nova-api/*/update-fields',
        'nova-api/*/creation-pivot-fields/*',
        'nova-api/*/update-pivot-fields/*',
        'nova-api/*/action',
    ];

    /**
     * Requests Nova sends with POST to render a field preview, such as Markdown.
     *
     * @var list<string>
     */
    private const array FIELD_PREVIEW_PATHS = [
        'nova-api/*/field/*/preview',
        'nova-api/*/field/*/preview/*',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_read_only || $this->changesNothing($request)) {
            return $next($request);
        }

        // Nova only shows a message from the server for a 5xx response; a 403 would
        // send the visitor to the "Forbidden" page, and a 4xx shows no message at all.
        return response()->json([
            'message' => __('This is a read-only demo account, so your changes were not saved.'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function changesNothing(Request $request): bool
    {
        return $request->isMethodSafe()
            || ($request->isMethod('PATCH') && $request->is(...self::FIELD_SYNC_PATHS))
            || ($request->isMethod('POST') && $request->is(...self::FIELD_PREVIEW_PATHS))
            // An admin impersonating a read-only account must be able to switch back.
            || ($request->isMethod('DELETE') && $request->is('nova-api/impersonate'))
            || $request->routeIs('nova.logout');
    }
}
