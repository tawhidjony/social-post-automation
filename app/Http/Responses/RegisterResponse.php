<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = $request->user();
        $workspace = $user?->ensureCurrentWorkspace();

        $home = ($workspace !== null && ! $workspace->hasActivePlan())
            ? route('subscription.upgrade')
            : Fortify::redirects('register');

        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended($home);
    }
}
