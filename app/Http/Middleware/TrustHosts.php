<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $hosts = [
            $this->allSubdomainsOfApplicationUrl(),
            '.*\.zaptask\.co\.uk',
            'zaptask\.co\.uk',
            'www\.zaptask\.co\.uk',
            // Heroku deployment URLs (e.g. task-it-9b20e77d6638.herokuapp.com)
            '.*\.herokuapp\.com',
        ];

        if ($extra = env('TRUSTED_HOSTS')) {
            foreach (explode(',', $extra) as $pattern) {
                $pattern = trim($pattern);
                if ($pattern !== '') {
                    $hosts[] = $pattern;
                }
            }
        }

        return array_values(array_filter($hosts));
    }
}
