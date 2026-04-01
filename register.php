<?php
session_start();
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location: '.SITE_URL); exit; }
$pageTitle = 'Sign Up — ChopDrop';
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name  = trim($_POST['name']     ?? '');
    $email = trim($_POST['email']    ?? '');
    $phone = trim($_POST['phone']    ?? '');
    $pass  = $_POST['password']      ?? '';
    $pass2 = $_POST['password2']     ?? '';
    $addr  = trim($_POST['address']  ?? '');

    if (!$name)                                    $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($pass) < 6)                         $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2)                          $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $em = db()->real_escape_string($email);
        if (db()->query("SELECT id FROM users WHERE email='$em'")->num_rows > 0) {
            $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $n = db()->real_escape_string($name);
            $p = db()->real_escape_string($phone);
            $a = db()->real_escape_string($addr);
            db()->query("INSERT INTO users (name,email,phone,password,address) VALUES ('$n','$em','$p','$hash','$a')");
            $_SESSION['user_id'] = db()->insert_id;
            $_SESSION['name']    = $name;
            $_SESSION['role']    = 'customer';
            flash('success', "Welcome to ChopDrop, $name! 🎉");
            header('Location: '.SITE_URL); exit;
        }
    }
}
require_once 'includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center px-5 py-20">
  <div class="w-full max-w-md fade-up">
    <div class="glass rounded-3xl p-8 md:p-10">
      <div class="text-center mb-8">
        <div class="font-display text-3xl font-black mb-2"><span class="gold-text">Chop</span><span class="text-white">Drop</span></div>
        <h1 class="text-xl font-bold text-white">Create your account</h1>
        <p class="text-purple-300 text-sm mt-1">Join thousands of food lovers</p>
      </div>

      <?php foreach($errors as $err): ?>
      <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-xl px-4 py-3 text-sm mb-3"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" class="space-y-4">
        <?php
        $fields = [
          ['name','text','Full Name','Jean-Paul Mbarga'],
          ['email','email','Email Address','you@example.com'],
          ['phone','text','Phone Number','+237 6XX XXX XXX'],
          ['address','text','Delivery Address','Akwa, Douala'],
        ];
        foreach($fields as [$fname,$ftype,$flabel,$fph]):
        ?>
        <div>
          <label class="block text-sm font-semibold text-purple-200 mb-1.5"><?= $flabel ?></label>
          <input class="w-full bg-purple-900/50 border border-purple-600/40 rounded-xl px-4 py-3 text-white text-sm placeholder-purple-400"
            type="<?= $ftype ?>" name="<?= $fname ?>" value="<?= e($_POST[$fname]??'') ?>" placeholder="<?= $fph ?>"
            <?= in_array($fname,['name','email'])?'required':'' ?>/>
        </div>
        <?php endforeach; ?>
        <div>
          <label class="block text-sm font-semibold text-purple-200 mb-1.5">Password</label>
          <input class="w-full bg-purple-900/50 border border-purple-600/40 rounded-xl px-4 py-3 text-white text-sm placeholder-purple-400" type="password" name="password" placeholder="At least 6 characters" required/>
        </div>
        <div>
          <label class="block text-sm font-semibold text-purple-200 mb-1.5">Confirm Password</label>
          <input class="w-full bg-purple-900/50 border border-purple-600/40 rounded-xl px-4 py-3 text-white text-sm placeholder-purple-400" type="password" name="password2" placeholder="Repeat password" required/>
        </div>
        <button type="submit" class="btn-gold w-full rounded-xl py-3.5 text-base mt-2">Create Account</button>
      </form>

      <p class="text-center text-purple-300 text-sm mt-6">
        Already have an account? <a href="login.php" class="text-gold-400 font-bold hover:underline">Login</a>
      </p>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
