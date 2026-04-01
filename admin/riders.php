<?php
session_start(); require_once '../includes/config.php'; requireVendor();
$db = db();
$rid = getVendorRid();
$pageTitle = 'My Riders — ChopDrop Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = $db->real_escape_string($_POST['name']);
        $email = $db->real_escape_string($_POST['email']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phone = $db->real_escape_string($_POST['phone']);
        
        // Check if exists
        $check = $db->query("SELECT id FROM users WHERE email='$email'")->fetch_assoc();
        if ($check) {
            flash('error', 'A user with this email already exists.');
        } else {
            $db->query("INSERT INTO users (name, email, password, role, restaurant_id, phone) 
                        VALUES ('$name', '$email', '$pass', 'rider', $rid, '$phone')");
            flash('success', "Rider $name registered successfully.");
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM users WHERE id=$id AND restaurant_id=$rid AND role='rider'");
        flash('success', 'Rider account removed.');
    }
    
    header('Location: riders.php'); exit;
}

$riders = $db->query("SELECT * FROM users WHERE restaurant_id=$rid AND role='rider' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white">My Riders</h1>
      <p class="text-purple-400 mt-1">Manage delivery personnel for <?= e($_SESSION['name']) ?></p>
    </div>
    <button onclick="document.getElementById('addRiderModal').classList.remove('hidden')" class="btn-gold rounded-2xl px-6 py-3 text-sm font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
      <span class="text-xl">+</span> Hire New Rider
    </button>
  </div>

  <?php if($f=flash('success')): ?>
  <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
    <span>✅</span> <?= e($f) ?>
  </div>
  <?php endif; ?>
  <?php if($f=flash('error')): ?>
  <div class="bg-red-900/40 border border-red-500/40 text-red-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
    <span>⚠️</span> <?= e($f) ?>
  </div>
  <?php endif; ?>

  <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-purple-700/30 bg-purple-900/20">
            <?php foreach(['Rider Info','Contact','Joined','Status','Action'] as $h): ?>
            <th class="px-6 py-5 text-left text-[10px] font-black text-purple-400 uppercase tracking-widest whitespace-nowrap"><?= $h ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($riders as $r): ?>
        <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
          <td class="px-6 py-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center font-black shadow-lg text-sm flex-shrink-0">
                <?= strtoupper(mb_substr($r['name'],0,1)) ?>
              </div>
              <div class="text-white font-bold whitespace-nowrap"><?= e($r['name']) ?></div>
            </div>
          </td>
          <td class="px-6 py-5">
            <div class="text-purple-300 text-xs font-semibold"><?= e($r['email']) ?></div>
            <div class="text-purple-500 text-[10px] font-bold"><?= e($r['phone'] ?? 'N/A') ?></div>
          </td>
          <td class="px-6 py-5 text-purple-400 text-xs font-semibold whitespace-nowrap"><?= date('d M, Y', strtotime($r['created_at'])) ?></td>
          <td class="px-6 py-5">
            <?php if($r['is_online']): ?>
              <span class="bg-emerald-900/40 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1.5 w-fit">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Online
              </span>
            <?php else: ?>
              <span class="bg-red-900/40 text-red-500 border border-red-500/30 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1.5 w-fit">
                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Offline
              </span>
            <?php endif; ?>
          </td>
          <td class="px-6 py-5">
            <form method="POST" onsubmit="return confirm('Remove rider <?= e($r['name']) ?>?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <button type="submit" class="w-9 h-9 flex items-center justify-center bg-red-900/30 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-lg active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($riders)): ?>
        <tr><td colspan="5" class="px-6 py-24 text-center">
          <div class="text-6xl mb-4 opacity-20">🚴</div>
          <p class="text-purple-400 font-bold">No riders assigned to your restaurant yet.</p>
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>

<!-- Add Rider Modal -->
<div id="addRiderModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center px-5 bg-purple-950/60 backdrop-blur-md">
  <div class="glass w-full max-w-md rounded-[40px] p-8 border border-gold-500/20 shadow-2xl animate-fade-up">
    <div class="flex justify-between items-center mb-8">
      <h3 class="font-display text-2xl font-black text-white">Hire New Rider</h3>
      <button onclick="document.getElementById('addRiderModal').classList.add('hidden')" class="text-purple-500 hover:text-white">✕</button>
    </div>
    <form method="POST" class="space-y-5">
      <input type="hidden" name="action" value="add"/>
      <div>
        <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Full Name</label>
        <input name="name" required class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm focus:border-gold-500 transition-all outline-none"/>
      </div>
      <div>
        <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
        <input type="email" name="email" required class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm focus:border-gold-500 transition-all outline-none"/>
      </div>
      <div>
        <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Temporary Password</label>
        <input type="password" name="password" required class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm focus:border-gold-500 transition-all outline-none"/>
      </div>
       <div>
        <label class="block text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2 ml-1">Phone Number</label>
        <input name="phone" required class="w-full bg-purple-900/40 border border-purple-700/30 rounded-2xl px-5 py-4 text-white text-sm focus:border-gold-500 transition-all outline-none"/>
      </div>
      <button type="submit" class="btn-gold w-full rounded-2xl py-5 text-sm font-black uppercase tracking-widest shadow-xl shadow-gold/20 active:scale-95 transition-all">
        Create Rider Account
      </button>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
