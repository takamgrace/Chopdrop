<?php
session_start(); require_once '../includes/config.php'; requireAdminOrVendor();
$pageTitle='Restaurants — ChopDrop Admin';
$db = db();
$isVendor = isVendor();
$vrid = getVendorRid();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    $targetId=(int)($_POST['id'] ?? 0);

    // Auth check for vendor
    if ($isVendor && $targetId !== $vrid) {
        flash('error', 'Unauthorized access.');
        header('Location: restaurants.php'); exit;
    }

    if ($action==='toggle') {
        if (!$isVendor) { flash('error', 'Only restaurant owners can open or close shops.'); header('Location: restaurants.php'); exit; }
        $db->query("UPDATE restaurants SET is_open=NOT is_open WHERE id=$targetId");
        flash('success','Restaurant status updated.');
    } elseif ($action==='add' && isAdmin()) {
        $n=$db->real_escape_string(trim($_POST['name']));
        $d=$db->real_escape_string(trim($_POST['description']));
        $c=$db->real_escape_string(trim($_POST['cuisine']));
        $a=$db->real_escape_string(trim($_POST['address']));
        $p=$db->real_escape_string(trim($_POST['phone']));
        $img = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $img = 'images/' . $filename;
                }
            }
        }
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '_logo.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $logo = 'images/' . $filename;
                }
            }
        }
        $dt=$db->real_escape_string(trim($_POST['delivery_time']));
        $df=(int)$_POST['delivery_fee'];
        $db->query("INSERT INTO restaurants (name,description,cuisine,address,phone,image,logo,delivery_time,delivery_fee) VALUES ('$n','$d','$c','$a','$p','$img','$logo','$dt',$df)");
        flash('success','Restaurant created!');
    } elseif ($action==='delete' && isAdmin()) {
        $db->query("DELETE FROM restaurants WHERE id=$targetId");
        flash('success','Restaurant deleted.');
    } elseif ($action==='activate' && isAdmin()) {
        $db->query("UPDATE restaurants SET is_active=NOT is_active WHERE id=$targetId");
        flash('success','Restaurant activation status updated.');
    } elseif ($action==='edit') {
        if (!isAdmin()) { flash('error', 'Only administrators can edit restaurant details.'); header('Location: restaurants.php'); exit; }
        $n=$db->real_escape_string(trim($_POST['name']));
        $d=$db->real_escape_string(trim($_POST['description']));
        $c=$db->real_escape_string(trim($_POST['cuisine']));
        $a=$db->real_escape_string(trim($_POST['address']));
        $p=$db->real_escape_string(trim($_POST['phone']));
        $dt=$db->real_escape_string(trim($_POST['delivery_time']));
        $df=(int)$_POST['delivery_fee'];
        
        $updateImg = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $updateImg = ", image='images/$filename'";
                }
            }
        }
        $updateLogo = "";
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $filename = uniqid() . '_logo.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../images/' . $filename)) {
                    $updateLogo = ", logo='images/$filename'";
                }
            }
        }
        $db->query("UPDATE restaurants SET name='$n',description='$d',cuisine='$c',address='$a',phone='$p',delivery_time='$dt',delivery_fee=$df $updateImg $updateLogo WHERE id=$targetId");
        flash('success','Restaurant updated!');
    }
    header('Location: restaurants.php'); exit;
}

