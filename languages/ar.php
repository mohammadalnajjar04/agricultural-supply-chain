<?php
// AR dictionary
$lang = [
  // Brand
  'app.name' => 'سلسلة التوريد الزراعية',
  'app.title' => 'إدارة سلسلة التوريد الزراعية في الأردن',

  // Nav
  'nav.home' => 'الرئيسية',
  'nav.sign_in' => 'تسجيل الدخول',
  'nav.sign_up' => 'إنشاء حساب',
  'nav.change_type' => 'تغيير النوع',
  'nav.language' => 'اللغة',
  'nav.english' => 'English',
  'nav.arabic' => 'العربية',
  'nav.back_home' => 'العودة للرئيسية',

  // Roles
  'role.farmer' => 'مزارع',
  'role.transporter' => 'ناقل',
  'role.store' => 'متجر',
  'role.admin' => 'مسؤول',
  'role.trusted_onboarding' => 'تسجيل موثوق',

  // Auth common
  'auth.sign_in' => 'تسجيل الدخول',
  'auth.sign_up' => 'إنشاء حساب',
  'auth.email' => 'البريد الإلكتروني',
  'auth.password' => 'كلمة المرور',
  'auth.location' => 'الموقع',
  'auth.phone' => 'رقم الهاتف',
  'auth.national_id' => 'الرقم الوطني',
  'auth.proof' => 'وثيقة إثبات',
  'auth.secure_sign_in' => 'تسجيل دخول آمن',
  'auth.redirect_note' => 'سيتم توجيهك إلى لوحة التحكم الخاصة بك.',
  'auth.no_account' => 'ليس لديك حساب؟',
  'auth.have_account' => 'لديك حساب بالفعل؟',
  'auth.pending_review' => 'حسابك قيد المراجعة. يمكنك تسجيل الدخول بعد الموافقة.',
  'auth.invalid_credentials' => 'بيانات الدخول غير صحيحة.',
  'auth.fill_all' => 'يرجى تعبئة جميع الحقول.',
  'auth.invalid_email' => 'صيغة البريد الإلكتروني غير صحيحة.',
  'auth.invalid_email_hidden' => 'البريد الإلكتروني غير صحيح. تأكد من عدم وجود مسافات/رموز خفية.',
  'auth.password_min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
  'auth.password_min_hint' => '6 أحرف على الأقل.',
  'auth.invalid_phone' => 'رقم الهاتف غير صحيح.',
  'auth.invalid_national_id' => 'الرقم الوطني غير صحيح.',
  'auth.invalid_land_area' => 'مساحة الأرض يجب أن تكون رقمًا أكبر من صفر.',
  'auth.unsupported_file' => 'نوع الملف غير مدعوم. (PDF/PNG/JPG)',
  'auth.proof_required' => 'الرجاء إرفاق إثبات (PDF/PNG/JPG).',
  'auth.proof_required_store' => 'الرجاء إرفاق إثبات (PDF/JPG/PNG).',
  'auth.created_pending' => 'تم إنشاء الحساب ✅ حسابك قيد المراجعة الآن.',
  'auth.create_account' => 'إنشاء الحساب',
  'auth.go_sign_in' => 'اذهب لتسجيل الدخول',

  // Sign in page
  'auth.sign_in_title' => 'تسجيل الدخول',
  'auth.sign_in_sub' => 'سجّل الدخول للوصول إلى لوحة التحكم الخاصة بدورك.',
  'auth.select_role' => 'اختر الدور للوصول إلى لوحة التحكم المخصصة.',

  // Change type page
  'auth.choose_type_title' => 'إنشاء حساب',
  'auth.choose_type_sub' => 'اختر نوع حسابك للمتابعة. الحسابات الجديدة تُراجع لضمان الموثوقية.',
  'auth.after_signup_title' => 'ماذا يحدث بعد إنشاء الحساب؟',
  'auth.after_signup_1' => 'قم بتعبئة البيانات وإرفاق وثيقة إثبات بسيطة.',
  'auth.after_signup_2' => 'يصبح حسابك (قيد المراجعة).',
  'auth.after_signup_3' => 'بعد الموافقة يمكنك تسجيل الدخول واستخدام النظام.',

  // Farmer sign up
  'auth.farmer_title' => 'تسجيل مزارع',
  'auth.farmer_sub' => 'سجّل حساب مزارع موثوق. سيتم تفعيل الحساب بعد المراجعة.',
  'auth.farm_name' => 'اسم المزرعة',
  'auth.land_area' => 'مساحة الأرض (دونم)',
  'auth.name' => 'الاسم',
  'auth.proof_hint_farmer' => 'مطلوب للموثوقية (مثال: هوية / إثبات مزرعة / رخصة).',

  // Transporter sign up
  'auth.transporter_title' => 'تسجيل ناقل',
  'auth.transporter_sub' => 'سجّل حساب ناقل موثوق. سيتم تفعيل الحساب بعد المراجعة.',
  'auth.full_name' => 'الاسم الكامل',
  'auth.license_no' => 'رقم الرخصة',
  'auth.vehicle_type' => 'نوع المركبة',
  'auth.plate_number' => 'رقم اللوحة',
  'auth.proof_hint' => 'مطلوب لضمان الموثوقية.',
  'auth.workflow' => 'تدفق العمل',
  'auth.workflow_text' => 'قبول طلب → بدء توصيل → تسليم',

  // Store sign up
  'auth.store_title' => 'تسجيل متجر',
  'auth.store_sub' => 'سجّل حساب متجر موثوق. سيتم تفعيل الحساب بعد المراجعة.',
  'auth.store_name' => 'اسم المتجر',
  'auth.license_registry' => 'رقم الرخصة / السجل',
  'auth.proof_hint_store' => 'مثال: رخصة محل / سجل تجاري.',
  
  // Menu
  'menu.dashboard' => 'لوحة التحكم',
  'menu.add_product' => 'إضافة منتج',
  'menu.my_products' => 'منتجاتي',
  'menu.store_orders' => 'طلبات المتاجر',
  'menu.transport_requests' => 'طلبات النقل',
  'menu.ai_recommendations' => 'توصيات الذكاء الاصطناعي',
  'menu.browse_products' => 'تصفح المنتجات',
  'menu.my_orders' => 'طلباتي',
  'menu.available_requests' => 'الطلبات المتاحة',
  'menu.my_requests' => 'طلباتي',
  'menu.logout' => 'تسجيل الخروج',


];
