<?php

// Single source of truth for which /app/{page} slugs each acting role can reach.
// Used by both routes/web.php (access gate) and the sidebar (link visibility) so
// they can't drift out of sync with each other.
return [
    'pages' => [
        'admin' => ['dashboard', 'users', 'messages', 'property', 'appointments', 'review', 'report', 'settings'],
        'broker' => ['dashboard', 'agents', 'messages', 'listings', 'appointments', 'review', 'report', 'settings'],
        'agent' => ['dashboard', 'messages', 'listings', 'appointments', 'review', 'report', 'settings'],
        'client' => ['dashboard', 'settings'],
    ],
];
