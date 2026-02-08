<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

$title = t('auth.store_title','Store sign up');
$sub   = t('auth.store_sub','Create a trusted store account. Your account will be activated after review.');

$err = '';
$ok  = '';

function save_verification_file(string $field_name): ?string {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['pdf','png','jpg','jpeg'];
    $name = $_FILES[$field_name]['name'];
    $tmp  = $_FILES[$field_name]['tmp_name'];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    $dir = __DIR__ . '/../uploads/verification';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = 'store_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $file;
    if (move_uploaded_file($tmp, $dest)) {
        return 'uploads/verification/' . $file;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    // RTL copy/paste may add invisible directional marks (RLM/LRM) that break email validation.
    $email      = trim($_POST['email'] ?? '');
    // Remove Unicode format characters and other non-printable chars
    $email      = preg_replace('/\p{Cf}+/u', '', $email); // e.g., RLM/LRM
    $email      = preg_replace('/[^\x20-\x7E]/', '', $email);
    $password   = $_POST['password'] ?? '';
    $location   = trim($_POST['location'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');

    // For credibility: make ALL inputs required
    if ($name === '' || $email === '' || $password === '' || $location === '' || $phone === '' || $license_no === '') {
        $err = t('auth.fill_all','Please fill all fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = t('auth.invalid_email_hidden','Invalid email. Please remove spaces/invisible characters.');
    } elseif (strlen($password) < 6) {
        $err = t('auth.password_min','Password must be at least 6 characters.');
    } elseif (!preg_match('/^\+?\d{8,15}$/', preg_replace('/\s+/', '', $phone))) {
        $err = t('auth.invalid_phone','Invalid phone number.');
    } else {
        $doc = save_verification_file('verification_doc');
        if ($doc === null) {
            $err = t('auth.proof_required_store','Please upload a proof document (PDF/JPG/PNG).');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO stores (name,email,password,location,phone,license_no,verification_doc,status) VALUES (?,?,?,?,?,?,?, 'pending')");
            $stmt->bind_param("sssssss", $name, $email, $hash, $location, $phone, $license_no, $doc);

            if ($stmt->execute()) {
                $ok = t('auth.created_pending','Account created ✅ Your account is pending review.');
            } else {
                $err = t('auth.email_used','Could not create account. Email may already exist.');
            }
        }
    }
}
?>

<?php
// ===== Shared Auth Layout render =====
$page_title = $title;
$data_role = 'store';
$nav_right_html =
  '<a class="btn btn-sm btn-soft" href="register.php"><i class="fa-solid fa-layer-group"></i> ' . htmlspecialchars(t('nav.change_type','Change type')) . '</a>' .
  '<a class="btn btn-sm btn-soft" href="login.php?role=store"><i class="fa-solid fa-right-to-bracket"></i> ' . htmlspecialchars(t('nav.sign_in','Sign in')) . '</a>';

$hero_pill_html = '<span class="pill mb-3" style="background: rgba(244,180,0,.14); color:#111"><i class="fa-solid fa-store"></i> ' . htmlspecialchars(t('role.store','Store')) . '</span>';
$hero_title = $title;
$hero_sub = $sub;

ob_start();
?>
<div class="cardx">
  <div class="cardx-body">
    <div class="fw-bold mb-1"><i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars(t('role.trusted_onboarding','Trusted onboarding')) ?></div>
    <div class="text-muted"><?= htmlspecialchars(t('auth.pending_review','Your account is pending review. You can sign in once it is approved.')) ?></div>
  </div>
</div>
<?php
$hero_extra_html = ob_get_clean();

ob_start();
?>
<h5 class="fw-bold mb-3"><?= htmlspecialchars(t('auth.store_title','Store sign up')) ?></h5>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?= htmlspecialchars($ok) ?>
    <a href="login.php?role=store" class="alert-link ms-1"><?= htmlspecialchars(t('auth.go_sign_in','Go to sign in')) ?></a>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.store_name','Store name')) ?> *</label>
    <input class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.email','Email')) ?> *</label>
    <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.password','Password')) ?> *</label>
    <div class="input-group">
      <input id="pw_store" class="form-control" type="password" name="password" required />
      <button class="btn btn-outline-secondary" type="button" data-toggle-password data-target="pw_store" aria-label="Toggle password">
        <i class="fa-solid fa-eye"></i>
      </button>
    </div>
    <div class="form-text text-muted"><?= htmlspecialchars(t('auth.password_min_hint','At least 6 characters.')) ?></div>
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.location','Location')) ?> *</label>
    <input class="form-control" name="location" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" />
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.phone','Phone')) ?> *</label>
    <input class="form-control" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.license_registry','License / Registry No.')) ?> *</label>
    <input class="form-control" name="license_no" required value="<?= htmlspecialchars($_POST['license_no'] ?? '') ?>" />
  </div>

  <div class="col-12">
    <label class="form-label"><?= htmlspecialchars(t('auth.proof','Proof document')) ?> (PDF/JPG/PNG) *</label>
    <input class="form-control" type="file" name="verification_doc" accept=".pdf,.png,.jpg,.jpeg" required />
    <div class="form-text text-muted"><?= htmlspecialchars(t('auth.proof_hint_store','Example: shop license / commercial registry.')) ?></div>
  </div>

  <div class="col-12">
    <button class="btn btn-brand w-100" style="--brand:#f4b400; --brand-2:#c28a00; color:#111" type="submit">
      <i class="fa-solid fa-user-plus"></i> <?= htmlspecialchars(t('auth.create_account','Create account')) ?>
    </button>
  </div>
</form>

<hr>
<div class="small-link">
  <?= htmlspecialchars(t('auth.have_account','Already have an account?')) ?>
  <a href="login.php?role=store"><?= htmlspecialchars(t('nav.sign_in','Sign in')) ?></a>
</div>
<?php
$right_html = ob_get_clean();

require __DIR__ . '/_auth_layout.php';
exit;
