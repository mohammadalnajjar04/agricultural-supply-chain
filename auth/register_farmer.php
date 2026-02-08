<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

$title = t('auth.farmer_title','Farmer sign up');
$sub   = t('auth.farmer_sub','Create a trusted farmer account. Your account will be activated after review.');

$err = '';
$ok  = '';

function save_verification_file(string $field): ?string {
    // For project credibility: verification document is REQUIRED
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['pdf','png','jpg','jpeg'];
    $name = $_FILES[$field]['name'] ?? '';
    $tmp = $_FILES[$field]['tmp_name'] ?? '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Unsupported file');
    }
    $dir = __DIR__ . '/../uploads/verification';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $safe = 'farmer_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $safe;
    if (!move_uploaded_file($tmp, $dest)) {
        return null;
    }
    return 'uploads/verification/' . $safe;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = trim($_POST['location'] ?? '');

    $phone = trim($_POST['phone'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $farm_name = trim($_POST['farm_name'] ?? '');
    $land_area = trim($_POST['land_area'] ?? '');

    // All inputs are mandatory for trust / credibility
    if ($name === '' || $email === '' || $password === '' || $location === '' || $phone === '' || $national_id === '' || $farm_name === '' || $land_area === '') {
        $err = t('auth.fill_all','Please fill all fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = t('auth.invalid_email','Invalid email format.');
    } elseif (strlen($password) < 6) {
        $err = t('auth.password_min','Password must be at least 6 characters.');
    } elseif (!preg_match('/^\+?\d{8,15}$/', preg_replace('/\s+/', '', $phone))) {
        $err = t('auth.invalid_phone','Invalid phone number.');
    } elseif (!preg_match('/^\d{8,20}$/', $national_id)) {
        $err = t('auth.invalid_national_id','Invalid national ID.');
    } elseif (!is_numeric($land_area) || (float)$land_area <= 0) {
        $err = t('auth.invalid_land_area','Land area must be a number greater than zero.');
    } else {
        try {
            $doc = save_verification_file('verification_doc');
        } catch (RuntimeException $e) {
            $doc = null;
            $err = t('auth.unsupported_file','Unsupported file type. (PDF/PNG/JPG)');
        }

        if ($err === '' && $doc === null) {
            $err = t('auth.proof_required','Please upload a proof document (PDF/PNG/JPG).');
        }

        if ($err === '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $land_area_val = (float)$land_area;

            $stmt = $conn->prepare("INSERT INTO farmers (name,email,password,location,phone,national_id,farm_name,land_area,verification_doc,status) VALUES (?,?,?,?,?,?,?,?,?,'pending')");
            // 7 strings + land_area (double) + verification_doc (string)
            $stmt->bind_param(
                "sssssssds",
                $name,
                $email,
                $hash,
                $location,
                $phone,
                $national_id,
                $farm_name,
                $land_area_val,
                $doc
            );

            if ($stmt->execute()) {
                $ok = t('auth.created_pending','Account created! Your account is now pending review.');
            } else {
                $err = t('auth.create_failed_email','Could not create account. Email may already be used.');
            }
        }
    }
}
?>

<?php
// ===== Shared Auth Layout render =====
$page_title = $title;
$data_role = 'farmer';
$nav_right_html =
  '<a class="btn btn-sm btn-soft" href="register.php"><i class="fa-solid fa-layer-group"></i> ' . htmlspecialchars(t('nav.change_type','Change type')) . '</a>' .
  '<a class="btn btn-sm btn-soft" href="login.php?role=farmer"><i class="fa-solid fa-right-to-bracket"></i> ' . htmlspecialchars(t('nav.sign_in','Sign in')) . '</a>';

$hero_pill_html = '<span class="pill mb-3"><i class="fa-solid fa-tractor"></i> ' . htmlspecialchars(t('role.farmer','Farmer')) . '</span>';
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
<h5 class="fw-bold mb-3"><?= htmlspecialchars(t('auth.farmer_title','Farmer sign up')) ?></h5>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alert alert-success"><?= htmlspecialchars($ok) ?>
    <a href="login.php?role=farmer" class="alert-link ms-1"><?= htmlspecialchars(t('auth.go_sign_in','Go to sign in')) ?></a>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.name','Name')) ?> *</label>
    <input class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.email','Email')) ?> *</label>
    <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.password','Password')) ?> *</label>
    <div class="input-group">
      <input id="pw_farmer" type="password" class="form-control" name="password" required>
      <button class="btn btn-outline-secondary" type="button" data-toggle-password data-target="pw_farmer" aria-label="Toggle password">
        <i class="fa-solid fa-eye"></i>
      </button>
    </div>
    <div class="form-text text-muted"><?= htmlspecialchars(t('auth.password_min_hint','At least 6 characters.')) ?></div>
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.location','Location')) ?> *</label>
    <input class="form-control" name="location" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.phone','Phone')) ?> *</label>
    <input class="form-control" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= htmlspecialchars(t('auth.national_id','National ID')) ?> *</label>
    <input class="form-control" name="national_id" required value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
  </div>

  <div class="col-md-7">
    <label class="form-label"><?= htmlspecialchars(t('auth.farm_name','Farm name')) ?> *</label>
    <input class="form-control" name="farm_name" required value="<?= htmlspecialchars($_POST['farm_name'] ?? '') ?>">
  </div>
  <div class="col-md-5">
    <label class="form-label"><?= htmlspecialchars(t('auth.land_area','Land area (Dunum)')) ?> *</label>
    <input type="number" step="0.01" class="form-control" name="land_area" required value="<?= htmlspecialchars($_POST['land_area'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label"><?= htmlspecialchars(t('auth.proof','Proof document')) ?> (PDF/PNG/JPG) *</label>
    <input class="form-control" type="file" name="verification_doc" accept=".pdf,.png,.jpg,.jpeg" required>
    <div class="form-text text-muted"><?= htmlspecialchars(t('auth.proof_hint_farmer','Required for credibility (e.g., ID / farm proof / license).')) ?></div>
  </div>

  <div class="col-12">
    <button class="btn btn-brand w-100" type="submit"><i class="fa-solid fa-user-plus"></i> <?= htmlspecialchars(t('auth.create_account','Create account')) ?></button>
  </div>
</form>

<hr>
<div class="small-link">
  <?= htmlspecialchars(t('auth.have_account','Already have an account?')) ?>
  <a href="login.php?role=farmer"><?= htmlspecialchars(t('nav.sign_in','Sign in')) ?></a>
</div>
<?php
$right_html = ob_get_clean();

require __DIR__ . '/_auth_layout.php';
exit;
