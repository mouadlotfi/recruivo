<?php

namespace App\Support;

use Illuminate\Foundation\Vite;

class DynamicVite extends Vite
{
    /**
     * Get the path to a given asset when running in HMR mode.
     *
     * In local development, dynamically rewrite the hot server host to match
     * the incoming HTTP request host so LAN/IP access (e.g. 192.168.x.x) loads
     * Vite scripts without hardcoded localhost failures.
     *
     * @param  string  $asset
     * @return string
     */
    protected function hotAsset($asset)
    {
        $url = rtrim(file_get_contents($this->hotFile()));

        if (request()->hasHeader('Host')) {
            $requestHost = request()->getHost();
            $parsed = parse_url($url);
            $port = isset($parsed['port']) ? ':'.$parsed['port'] : ':5173';
            $scheme = $parsed['scheme'] ?? (request()->isSecure() ? 'https' : 'http');
            $url = "{$scheme}://{$requestHost}{$port}";
        }

        return $url.'/'.$asset;
    }
}
