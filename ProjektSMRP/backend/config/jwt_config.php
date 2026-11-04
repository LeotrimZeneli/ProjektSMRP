<?php

declare(strict_types=1);

return [
    'secret' => getenv('JWT_SECRET') ?: null,
    'issuer' => 'smrp-api',
    'lifetime_seconds' => 3600,
];
