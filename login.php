<?php
require 'includes/database.php';
session_start();
$err = '';
$selectedRole = $_POST['role'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare("SELECT * FROM users WHERE email=? AND role=?");
    $st->execute([$_POST['email'], $selectedRole]);
    $u = $st->fetch();

    if ($u && password_verify($_POST['password'], $u['password'])) {
        $_SESSION['user'] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'role' => $u['role']
        ];
        header('Location: ' . ($u['role'] === 'owner' ? 'owner/dashboard.php' : 'index.php'));
        exit;
    }
    $err = 'อีเมล รหัสผ่าน หรือประเภทบัญชีไม่ถูกต้อง';
}
require 'includes/header.php';
?>
<main class="wrap login-page">
    <form class="form card login-card" method="post">
        <div class="login-heading">
            <span class="eyebrow">ยินดีต้อนรับกลับ</span>
            <h2>เข้าสู่ระบบ</h2>
            <p class="muted">เลือกประเภทบัญชีของคุณก่อนเข้าสู่ระบบ</p>
        </div>

        <?php if ($err): ?>
            <div class="login-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <label class="account-label">ประเภทบัญชี</label>
        <div class="account-type-grid" role="radiogroup" aria-label="ประเภทบัญชี">
            <label class="account-type-option <?= $selectedRole === 'customer' ? 'selected' : '' ?>">
                <input type="radio" name="role" value="customer" <?= $selectedRole === 'customer' ? 'checked' : '' ?> required>
                <span class="account-type-icon">👤</span>
                <span class="account-type-copy">
                    <strong>ลูกค้า</strong>
                    <small>ค้นหาร้านและจองบริการ</small>
                </span>
                <span class="account-check">✓</span>
            </label>

            <label class="account-type-option <?= $selectedRole === 'owner' ? 'selected' : '' ?>">
                <input type="radio" name="role" value="owner" <?= $selectedRole === 'owner' ? 'checked' : '' ?> required>
                <span class="account-type-icon">🏪</span>
                <span class="account-type-copy">
                    <strong>เจ้าของร้าน</strong>
                    <small>จัดการร้านและคิวจอง</small>
                </span>
                <span class="account-check">✓</span>
            </label>
        </div>

        <label for="email">อีเมล</label>
        <input id="email" type="email" name="email" placeholder="example@email.com" required autocomplete="email">

        <label for="password">รหัสผ่าน</label>
        <input id="password" type="password" name="password" placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">

        <button class="btn login-submit" type="submit">เข้าสู่ระบบ</button>

        <p class="login-register">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
    </form>
</main>
<script>
document.querySelectorAll('.account-type-option').forEach(option => {
    option.addEventListener('click', () => {
        document.querySelectorAll('.account-type-option').forEach(item => item.classList.remove('selected'));
        option.classList.add('selected');
        const radio = option.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    });
});
</script>
<?php require 'includes/footer.php'; ?>
