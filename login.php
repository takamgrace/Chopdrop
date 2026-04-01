<?php
// login.php
session_start();
require_once 'includes/config.php';
if (isLoggedIn()) {
    if (isAdmin() || isVendor()) header('Location: '.SITE_URL.'/admin/index.php');
    elseif (isset($_SESSION['role']) && $_SESSION['role']==='rider') header('Location: '.SITE_URL.'/admin/my-orders.php');
    else header('Location: '.SITE_URL);
    exit;
}
$pageTitle = 'Login — ChopDrop';
$error = '';
$selectedRole = $_POST['role'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'customer';
    $em    = db()->real_escape_string($email);
    
    $row   = db()->query("SELECT * FROM users WHERE email='$em'")->fetch_assoc();
    
    if ($row && password_verify($pass, $row['password'])) {
        if ($row['role'] !== $role) {
            $error = "Incorrect role selected for this account.";
        } else {
            $_SESSION['user_id']       = $row['id'];
            $_SESSION['name']          = $row['name'];
            $_SESSION['role']          = $row['role'];
            $_SESSION['restaurant_id'] = $row['restaurant_id'] ?? null;
            
            if (in_array($row['role'], ['admin', 'vendor'])) {
                header('Location: '.SITE_URL.'/admin/index.php'); exit;
            } elseif ($row['role'] === 'rider') {
                header('Location: '.SITE_URL.'/admin/my-orders.php'); exit;
            } else {
                header('Location: '.($_GET['redirect'] ?? SITE_URL)); exit;
            }
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
require_once 'includes/header.php';
?>
<div class="min-h-screen flex items-center justify-center px-5 py-20 relative overflow-hidden">
  <!-- Background Decoration -->
  <div class="absolute top-0 right-0 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl -mr-48 -mt-48"></div>
  <div class="absolute bottom-0 left-0 w-96 h-96 bg-gold-400/5 rounded-full blur-3xl -ml-48 -mb-48"></div>

  <div class="w-full max-w-md fade-up z-10">
    <!-- Card -->
    <div class="glass rounded-3xl p-8 md:p-10 border border-purple-500/20 shadow-2xl">
      <div class="text-center mb-8">
        <div class="font-display text-4xl font-black mb-3"><span class="gold-text">Chop</span><span class="text-white">Drop</span></div>
        <h1 class="text-xl font-bold text-white">Welcome back</h1>
        <p class="text-purple-300 text-sm mt-1">Please select your role and login</p>
      </div>

      <?php if($error): ?>
      <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-5 py-3 text-sm mb-6 flex items-center gap-3">
        <span>⚠️</span> <?= e($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="space-y-6">
        <!-- Role Selection -->
        <div class="grid grid-cols-2 gap-2 p-1 bg-purple-950/50 rounded-2xl border border-purple-700/30">
          <?php 
          $roles = [
            ['customer', '👤 Customer'],
            ['vendor', '🏪 Restaurant'],
            ['rider', '🚴 Rider'],
            ['admin', '🛡️ Admin']
          ];
          foreach($roles as [$r, $label]): 
            $active = $selectedRole === $r;
          ?>
          <label class="cursor-pointer">
            <input type="radio" name="role" value="<?= $r ?>" class="hidden peer" <?= $active ? 'checked' : '' ?> onchange="this.form.role_val.value=this.value"/>
            <div class="text-center py-2.5 rounded-xl text-xs font-bold transition-all peer-checked:bg-gold-500 peer-checked:text-purple-950 text-purple-300 hover:text-white">
              <?= $label ?>
            </div>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-400">📧</span>
              <input class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl pl-12 pr-4 py-4 text-white text-sm placeholder-purple-500 focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition-all outline-none" 
                type="email" name="email" value="<?= e($_POST['email']??'') ?>" placeholder="name@example.com" required autofocus/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Password</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-400">🔒</span>
              <input class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl pl-12 pr-4 py-4 text-white text-sm placeholder-purple-500 focus:border-gold-500 focus:ring-1 focus:ring-gold-500/30 transition-all outline-none" 
                type="password" name="password" placeholder="••••••••" required/>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-gold w-full rounded-2xl py-4 text-base font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
          Login to Account
        </button>
      </form>

      <p class="text-center text-purple-300 text-sm mt-8">
        New to ChopDrop? <a href="register.php" class="text-gold-400 font-bold hover:text-gold-300 transition-colors underline underline-offset-4">Create account</a>
      </p>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
