<?php

declare(strict_types=1);

namespace App\Shared\App;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

class JsonOnlyCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($request->query('fmt') === 'csv') {
            return false;
        }

        return parent::shouldCacheRequest($request);
    }
}
