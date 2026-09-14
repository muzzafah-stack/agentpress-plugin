<?php
namespace AgentPress\Security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate Limiter for AgentPress REST API requests
 */
class RateLimiter {

    /**
     * Check if request exceeds rate limit
     *
     * @param string $identifier IP address or Token ID
     * @return array
     */
    public function check(string $identifier): array {
        $settings = get_option('agentpress_settings', []);
        $max_requests = (int) ($settings['rate_limit_requests'] ?? 120);
        $window_seconds = (int) ($settings['rate_limit_window'] ?? 60);

        if ($max_requests <= 0) {
            return ['allowed' => true, 'remaining' => 999, 'retry_after' => 0];
        }

        $transient_key = 'agentpress_rl_' . md5($identifier);
        $current_hits = (int) get_transient($transient_key);

        if ($current_hits >= $max_requests) {
            return [
                'allowed'     => false,
                'remaining'   => 0,
                'retry_after' => $window_seconds,
            ];
        }

        if ($current_hits === 0) {
            set_transient($transient_key, 1, $window_seconds);
            $current_hits = 1;
        } else {
            // Increment transient
            $current_hits++;
            set_transient($transient_key, $current_hits, $window_seconds);
        }

        return [
            'allowed'     => true,
            'remaining'   => max(0, $max_requests - $current_hits),
            'retry_after' => 0,
        ];
    }
}
