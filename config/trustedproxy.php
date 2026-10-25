<?php

return [
    // Only list the actual reverse proxy IPs/CIDRs in front of PHP.
    // An empty value keeps forwarded headers from arbitrary clients untrusted.
    'proxies' => array_values(array_filter(array_map(
        'trim', explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
];
