<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

$is_ar = ($lang_code === 'ar');
$title = t('auth.transporter_title','Transporter sign up');
$sub   = t('auth.transporter_sub','Create a trusted transporter account. Your account will be activated after review.');

$err='';
$ok='';

function save_verification_file(string $field_name): ?string {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) return null;
    $tmp = $_FILES[$field_name]['tmp_name'];
    $name = $_FILES[$field_name]['name'] ?? 'file';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['pdf','jpg','jpeg','png'];
    if (!in_array($ext, $allowed, true)) return null;
    $dir = dirname(__DIR__) . '/uploads/verification';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $safe = 'transporter_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $safe;
    if (!move_uploaded_file($tmp, $dest)) return null;
    return 'uploads/verification/' . $safe;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    // RTL copy/paste may add invisible directional marks (RLM/LRM) that break email validation.
    $email = trim($_POST['email'] ?? '');
    $email = preg_replace('/\p{Cf}+/u', '', $email); // e.g., RLM/LRM
    $email = preg_replace('/[^\x20-\x7E]/', '', $email);
    $password = $_POST['password'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $plate_number = trim($_POST['plate_number'] ?? '');

    // For credibility: make ALL inputs required
    if ($name==='' || $email==='' || $password==='' || $location==='' || $phone==='' || $national_id==='' || $license_no==='' || $vehicle_type==='' || $plate_number==='') {
        $err = $is_ar ? "يرجى تعبئة جميع الحقول." : "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = $is_ar ? "البريد الإلكتروني غير صحيح. تأكد من عدم وجود مسافات/رموز خفية." : "Invalid email. Please remove spaces/invisible characters.";
    } elseif (strlen($password) < 6) {
        $err = $is_ar ? "كلمة المرور يجب أن تكون 6 أحرف على الأقل." : "Password must be at least 6 characters.";
    } elseif (!preg_match('/^\+?\d{8,15}$/', preg_replace('/\s+/', '', $phone))) {
        $err = $is_ar ? "رقم الهاتف غير صحيح." : "Invalid phone number.";
    } elseif (!preg_match('/^\d{8,20}$/', $national_id)) {
        $err = $is_ar ? "الرقم الوطني غير صحيح." : "Invalid national ID.";
    } else {
        $doc = save_verification_file('verification_doc');
        if ($doc === null) {
            $err = $is_ar ? "الرجاء إرفاق إثبات (PDF/JPG/PNG)." : "Please upload a proof document (PDF/JPG/PNG).";
        }
        if ($err === '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO transporters (name,email,password,location,vehicle_type,plate_number,phone,national_id,license_no,verification_doc,status) VALUES (?,?,?,?,?,?,?,?,?,?,'pending')");
            $stmt->bind_param("ssssssssss", $name,$email,$hash,$location,$vehicle_type,$plate_number,$phone,$national_id,$license_no,$doc);
            if ($stmt->execute()) {
                $ok = $is_ar ? "تم إنشاء الحساب بنجاح. الحساب الآن قيد المراجعة." : "Account created successfully. Your account is now pending review.";
            } else {
                $err = $is_ar ? "تعذر إنشاء الحساب. ربما البريد مستخدم مسبقًا." : "Could not create account. Email may already be used.";
            }
        }
    }
}
?>

<?php
// ===== Shared Auth Layout render =====
$page_title = $title;
$data_role = 'transporter';
$nav_right_html =
  '<a class="btn btn-sm btn-soft" href="register.php"><i class="fa-solid fa-layer-group"></i> ' . htmlspecialchars(t('nav.change_type','Change type')) . '</a>' .
  '<a class="btn btn-sm btn-soft" href="login.php?role=transporter"><i class="fa-solid fa-right-to-bracket"></i> ' . htmlspecialchars(t('nav.sign_in','Sign in')) . '</a>';

$hero_pill_html = '<span class="pill mb-3"><i class="fa-solid fa-id-card"></i> ' . htmlspecialchars(t('role.trusted_onboarding','Trusted onboarding')) . '</span>';
$hero_title = $title;
$hero_sub = $sub;

ob_start();
?>
<div class="cardx">
  <div class="cardx-body">
    <div class="fw-bold mb-1"><i class="fa-solid fa-route"></i> <?= htmlspecialchars(t('auth.workflow','Workflow')) ?></div>
    <div class="text-muted"><?= htmlspecialchars(t('auth.workflow_text','Accept request → Start → Deliver')) ?></div>
  </div>
</div>
<?php
$hero_extra_html = ob_get_clean();

ob_start();
?>
<h5 class="fw-bold mb-3"><?= htmlspecialchars(t('auth.transporter_title','Transporter sign up')) ?></h5>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?= htmlspecialchars($ok) ?>
    <a href="login.php?role=transporter" class="alert-link ms-1"><?= htmlspecialchars(t('auth.go_sign_in','Go to sign in')) ?></a>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.full_name','Full name')) ?> *</label>
    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.email','Email')) ?> *</label>
    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.password','Password')) ?> *</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.location','Location')) ?> *</label>
    <input type="text" name="location" class="form-control" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label"><?= htmlspecialchars(t('auth.phone','Phone')) ?> *</label>
    <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label"><?= htmlspecialchars(t('auth.national_id','National ID')) ?> *</label>
    <input type="text" name="national_id" class="form-control" required value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label"><?= htmlspecialchars(t('auth.license_no','Driver license no.')) ?> *</label>
    <input type="text" name="license_no" class="form-control" required value="<?= htmlspecialchars($_POST['license_no'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.vehicle_type','Vehicle type')) ?> *</label>
    <input type="text" name="vehicle_type" class="form-control" required value="<?= htmlspecialchars($_POST['vehicle_type'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.plate_number','Plate number')) ?> *</label>
    <input type="text" name="plate_number" class="form-control" required value="<?= htmlspecialchars($_POST['plate_number'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label"><?= htmlspecialchars(t('auth.proof','Proof document')) ?> (PDF/JPG/PNG) *</label>
    <input type="file" name="verification_doc" class="form-control" accept=".pdf,.png,.jpg,.jpeg" required>
    <div class="form-text text-muted"><?= htmlspecialchars(t('auth.proof_hint','Required for credibility.')) ?></div>
  </div>

  <div class="col-12">
    <button class="btn btn-brand w-100" type="submit"><i class="fa-solid fa-user-plus"></i> <?= htmlspecialchars(t('auth.create_account','Create account')) ?></button>
  </div>
</form>

<hr>
<div class="small-link">
  <?= htmlspecialchars(t('auth.have_account','Already have an account?')) ?>
  <a href="login.php?role=transporter"><?= htmlspecialchars(t('nav.sign_in','Sign in')) ?></a>
</div>
<?php
$right_html = ob_get_clean();

require __DIR__ . '/_auth_layout.php';
exit;
