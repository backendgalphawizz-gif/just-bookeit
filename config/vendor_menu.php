<?php

return [
    [
        'label' => 'Dashboard',
        'route' => 'vendor.dashboard',
        'icon' => 'dashboard',
        'match' => ['vendor.dashboard'],
    ],
    [
        'label' => 'Bookings',
        'route' => 'vendor.bookings.index',
        'icon' => 'bookings',
        'match' => ['vendor.bookings.*'],
    ],
    [
        'label' => 'Products',
        'icon' => 'products',
        'children' => [
            ['label' => 'Fashion Designer', 'route' => 'vendor.products.index', 'params' => ['type' => 'fashion-designer'], 'match' => ['vendor.products.index', 'vendor.products.create', 'vendor.products.edit', 'vendor.products.show']],
            ['label' => 'Rental Dresses', 'route' => 'vendor.products.index', 'params' => ['type' => 'rented-dress'], 'match' => ['vendor.products.index', 'vendor.products.create', 'vendor.products.edit', 'vendor.products.show']],
            ['label' => 'Rental Jewelry', 'route' => 'vendor.products.index', 'params' => ['type' => 'rented-jewellery'], 'match' => ['vendor.products.index', 'vendor.products.create', 'vendor.products.edit', 'vendor.products.show']],
        ],
    ],
    [
        'label' => 'Payment Management',
        'route' => 'vendor.payments.index',
        'icon' => 'payments',
        'match' => ['vendor.payments.*'],
    ],
    [
        'label' => 'Chat',
        'route' => 'vendor.chat.index',
        'icon' => 'chat',
        'match' => ['vendor.chat.*'],
    ],
    [
        'label' => 'Settings',
        'route' => 'vendor.settings.index',
        'icon' => 'settings',
        'match' => ['vendor.settings.*'],
    ],
];
