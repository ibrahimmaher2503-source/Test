<?php

return [
    'nav' => [
        'administration' => 'الإدارة',
        'catalog' => 'الكتالوج',
        'inventory' => 'الجرد',
        'reports' => 'التقارير',
    ],

    'branch' => [
        'label' => 'فرع',
        'plural_label' => 'الفروع',
        'nav_label' => 'الفروع',
        'fields' => [
            'name_en' => 'الاسم (إنجليزي)',
            'name_ar' => 'الاسم (عربي)',
            'code' => 'الكود',
            'address' => 'العنوان',
            'phone' => 'الهاتف',
            'is_active' => 'نشط',
        ],
    ],

    'category' => [
        'label' => 'تصنيف',
        'plural_label' => 'التصنيفات',
        'nav_label' => 'التصنيفات',
        'fields' => [
            'name_en' => 'الاسم (إنجليزي)',
            'name_ar' => 'الاسم (عربي)',
        ],
    ],

    'product' => [
        'label' => 'منتج',
        'plural_label' => 'المنتجات',
        'nav_label' => 'المنتجات',
        'fields' => [
            'sku' => 'رمز المنتج',
            'barcode' => 'الباركود',
            'name' => 'الاسم',
            'name_en' => 'الاسم (إنجليزي)',
            'name_ar' => 'الاسم (عربي)',
            'category' => 'التصنيف',
            'unit' => 'الوحدة',
            'status' => 'الحالة',
            'is_active' => 'نشط',
        ],
        'status' => [
            'active' => 'نشط',
            'pending_review' => 'بانتظار المراجعة',
            'inactive' => 'غير نشط',
        ],
        'actions' => [
            'generate_barcode' => 'إنشاء باركود',
            'print_label' => 'طباعة الملصق',
            'print_labels' => 'طباعة الملصقات',
        ],
        'filters' => [
            'has_barcode' => 'له باركود',
        ],
    ],

    'stock' => [
        'nav_label' => 'الرصيد المتوقع',
        'title' => 'رصيد المنتج حسب الفرع',
        'fields' => [
            'branch' => 'الفرع',
            'sku' => 'رمز المنتج',
            'product' => 'المنتج',
            'expected_quantity' => 'الكمية المتوقعة',
            'updated_by' => 'آخر تحديث بواسطة',
            'updated_at' => 'آخر تحديث',
        ],
    ],

    'session' => [
        'label' => 'جلسة جرد',
        'plural_label' => 'جلسات الجرد',
        'nav_label' => 'جلسات الجرد',
        'fields' => [
            'reference' => 'المرجع',
            'branch' => 'الفرع',
            'status' => 'الحالة',
            'counters' => 'العدّادون',
            'created_by' => 'أُنشئت بواسطة',
            'approved_by' => 'اعتُمدت بواسطة',
            'started_at' => 'وقت البدء',
            'submitted_at' => 'وقت التسليم',
            'approved_at' => 'وقت الاعتماد',
            'notes' => 'ملاحظات',
        ],
        'status' => [
            'draft' => 'مسودة',
            'in_progress' => 'جارية',
            'submitted' => 'مُسلَّمة',
            'approved' => 'معتمدة',
            'closed' => 'مغلقة',
        ],
        'actions' => [
            'start' => 'بدء',
            'submit' => 'تسليم',
            'approve' => 'اعتماد',
            'close' => 'إغلاق',
        ],
        'notifications' => [
            'already_in_progress' => 'هذا الفرع لديه بالفعل جلسة جرد جارية.',
        ],
    ],

    'count_lines' => [
        'title' => 'بنود الجرد',
        'fields' => [
            'sku' => 'رمز المنتج',
            'product' => 'المنتج',
            'expected' => 'المتوقع',
            'counted' => 'المعدود',
            'variance' => 'الفرق',
            'last_scanned_by' => 'آخر مسح بواسطة',
            'last_scanned_at' => 'آخر مسح',
        ],
        'filters' => [
            'has_variance' => 'الفرق ≠ 0',
        ],
    ],

    'scanning' => [
        'nav_label' => 'جلساتي',
        'title' => 'مسح',
        'session_not_open' => 'هذه الجلسة :status ولم تعد مفتوحة للمسح. يمكنك مراجعة ما تم عدّه أدناه.',
        'keyboard_wedge_placeholder' => 'أو اكتب/امسح الباركود واضغط إدخال',
        'add' => 'إضافة',
        'counted_so_far' => 'تم عدّه حتى الآن (:count)',
        'nothing_scanned_yet' => 'لم يتم مسح أي شيء بعد — وجّه الكاميرا نحو الباركود للبدء.',
        'unknown_barcode' => 'باركود غير معروف: :barcode — إضافة منتج جديد؟',
        'name_en_placeholder' => 'الاسم (إنجليزي)',
        'name_ar_placeholder' => 'الاسم (عربي)',
        'no_category' => '— بدون تصنيف —',
        'save_and_count' => 'حفظ وعدّ',
        'cancel' => 'إلغاء',
        'adjust' => 'تعديل',
        'manual_adjust_note_placeholder' => 'سبب التعديل اليدوي (مطلوب)',
        'save' => 'حفظ',
        'scan_button' => 'مسح',
        'not_open_for_scanning' => 'هذه الجلسة غير مفتوحة للمسح.',
        'not_open_for_editing' => 'هذه الجلسة غير مفتوحة للتعديل.',
        'counted_notification' => 'تم العدّ: :name',
        'manual_update_notification' => 'تم تحديث الكمية يدويًا',
    ],

    'reports' => [
        'session_detail' => [
            'nav_label' => 'تقرير تفاصيل الجلسة',
            'title' => 'تقرير تفاصيل الجلسة',
            'session_field' => 'الجلسة',
            'export_csv' => 'تصدير CSV',
            'no_lines' => 'لا توجد بنود جرد لهذه الجلسة بعد.',
        ],
        'branch_summary' => [
            'nav_label' => 'تقرير ملخص الفروع',
            'title' => 'تقرير ملخص الفروع',
            'from' => 'من',
            'to' => 'إلى',
            'branch' => 'الفرع',
            'all_branches' => 'كل الفروع',
            'export_csv' => 'تصدير CSV',
            'no_data' => 'لا توجد بيانات لهذه الفترة.',
            'fields' => [
                'branch' => 'الفرع',
                'sessions' => 'الجلسات',
                'total_counted' => 'إجمالي العناصر المعدودة',
                'total_variance' => 'إجمالي الفرق',
                'pending_review' => 'منتجات بانتظار المراجعة',
            ],
        ],
    ],

    'user' => [
        'label' => 'مستخدم',
        'plural_label' => 'المستخدمون',
        'nav_label' => 'المستخدمون',
        'fields' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'role' => 'الدور',
            'branch' => 'الفرع',
        ],
        'roles' => [
            'super_admin' => 'مدير عام',
            'branch_manager' => 'مدير فرع',
            'counter' => 'عدّاد',
        ],
    ],

    'locale' => [
        'switch_to_arabic' => 'العربية',
        'switch_to_english' => 'English',
    ],
];
