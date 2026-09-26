<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the demo deployment's read-only promise at the boundary instead of at each
 * call site.
 *
 * The flag used to be enforced only by leaf calls to DemoAccountGuard, so its
 * coverage was whatever the last author remembered to instrument: the recruiter
 * CRUD, toggle, application-status and note-template surfaces were never covered
 * and a demo visitor could publish and delete real listings on the shared demo
 * board. Those leaf calls stay as defence in depth; this refuses the whole class of
 * mutation on the groups where the promise was missing.
 */
class RefuseDemoMutations
{
    /**
     * Read-only verbs. Anything else on a covered group changes state.
     *
     * @var array<int, string>
     */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_demo && ! in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            throw ValidationException::withMessages([
                'demo' => __('common.demo_account_read_only'),
            ]);
        }

        return $next($request);
    }
}
