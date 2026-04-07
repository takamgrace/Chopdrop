<?php
session_start(); require_once '../includes/config.php'; requireAdmin();
$db=db();
$pageTitle = 'Vendors — ChopDrop Admin';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action==='delete' && $id!==(int)$_SESSION['user_id']) {
        $db->query("DELETE FROM users WHERE id=$id AND role='vendor'"); 
        flash('success','Vendor account removed.'); 
    } elseif ($action==='toggle_status') {
        $db->query("UPDATE users SET is_active = NOT is_active WHERE id=$id AND role='vendor'");
        flash('success','Vendor account status updated.');
    }
    header('Location: users.php'); exit;
}

$search=trim($_GET['q']??'');
$s=$db->real_escape_string($search);
$whereSearch = $search ? "AND (u.name LIKE '%$s%' OR u.email LIKE '%$s%')" : "";

// EXCLUSIVE VENDOR OVERSIGHT. Only Vendors.
$users=$db->query("SELECT u.*,
    r.name as rname, r.id as rid
    FROM users u 
    LEFT JOIN restaurants r ON r.id=u.restaurant_id
    WHERE u.role = 'vendor' $whereSearch 
    ORDER BY u.created_at DESC")->fetch_all();
require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white">Vendor Management</h1>
      <p class="text-purple-400 mt-1"><?= count($users) ?> restaurant partners currently managed globally</p>
    </div>
    <form method="GET" class="flex gap-3">
      <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-500">🔍</span>
        <input name="q" value="<?= e($search) ?>" placeholder="Search vendors..." 
          class="bg-purple-900/40 border border-purple-700/30 rounded-2xl pl-12 pr-4 py-3 text-white text-sm placeholder-purple-600 focus:border-gold-500 transition-all outline-none w-64 shadow-lg"/>
      </div>
      <button type="submit" class="btn-gold rounded-2xl px-6 py-3 text-sm font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all">Search</button>
    </form>
  </div>

  <?php if($f=flash('success')): ?>
  <div class="bg-green-900/40 border border-green-500/40 text-green-300 rounded-2xl px-6 py-4 text-sm mb-6 flex items-center gap-3">
    <span>✅</span> <?= e($f) ?>
  </div>
  <?php endif; ?>

  <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-purple-700/30 bg-purple-900/20">
            <?php foreach(['Vendor Account','Email Address','Managed Restaurant','Account Status','Action'] as $h): ?>
            <th class="px-6 py-5 text-left text-[10px] font-black text-purple-400 uppercase tracking-widest whitespace-nowrap"><?= $h ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr class="border-b border-purple-700/20 hover:bg-purple-800/10 transition-all duration-300">
          <td class="px-6 py-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center font-black text-blue-950 shadow-lg text-sm flex-shrink-0">
                <?= strtoupper(mb_substr($u['name'],0,1)) ?>
              </div>
              <div class="text-white font-bold whitespace-nowrap"><?= e($u['name']) ?></div>
            </div>
          </td>
          <td class="px-6 py-5 text-purple-300 text-xs font-semibold"><?= e($u['email']) ?></td>
          <td class="px-6 py-5">
            <?php if($u['rname']): ?>
              <div class="text-gold-400 font-black"><?= e($u['rname']) ?></div>
              <div class="text-[9px] text-purple-500 uppercase font-black tracking-widest mt-1">Linked Shop</div>
            <?php else: ?>
              <span class="text-purple-600 text-[10px] font-black uppercase tracking-widest">No Shop Linked</span>
            <?php endif; ?>
          </td>
          <td class="px-6 py-5">
            <form method="POST">
              <input type="hidden" name="action" value="toggle_status"/>
              <input type="hidden" name="id" value="<?= $u['id'] ?>"/>
              <button type="submit" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all shadow-md active:scale-95
                <?= $u['is_active'] ? 'bg-emerald-900/30 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500 hover:text-white' : 'bg-red-900/30 text-red-400 border-red-500/30 hover:bg-red-500 hover:text-white' ?>">
                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
              </button>
            </form>
          </td>
          <td class="px-6 py-5">
            <div class="flex items-center gap-2">
              <form method="POST" onsubmit="return confirm('Remove account for <?= e($u['name']) ?>?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="id" value="<?= $u['id'] ?>"/>
                <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-900/30 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-lg active:scale-95">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</div>
<?php require_once '../includes/header.php'; ?>
