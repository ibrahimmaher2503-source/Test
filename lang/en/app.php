<?php

return [
    'nav' => [
        'administration' => 'Administration',
        'catalog' => 'Catalog',
        'inventory' => 'Inventory',
        'reports' => 'Reports',
    ],

    'branch' => [
        'label' => 'Branch',
        'plural_label' => 'Branches',
        'nav_label' => 'Branches',
        'fields' => [
            'name_en' => 'Name (English)',
            'name_ar' => 'Name (Arabic)',
            'code' => 'Code',
            'address' => 'Address',
            'phone' => 'Phone',
            'is_active' => 'Active',
        ],
    ],

    'category' => [
        'label' => 'Category',
        'plural_label' => 'Categories',
        'nav_label' => 'Categories',
        'fields' => [
            'name_en' => 'Name (English)',
            'name_ar' => 'Name (Arabic)',
        ],
    ],

    'product' => [
        'label' => 'Product',
        'plural_label' => 'Products',
        'nav_label' => 'Products',
        'fields' => [
            'sku' => 'SKU',
            'barcode' => 'Barcode',
            'name' => 'Name',
            'name_en' => 'Name (English)',
            'name_ar' => 'Name (Arabic)',
            'category' => 'Category',
            'unit' => 'Unit',
            'status' => 'Status',
            'is_active' => 'Active',
        ],
        'status' => [
            'active' => 'Active',
            'pending_review' => 'Pending review',
            'inactive' => 'Inactive',
        ],
        'actions' => [
            'generate_barcode' => 'Generate barcode',
            'print_label' => 'Print label',
            'print_labels' => 'Print labels',
        ],
        'filters' => [
            'has_barcode' => 'Has barcode',
        ],
    ],

    'stock' => [
        'nav_label' => 'Expected Stock',
        'title' => 'Product Branch Stock',
        'fields' => [
            'branch' => 'Branch',
            'sku' => 'SKU',
            'product' => 'Product',
            'expected_quantity' => 'Expected qty',
            'updated_by' => 'Last updated by',
            'updated_at' => 'Last updated',
        ],
    ],

    'session' => [
        'label' => 'Inventory Session',
        'plural_label' => 'Inventory Sessions',
        'nav_label' => 'Inventory Sessions',
        'fields' => [
            'reference' => 'Reference',
            'branch' => 'Branch',
            'status' => 'Status',
            'counters' => 'Counters',
            'created_by' => 'Created by',
            'approved_by' => 'Approved by',
            'started_at' => 'Started at',
            'submitted_at' => 'Submitted at',
            'approved_at' => 'Approved at',
            'notes' => 'Notes',
        ],
        'status' => [
            'draft' => 'Draft',
            'in_progress' => 'In progress',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'closed' => 'Closed',
        ],
        'actions' => [
            'start' => 'Start',
            'submit' => 'Submit',
            'approve' => 'Approve',
            'close' => 'Close',
        ],
        'notifications' => [
            'already_in_progress' => 'This branch already has a session in progress.',
        ],
    ],

    'count_lines' => [
        'title' => 'Count lines',
        'fields' => [
            'sku' => 'SKU',
            'product' => 'Product',
            'expected' => 'Expected',
            'counted' => 'Counted',
            'variance' => 'Variance',
            'last_scanned_by' => 'Last scanned by',
            'last_scanned_at' => 'Last scanned',
        ],
        'filters' => [
            'has_variance' => 'Variance ≠ 0',
        ],
    ],

    'scanning' => [
        'nav_label' => 'My Sessions',
        'title' => 'Scan',
        'session_not_open' => 'This session is :status and is no longer open for scanning. You can still review what was counted below.',
        'keyboard_wedge_placeholder' => 'Or type/scan a barcode and press Enter',
        'add' => 'Add',
        'counted_so_far' => 'Counted so far (:count)',
        'nothing_scanned_yet' => 'Nothing scanned yet — point the camera at a barcode to start.',
        'unknown_barcode' => 'Unknown barcode: :barcode — add new product?',
        'name_en_placeholder' => 'Name (English)',
        'name_ar_placeholder' => 'Name (Arabic)',
        'no_category' => '— No category —',
        'save_and_count' => 'Save & count',
        'cancel' => 'Cancel',
        'adjust' => 'Adjust',
        'manual_adjust_note_placeholder' => 'Reason for manual adjustment (required)',
        'save' => 'Save',
        'scan_button' => 'Scan',
        'not_open_for_scanning' => 'This session is not open for scanning.',
        'not_open_for_editing' => 'This session is not open for editing.',
        'counted_notification' => 'Counted: :name',
        'manual_update_notification' => 'Quantity updated manually',
    ],

    'reports' => [
        'session_detail' => [
            'nav_label' => 'Session Detail Report',
            'title' => 'Session Detail Report',
            'session_field' => 'Session',
            'export_csv' => 'Export CSV',
            'no_lines' => 'No count lines for this session yet.',
        ],
        'branch_summary' => [
            'nav_label' => 'Branch Summary Report',
            'title' => 'Branch Summary Report',
            'from' => 'From',
            'to' => 'To',
            'branch' => 'Branch',
            'all_branches' => 'All branches',
            'export_csv' => 'Export CSV',
            'no_data' => 'No data for this range.',
            'fields' => [
                'branch' => 'Branch',
                'sessions' => 'Sessions',
                'total_counted' => 'Total items counted',
                'total_variance' => 'Total variance',
                'pending_review' => 'Pending review products',
            ],
        ],
    ],

    'user' => [
        'label' => 'User',
        'plural_label' => 'Users',
        'nav_label' => 'Users',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Role',
            'branch' => 'Branch',
        ],
        'roles' => [
            'super_admin' => 'Super Admin',
            'branch_manager' => 'Branch Manager',
            'counter' => 'Counter',
        ],
    ],

    'locale' => [
        'switch_to_arabic' => 'العربية',
        'switch_to_english' => 'English',
    ],
];
