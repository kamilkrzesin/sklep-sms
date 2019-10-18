<?php
namespace App\Middlewares;

use App\Application;
use App\Install\ShopState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IsUpToDate implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        if (!ShopState::isInstalled()) {
            return new RedirectResponse('/setup');
        }

        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        if (!$shopState->isUpToDate()) {
            return new RedirectResponse('/setup');
        }

        return null;
    }
}