$where = $isVendor ? "AND r.id=$vrid" : "";
$restaurants=$db->query("SELECT r.*, 
    (SELECT COUNT(*) FROM foods WHERE restaurant_id=r.id) fc, 
    (SELECT COUNT(*) FROM orders WHERE restaurant_id=r.id) oc
    FROM restaurants r 
    WHERE 1=1 $where
    ORDER BY r.is_active DESC, r.id DESC")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>
<div class="flex flex-col md:flex-row min-h-[calc(100vh-68px)] relative overflow-hidden">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8 overflow-x-auto relative">
  <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
    <div>
      <h1 class="font-display text-4xl font-black text-white"><?= $isVendor ? 'My Restaurant' : 'Restaurants' ?></h1>
      <p class="text-purple-400 mt-1"><?= $isVendor ? 'Update your store profile and operational status' : count($restaurants).' registered shops on the platform' ?></p>
    </div>
    <?php if(isAdmin()): ?>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn-gold rounded-2xl px-6 py-3 text-sm font-black shadow-lg shadow-gold/20 transition-all hover:scale-[1.02] active:scale-[0.98]">+ Add Restaurant</button>
    <?php endif; ?>
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

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php foreach($restaurants as $r): ?>
    <div class="glass rounded-3xl overflow-hidden border border-purple-700/30 card-hover group shadow-xl">
      <div class="relative h-44 overflow-hidden">
        <img src="<?= e($r['image'] ? '../'.$r['image'] : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80') ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"/>
        <div class="absolute inset-0 bg-gradient-to-t from-purple-950/80 to-transparent"></div>
        <div class="absolute top-4 left-4">
          <?php if($isVendor): ?>
          <form method="POST">
            <input type="hidden" name="action" value="toggle"/>
            <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
            <button type="submit" class="glass px-3 py-1.5 rounded-full flex items-center gap-2 border border-white/10 hover:bg-white/10 transition-all shadow-xl active:scale-95">
              <div class="w-2 h-2 rounded-full <?= $r['is_open'] ? 'bg-green-500 animate-pulse' : 'bg-red-500' ?>"></div>
              <span class="text-[10px] font-black uppercase tracking-widest text-white"><?= $r['is_open'] ? 'Open' : 'Closed' ?></span>
            </button>
          </form>
          <?php else: ?>
          <div class="glass px-3 py-1.5 rounded-full flex items-center gap-2 border border-white/10 shadow-xl">
            <div class="w-2 h-2 rounded-full <?= $r['is_open'] ? 'bg-green-500' : 'bg-red-500' ?>"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-white"><?= $r['is_open'] ? 'Open' : 'Closed' ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div class="absolute top-4 right-4 bg-black/40 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-black text-gold-400 border border-white/10 shadow-lg">★ <?= $r['rating'] ?></div>
        <div class="absolute bottom-4 left-5">
          <h3 class="font-display text-xl font-black text-white"><?= e($r['name']) ?></h3>
          <p class="text-gold-400 text-[10px] font-black uppercase tracking-widest"><?= e($r['cuisine']) ?></p>
        </div>
      </div>
      <div class="p-6">
        <p class="text-purple-300 text-sm mb-6 line-clamp-2 min-h-[40px]"><?= e($r['description']) ?></p>
        
        <div class="grid grid-cols-3 gap-4 mb-8">
          <div class="bg-purple-900/40 rounded-2xl py-3 border border-purple-700/20 text-center">
            <div class="text-white font-black text-lg"><?= $r['fc'] ?></div>
            <div class="text-[9px] text-purple-400 font-bold uppercase tracking-widest">Dishes</div>
          </div>
          <div class="bg-purple-900/40 rounded-2xl py-3 border border-purple-700/20 text-center">
            <div class="text-white font-black text-lg"><?= $r['oc'] ?></div>
            <div class="text-[9px] text-purple-400 font-bold uppercase tracking-widest">Sales</div>
          </div>
          <div class="bg-gold-500/10 rounded-2xl py-3 border border-gold-500/10 text-center">
            <div class="text-gold-400 font-black text-sm pt-1"><?= money($r['delivery_fee']) ?></div>
            <div class="text-[9px] text-gold-500/70 font-bold uppercase tracking-widest">Fee</div>
          </div>
        </div>

        <div class="flex gap-2 items-center pt-5 border-t border-purple-700/30">
          <a href="foods.php?restaurant_id=<?= $r['id'] ?>" class="flex-1 glass text-center py-3 rounded-xl text-xs font-black text-purple-200 hover:text-white hover:bg-gold-500/20 transition-all border border-transparent hover:border-gold-500/30">Menu Items</a>
          <?php if($isVendor): ?>
          <button type="button" onclick="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" class="flex-1 glass py-3 rounded-xl text-xs font-black text-purple-200 hover:text-white hover:bg-gold-500/20 transition-all border border-transparent hover:border-gold-500/30">
            Edit Profile
          </button>
          <?php endif; ?>
          
          <?php if(isAdmin()): ?>
            <form method="POST" class="flex-shrink-0">
              <input type="hidden" name="action" value="activate"/>
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <button type="submit" class="w-10 h-10 flex items-center justify-center <?= (!isset($r['is_active']) || $r['is_active']) ? 'text-blue-400 bg-blue-500/10 hover:bg-blue-500 hover:text-white' : 'text-gray-500 bg-gray-500/10 hover:bg-gray-500 hover:text-white' ?> rounded-xl transition-all shadow-lg active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </button>
            </form>
            <form method="POST" class="flex-shrink-0" onsubmit="return confirm('Permanently delete this restaurant?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-900/30 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-lg active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>
</div>

<!-- Add Restaurant Modal -->
<div id="addModal" style="display:none" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-5">
  <div class="glass rounded-3xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto border border-purple-600/40">
    <div class="flex justify-between items-center mb-8">
      <h2 class="font-display text-2xl font-black text-white">Register Restaurant</h2>
      <button onclick="document.getElementById('addModal').style.display='none'" class="text-purple-400 hover:text-white text-2xl transition-transform hover:rotate-90">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="action" value="add"/>
      <?php
      $ff=[['name','text','Restaurant Name','','required'],['description','text','Marketing Description','',''],['cuisine','text','Cuisines (e.g. African, Pizza)','',''],['address','text','Full Address','',''],['phone','text','Support Phone','','']];
      foreach($ff as [$fn,$ft,$fl,$fp,$req]): ?>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1"><?= $fl ?></label>
        <input type="<?= $ft ?>" name="<?= $fn ?>" placeholder="<?= $fp ?>" <?= $req ?>
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm placeholder-purple-600 outline-none focus:border-gold-500 transition-all"/>
      </div>
      <?php endforeach; ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Main Hero Image</label>
          <input type="file" name="image" accept="image/*"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-2.5 text-white text-xs file:hidden cursor-pointer"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Brand Logo</label>
          <input type="file" name="logo" accept="image/*"
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-2.5 text-white text-xs file:hidden cursor-pointer"/>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Delivery Time (min)</label>
          <input type="text" name="delivery_time" placeholder="20-30" required class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Delivery Fee (XAF)</label>
          <input type="number" name="delivery_fee" placeholder="500" required class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all"/>
        </div>
      </div>
      <button type="submit" class="btn-gold w-full rounded-2xl py-4 font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all mt-4">Create Establishment</button>
    </form>
  </div>
</div>

<!-- Edit Restaurant Modal -->
<div id="editModal" style="display:none" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-5">
  <div class="glass rounded-3xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto border border-purple-600/40">
    <div class="flex justify-between items-center mb-8">
      <h2 class="font-display text-2xl font-black text-white"><?= $isVendor ? 'Edit Profile' : 'Shop Details (Read-Only)' ?></h2>
      <button onclick="document.getElementById('editModal').style.display='none'" class="text-purple-400 hover:text-white text-2xl transition-transform hover:rotate-90">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" id="editId"/>
      <?php foreach($ff as [$fn,$ft,$fl,$fp,$req]): ?>
      <div>
        <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1"><?= $fl ?></label>
        <input type="<?= $ft ?>" name="<?= $fn ?>" id="edit_<?= $fn ?>" placeholder="<?= $fp ?>" <?= $req ?> <?= !$isVendor ? 'disabled' : '' ?>
          class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all <?= !$isVendor ? 'opacity-60 cursor-not-allowed' : '' ?>"/>
      </div>
      <?php endforeach; ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">New Hero Image</label>
          <input type="file" name="image" accept="image/*" <?= !$isVendor ? 'disabled' : '' ?>
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-2.5 text-white text-xs file:hidden <?= $isVendor ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' ?>"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">New Logo</label>
          <input type="file" name="logo" accept="image/*" <?= !$isVendor ? 'disabled' : '' ?>
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-2.5 text-white text-xs file:hidden <?= $isVendor ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed' ?>"/>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Avg Delivery Time</label>
          <input type="text" name="delivery_time" id="edit_delivery_time" <?= !$isVendor ? 'disabled' : '' ?> 
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all <?= !$isVendor ? 'opacity-60 cursor-not-allowed' : '' ?>"/>
        </div>
        <div>
          <label class="block text-xs font-bold text-purple-400 uppercase tracking-widest mb-2 ml-1">Delivery Fee (XAF)</label>
          <input type="number" name="delivery_fee" id="edit_delivery_fee" <?= !$isVendor ? 'disabled' : '' ?>
            class="w-full bg-purple-900/50 border border-purple-600/40 rounded-2xl px-4 py-3 text-white text-sm outline-none focus:border-gold-500 transition-all <?= !$isVendor ? 'opacity-60 cursor-not-allowed' : '' ?>"/>
        </div>
      </div>
      <?php if($isVendor): ?>
      <button type="submit" class="btn-gold w-full rounded-2xl py-4 font-black shadow-lg shadow-gold/20 hover:scale-[1.02] active:scale-[0.98] transition-all mt-4">Update Storefront</button>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
function openEdit(r) {
  document.getElementById('editId').value = r.id;
  document.getElementById('edit_name').value = r.name;
  document.getElementById('edit_description').value = r.description;
  document.getElementById('edit_cuisine').value = r.cuisine;
  document.getElementById('edit_address').value = r.address;
  document.getElementById('edit_phone').value = r.phone;
  document.getElementById('edit_delivery_time').value = r.delivery_time;
  document.getElementById('edit_delivery_fee').value = r.delivery_fee;
  document.getElementById('editModal').style.display='flex';
}
</script>

<?php require_once '../includes/footer.php'; ?>
