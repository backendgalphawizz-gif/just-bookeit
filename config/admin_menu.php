<?php

return [
    'groups' => [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'route_is' => 'admin.dashboard', 'permission' => 'dashboard', 'badge' => null, 'icon' => 'dashboard'],
        ],
        'Operations' => [
            ['label' => 'Customers', 'route' => 'admin.customers.index', 'route_is' => 'admin.customers.*', 'permission' => 'customers', 'badge' => null, 'icon' => 'users'],
            ['label' => 'Vendors', 'route' => 'admin.vendors.index', 'route_is' => 'admin.vendors.*', 'permission' => 'vendors', 'badge' => 'pending_vendors', 'icon' => 'store'],
            ['label' => 'Drivers', 'route' => 'admin.drivers.index', 'route_is' => 'admin.drivers.*', 'permission' => 'drivers', 'badge' => 'pending_drivers', 'icon' => 'wallet'],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'route_is' => 'admin.categories.*', 'permission' => 'categories', 'badge' => null, 'icon' => 'grid'],
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'route_is' => 'admin.orders.*', 'permission' => 'orders', 'badge' => 'new_orders', 'icon' => 'cart'],
            ['label' => 'Checkout orders', 'route' => 'admin.checkout-orders.index', 'route_is' => 'admin.checkout-orders.*', 'permission' => 'orders', 'badge' => null, 'icon' => 'cart'],
            ['label' => 'Disputes', 'route' => 'admin.disputes.index', 'route_is' => 'admin.disputes.*', 'permission' => 'disputes', 'badge' => 'open_disputes', 'icon' => 'alert'],
        ],
        'Finance' => [
            ['label' => 'Payments', 'route' => 'admin.payments.index', 'route_is' => 'admin.payments.*', 'permission' => 'payments', 'badge' => null, 'icon' => 'card'],
            ['label' => 'Refunds', 'route' => 'admin.refunds.index', 'route_is' => 'admin.refunds.*', 'permission' => 'refunds', 'badge' => 'open_refunds', 'icon' => 'refund'],
            ['label' => 'Payouts', 'route' => 'admin.payouts.index', 'route_is' => 'admin.payouts.*', 'permission' => 'payouts', 'badge' => 'open_payouts', 'icon' => 'wallet'],
            ['label' => 'Withdrawals', 'route' => 'admin.withdrawals.index', 'route_is' => 'admin.withdrawals.*', 'permission' => 'payouts', 'badge' => 'open_withdrawals', 'icon' => 'wallet'],
        ],
        'Content' => [
            ['label' => 'Products', 'route' => 'admin.portfolio.index', 'route_is' => 'admin.portfolio.*', 'permission' => 'portfolio', 'badge' => 'pending_portfolio', 'icon' => 'grid'],
            ['label' => 'Portfolio', 'route' => 'admin.vendor-portfolio.index', 'route_is' => 'admin.vendor-portfolio.*', 'permission' => 'portfolio', 'badge' => null, 'icon' => 'image'],
            ['label' => 'Banners & CMS', 'route' => 'admin.banners.index', 'route_is' => 'admin.banners.*', 'permission' => 'banners', 'badge' => null, 'icon' => 'banner'],
            ['label' => 'FAQs', 'route' => 'admin.faqs.index', 'route_is' => 'admin.faqs.*', 'permission' => 'faqs', 'badge' => null, 'icon' => 'grid'],
            ['label' => 'Contact messages', 'route' => 'admin.contact-messages.index', 'route_is' => 'admin.contact-messages.*', 'permission' => 'contact_messages', 'badge' => 'unread_contact_messages', 'icon' => 'mail'],
        ],
        'System' => [
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'route_is' => 'admin.reports.*', 'permission' => 'reports', 'badge' => null, 'icon' => 'chart'],
            ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'route_is' => 'admin.notifications.*', 'permission' => 'notifications', 'badge' => null, 'icon' => 'bell'],
            ['label' => 'Admin Users', 'route' => 'admin.admins.index', 'route_is' => 'admin.admins.*', 'permission' => 'admins', 'badge' => null, 'icon' => 'users'],
            ['label' => 'Roles', 'route' => 'admin.roles.index', 'route_is' => 'admin.roles.*', 'permission' => 'admins', 'badge' => null, 'icon' => 'shield'],
            ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'route_is' => 'admin.permissions.*', 'permission' => 'admins', 'badge' => null, 'icon' => 'grid'],
            ['label' => 'Settings', 'route' => 'admin.settings.index', 'route_is' => 'admin.settings.index', 'permission' => 'settings', 'badge' => null, 'icon' => 'settings'],
            ['label' => 'Damage deduction', 'route' => 'admin.settings.damage-deduction.index', 'route_is' => 'admin.settings.damage-deduction.*', 'permission' => 'settings', 'badge' => null, 'icon' => 'shield'],
        ],
    ],
];
