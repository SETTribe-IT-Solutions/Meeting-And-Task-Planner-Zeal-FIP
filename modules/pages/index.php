<?php
// modules/pages/index.php — Portal Page Manager (Organizer / Collector only)
require_once '../../config/db.php';
if (!isLoggedIn() || !isOrganizer()) {
    header('Location: ../users/login.php'); exit();
}

$conn  = getDBConnection();
$pages = $conn->query("SELECT slug, title, icon, updated_at FROM portal_pages ORDER BY id ASC")
              ->fetch_all(MYSQLI_ASSOC);

$editPage = null;
if (isset($_GET['edit'])) {
    $s = preg_replace('/[^a-z0-9\-]/', '', $_GET['edit']);
    $st = $conn->prepare("SELECT * FROM portal_pages WHERE slug = ? LIMIT 1");
    $st->bind_param('s', $s); $st->execute();
    $editPage = $st->get_result()->fetch_assoc();
}

$BASE = APP_URL;
include '../../includes/header.php';
?>
<style>
.pages-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;margin-top:20px}
.page-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);transition:box-shadow .2s}
.page-tile:hover{box-shadow:0 4px 14px rgba(0,0,0,.1)}
.tile-icon{width:44px;height:44px;background:linear-gradient(135deg,#003366,#004080);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#DAA520;font-size:1.3rem}
.tile-title{font-weight:700;font-size:.95rem;color:#1e293b}
.tile-slug{font-size:.72rem;color:#94a3b8;font-family:monospace}
.tile-updated{font-size:.72rem;color:#64748b}
.tile-actions{display:flex;gap:8px;margin-top:4px}
.btn-view{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:600;text-decoration:none;border:none;cursor:pointer}
.btn-edit-tile{background:#003366;color:#fff} .btn-edit-tile:hover{background:#004080;color:#fff;text-decoration:none}
.btn-preview-tile{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0} .btn-preview-tile:hover{background:#e2e8f0;text-decoration:none}
/* Edit modal */
.edit-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px}
.edit-modal{background:#fff;border-radius:14px;width:100%;max-width:860px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.edit-modal-header{background:#003366;color:#fff;padding:16px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:center}
.edit-modal-header h5{margin:0;font-size:1rem;font-weight:700}
.modal-close{background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;line-height:1;opacity:.8} .modal-close:hover{opacity:1}
.edit-modal-body{padding:20px 24px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:14px}
.edit-modal-footer{padding:14px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px}
.field-label{font-size:.78rem;font-weight:600;color:#374151;margin-bottom:4px;display:block}
.field-hint{font-size:.7rem;color:#94a3b8;margin-top:4px}
.tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:12px}
.tab-btn{padding:7px 18px;font-size:.82rem;font-weight:600;border:none;background:none;cursor:pointer;color:#64748b;border-bottom:2px solid transparent;margin-bottom:-2px}
.tab-btn.active{color:#003366;border-bottom-color:#003366}
.tab-pane{display:none} .tab-pane.active{display:block}
#contentEditor{width:100%;height:260px;border:1px solid #cbd5e1;border-radius:8px;padding:12px;font-size:.82rem;font-family:monospace;resize:vertical;line-height:1.6;color:#1e293b}
#contentEditor:focus{outline:none;border-color:#003366;box-shadow:0 0 0 2px rgba(0,51,102,.15)}
#previewPane{min-height:200px;border:1px solid #e2e8f0;border-radius:8px;padding:16px;background:#fafafa;font-size:.88rem;line-height:1.7;color:#1e293b;overflow-y:auto}
#previewPane h4{color:#003366;font-size:.95rem;font-weight:700;margin:16px 0 6px;padding-bottom:4px;border-bottom:2px solid #DAA520}
#previewPane h4:first-child{margin-top:0}
#previewPane p{margin-bottom:10px} #previewPane ul{margin:0 0 12px 18px}
#previewPane table{width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:12px}
#previewPane table th{background:#003366;color:#fff;padding:8px 12px;text-align:left}
#previewPane table td{padding:8px 12px;border-bottom:1px solid #e5d9b5;vertical-align:top}
#previewPane table tr:nth-child(even){background:#f9f6f0}
</style>

<div class="container-fluid px-4 py-3">
  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="fw-bold mb-0" style="color:var(--gov-blue)"><i class="fas fa-globe me-2 text-primary"></i>Portal Pages</h4>
      <p class="text-muted small mb-0 mt-1">Manage public-facing content visible to all visitors on the portal.</p>
    </div>
    <a href="<?php echo $BASE; ?>/modules/users/login.php" target="_blank"
       class="btn btn-sm btn-outline-primary rounded-3 fw-semibold">
      <i class="fas fa-external-link-alt me-1"></i> View Portal
    </a>
  </div>

  <?php if (!empty($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible rounded-3 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger alert-dismissible rounded-3 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="pages-grid">
    <?php foreach ($pages as $p): ?>
    <div class="page-tile">
      <div class="d-flex align-items-center gap-3">
        <div class="tile-icon"><i class="bi <?php echo htmlspecialchars($p['icon']); ?>"></i></div>
        <div>
          <div class="tile-title"><?php echo htmlspecialchars($p['title']); ?></div>
          <div class="tile-slug">/public/page.php?slug=<?php echo $p['slug']; ?></div>
        </div>
      </div>
      <div class="tile-updated"><i class="fas fa-clock me-1 text-muted"></i>Updated: <?php echo date('d M Y, h:i A', strtotime($p['updated_at'])); ?></div>
      <div class="tile-actions">
        <button class="btn-view btn-edit-tile"
                onclick="openEdit('<?php echo $p['slug']; ?>')">
          <i class="fas fa-edit"></i> Edit
        </button>
        <a href="<?php echo $BASE; ?>/public/page.php?slug=<?php echo $p['slug']; ?>"
           target="_blank" class="btn-view btn-preview-tile">
          <i class="fas fa-eye"></i> View
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Edit Modal -->
<div class="edit-modal-overlay" id="editOverlay" style="display:none" onclick="closeOnBackdrop(event)">
  <div class="edit-modal" id="editModal">
    <div class="edit-modal-header">
      <h5><i class="bi bi-pencil-square me-2"></i><span id="modalTitle">Edit Page</span></h5>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <form action="<?php echo $BASE; ?>/controllers/PageController.php" method="POST" id="editForm">
      <div class="edit-modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <input type="hidden" name="slug" id="editSlug">

        <div>
          <label class="field-label">Page Title</label>
          <input type="text" name="title" id="editTitle" class="form-control form-control-sm rounded-3" maxlength="200" required>
        </div>

        <div>
          <label class="field-label">Page Content</label>
          <div class="tabs">
            <button type="button" class="tab-btn active" onclick="switchTab('editor',this)">✏️ HTML Editor</button>
            <button type="button" class="tab-btn" onclick="switchTab('preview',this)">👁 Preview</button>
          </div>
          <div class="tab-pane active" id="tabEditor">
            <textarea name="content" id="contentEditor" placeholder="Enter HTML content here…"></textarea>
            <div class="field-hint">
              Supports HTML: &lt;h4&gt; for headings · &lt;p&gt; for paragraphs · &lt;ul&gt;&lt;li&gt; for lists · &lt;table&gt;&lt;tr&gt;&lt;td&gt; for tables · &lt;strong&gt; for bold
            </div>
          </div>
          <div class="tab-pane" id="tabPreview">
            <div id="previewPane"></div>
          </div>
        </div>
      </div>
      <div class="edit-modal-footer">
        <button type="button" class="btn btn-outline-secondary rounded-3" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
          <i class="fas fa-save me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const pagesData = <?php echo json_encode(array_column(
    $conn->query("SELECT slug, title, content FROM portal_pages")->fetch_all(MYSQLI_ASSOC),
    null, 'slug'
)); ?>;

function openEdit(slug) {
    const p = pagesData[slug];
    if (!p) return;
    document.getElementById('editSlug').value  = slug;
    document.getElementById('editTitle').value = p.title;
    document.getElementById('contentEditor').value = p.content;
    document.getElementById('modalTitle').textContent = 'Edit: ' + p.title;
    document.getElementById('previewPane').innerHTML = p.content;
    // reset to editor tab
    switchTab('editor', document.querySelector('.tab-btn'));
    document.getElementById('editOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEdit() {
    document.getElementById('editOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function closeOnBackdrop(e) {
    if (e.target === document.getElementById('editOverlay')) closeEdit();
}

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab' + (tab === 'editor' ? 'Editor' : 'Preview')).classList.add('active');
    btn.classList.add('active');
    if (tab === 'preview') {
        document.getElementById('previewPane').innerHTML = document.getElementById('contentEditor').value;
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => { el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>
