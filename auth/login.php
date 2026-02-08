<?php
session_start();
include "../includes/language.php";
include "../config/db.php";

$is_ar = ($lang_code === 'ar');
$role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';
$allowed_roles = ['farmer','transporter','store','admin'];
if (!in_array($role, $allowed_roles, true)) {
    $role = 'farmer';
}

$title = t('auth.sign_in_title', 'Sign in');
$sub = t('auth.sign_in_sub', 'Sign in to access your role dashboard.');

$error = '';

function redirect_by_role(string $role): string {
    switch ($role) {
        case 'farmer': return '../farmer/dashboard.php';
        case 'transporter': return '../transporter/dashboard.php';
        case 'store': return '../store/dashboard.php';
        case 'admin': return '../admin/dashboard.php';
        default: return '../index.php';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_post = strtolower(trim($_POST['role'] ?? ''));
    if (!in_array($role_post, $allowed_roles, true)) {
        $role_post = 'farmer';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = $is_ar ? "الرجاء إدخال البريد الإلكتروني وكلمة المرور." : "Please enter email and password.";
    } else {
        if ($role_post === 'admin') {
            $stmt = $conn->prepare("SELECT admin_id, name, email, password FROM admins WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = (int)$admin['admin_id'];
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = $admin['name'];
                header('Location: ' . redirect_by_role('admin'));
                exit;
            }
            $error = $is_ar ? "بيانات الدخول غير صحيحة." : "Invalid credentials.";
        } else {
            $table = $role_post === 'farmer' ? 'farmers' : ($role_post === 'transporter' ? 'transporters' : 'stores');
            $id_col = $role_post === 'farmer' ? 'farmer_id' : ($role_post === 'transporter' ? 'transporter_id' : 'store_id');

            $sql = "SELECT $id_col AS id, name, email, password, status FROM $table WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Trust gate
                if (isset($user['status']) && $user['status'] !== 'approved') {
                    $error = $is_ar
                        ? "حسابك قيد المراجعة حاليًا. سيتم تفعيله بعد التحقق من المعلومات." 
                        : "Your account is pending review. You can sign in once it is approved.";
                } else {
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['role'] = $role_post;
                    $_SESSION['name'] = $user['name'];
                    header('Location: ' . redirect_by_role($role_post));
                    exit;
                }
            } else {
                $error = $is_ar ? "بيانات الدخول غير صحيحة." : "Invalid credentials.";
            }
        }
    }
}

function role_label(string $role): string {
    switch ($role) {
        case 'farmer': return t('role.farmer','Farmer');
        case 'transporter': return t('role.transporter','Transporter');
        case 'store': return t('role.store','Store');
        case 'admin': return t('role.admin','Admin');
        default: return ucfirst($role);
    }
}

// Build shared layout pieces
$page_title = $title;
$data_role = $role;
$nav_right_html =
    '<a class="btn btn-sm btn-soft" href="register.php"><i class="fa-solid fa-layer-group"></i> ' . htmlspecialchars(t('nav.change_type','Change type')) . '</a>' .
    '<a class="btn btn-sm btn-soft" href="register.php"><i class="fa-solid fa-user-plus"></i> ' . htmlspecialchars(t('nav.sign_up','Sign up')) . '</a>';

ob_start();
?>
<span class="pill mb-3"><i class="fa-solid fa-right-to-bracket"></i> <?= htmlspecialchars(t('auth.secure_sign_in','Secure sign in')) ?></span>

<div class="cardx">
  <div class="cardx-body">
    <div class="segmented">
      <a class="seg <?= $role==='farmer' ? 'active' : '' ?>" href="?role=farmer&lang=<?= $is_ar ? 'ar' : 'en' ?>"><i class="fa-solid fa-tractor"></i> <?= htmlspecialchars(role_label('farmer')) ?></a>
      <a class="seg <?= $role==='transporter' ? 'active' : '' ?>" href="?role=transporter&lang=<?= $is_ar ? 'ar' : 'en' ?>" style="--brand:#1e63d8; --brand-2:#0e46a8"><i class="fa-solid fa-truck"></i> <?= htmlspecialchars(role_label('transporter')) ?></a>
      <a class="seg <?= $role==='store' ? 'active' : '' ?>" href="?role=store&lang=<?= $is_ar ? 'ar' : 'en' ?>" style="--brand:#f4b400; --brand-2:#c28a00"><i class="fa-solid fa-store"></i> <?= htmlspecialchars(role_label('store')) ?></a>
      <a class="seg <?= $role==='admin' ? 'active' : '' ?>" href="?role=admin&lang=<?= $is_ar ? 'ar' : 'en' ?>"><i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars(role_label('admin')) ?></a>
    </div>
    <div class="text-muted mt-2"><?= htmlspecialchars(t('auth.select_role','Select a role to access its dedicated dashboard.')) ?></div>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <span class="pill"><i class="fa-solid fa-shield-check"></i> <?= htmlspecialchars(t('role.trusted_onboarding','Trusted onboarding')) ?></span>
    </div>
  </div>
</div>
<?php
$hero_pill_html = ob_get_clean();

ob_start();
?>
<h5 class="fw-bold mb-2"><?= htmlspecialchars(t('auth.sign_in','Sign in')) ?> — <?= htmlspecialchars(role_label($role)) ?></h5>
<div class="text-muted mb-3"><?= htmlspecialchars(t('auth.redirect_note',"You'll be redirected to your dashboard.")) ?></div>

<?php if ($error): ?>
  <div class="alert alert-warning" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="application/x-www-form-urlencoded">
  <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
  <div class="mb-3">
    <label class="form-label"><?= htmlspecialchars(t('auth.email','Email')) ?></label>
    <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label"><?= htmlspecialchars(t('auth.password','Password')) ?></label>
    <div class="input-group">
      <input id="pw_login" type="password" name="password" class="form-control" required placeholder="••••••••">
      <button class="btn btn-outline-secondary" type="button" data-toggle-password data-target="pw_login" aria-label="Toggle password">
        <i class="fa-solid fa-eye"></i>
      </button>
    </div>
  </div>
  <button type="submit" class="btn btn-brand w-100">
    <i class="fa-solid fa-right-to-bracket"></i> <?= htmlspecialchars(t('auth.sign_in','Sign in')) ?>
  </button>
</form>

<hr>
<div class="small-link">
  <?= htmlspecialchars(t('auth.no_account',"Don't have an account?")) ?>
  <a href="register.php"><?= htmlspecialchars(t('nav.sign_up','Sign up')) ?></a>
</div>
<?php
$right_html = ob_get_clean();

// Hero title/sub for layout
$hero_title = $title;
$hero_sub = $sub;
$hero_extra_html = '';

require __DIR__ . '/_auth_layout.php';
exit;

