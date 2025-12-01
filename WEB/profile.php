<?php
// profile.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// fetch semesters
$stmt = $mysqli->prepare("SELECT id, semester_no, sgpa, total_credits FROM semesters WHERE user_id = ? ORDER BY semester_no ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$totalQuality = 0;
$totalCredits = 0;
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $totalQuality += floatval($r['sgpa']) * floatval($r['total_credits']);
    $totalCredits += floatval($r['total_credits']);
}
$cgpa = $totalCredits > 0 ? round($totalQuality / $totalCredits, 2) : 0.00;
$full = htmlspecialchars($_SESSION['user_name']);
$usern = htmlspecialchars($_SESSION['user_username']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>UniGrade Tracker — Profile</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header id="site-header">
    <div class="container header-inner">
      <a href="home.html" id="logo"><span class="logo-mark">UN</span> UniGrade Tracker</a>

      <nav id="main-nav">
        <a href="home.html" class="nav-link">Home</a>
        <a href="about.html" class="nav-link">About</a>
        <a href="profile.php" class="nav-link active">My Profile</a>
      </nav>

      <div class="header-actions">
        <button class="btn dark">Dark Mode</button>
        <a href="logout.php" class="btn sign">Logout</a>
      </div>
    </div>
  </header>

  <main class="container profile-page">
    <div class="two-col">
      <aside class="card profile-summary">
        <h3>Profile Summary</h3>
        <div style="padding:14px;text-align:center">
          <p><strong>Username:</strong> <?=$usern?></p>
          <p><strong>Full name:</strong> <?=$full?></p>
        </div>
      </aside>

      <section class="card academic-record">
        <h3>Academic Record & Saved SGPAs</h3>

        <table class="sgpa-table">
          <thead>
            <tr><th>Semester</th><th>SGPA</th><th>Credits</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (count($rows) === 0): ?>
              <tr><td colspan="4" class="muted">No semesters saved yet.</td></tr>
            <?php else: foreach($rows as $r): ?>
              <tr>
                <td>Semester <?=intval($r['semester_no'])?></td>
                <td><?=htmlspecialchars($r['sgpa'])?></td>
                <td><?=htmlspecialchars($r['total_credits'])?></td>
                <td>
                  <a class="btn editcg" href="#" onclick="editSemester(<?=intval($r['id'])?>, <?=intval($r['semester_no'])?>, <?=htmlspecialchars($r['sgpa'])?>, <?=htmlspecialchars($r['total_credits'])?>); return false;">Edit</a>
                  <a class="btn another" href="#" onclick="deleteSemester(<?=intval($r['id'])?>); return false;">Delete</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>

        <div class="semester-actions" style="margin-top:12px;">
          <form id="add-semester-form" method="post" action="save_result.php" onsubmit="return submitFormAjax(this);">
            <label style="display:block;margin-bottom:6px;font-weight:600">Add Semester</label>
            <input name="semester_no" class="input small" placeholder="No." type="number" required style="width:100px;display:inline-block;margin-right:8px" />
            <input name="sgpa" class="input small" placeholder="SGPA" type="number" step="0.01" required style="width:100px;display:inline-block;margin-right:8px" />
            <input name="total_credits" class="input small" placeholder="Credits" type="number" step="0.01" required style="width:100px;display:inline-block;margin-right:8px" />
            <button class="btn another" type="submit">+ Add Semester</button>
          </form>
        </div>

        <div class="cgpa-box" style="margin-top:14px;">
          <p class="muted">Calculated from saved semesters</p>
          <h2>Current CGPA: <?=number_format($cgpa,2)?></h2>
        </div>
      </section>
    </div>
  </main>

  <footer id="site-footer">Copyright © UniGrade Tracker. All rights reserved.</footer>

  <script src="web.js"></script>
  <script>
  // plain-text AJAX handlers (no JSON)
  function submitFormAjax(form) {
    const data = new URLSearchParams(new FormData(form));
    fetch(form.action, { method:'POST', body: data })
      .then(r => r.text())
      .then(txt => {
        const t = (txt || '').trim();
        if (t === 'OK') window.location.reload();
        else if (t.startsWith('ERROR:')) alert('Save failed: ' + t.substring(6));
        else alert('Unexpected response: ' + t);
      })
      .catch(e => { alert('Request failed'); console.error(e); });
    return false;
  }

  function deleteSemester(id) {
    if (!confirm('Delete this semester?')) return;
    fetch('delete_semester.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'id=' + encodeURIComponent(id)
    })
    .then(r => r.text())
    .then(txt => {
      const t = (txt || '').trim();
      if (t === 'OK') window.location.reload();
      else if (t.startsWith('ERROR:')) alert('Delete failed: ' + t.substring(6));
      else alert('Unexpected response: ' + t);
    }).catch(e => { alert('Delete request failed'); console.error(e); });
  }

  function editSemester(id, semNo, sgpa, credits) {
    const newSgpa = prompt('Enter SGPA for semester ' + semNo, sgpa);
    if (newSgpa === null) return;
    const newCredits = prompt('Enter total credits for semester ' + semNo, credits);
    if (newCredits === null) return;
    const data = new URLSearchParams();
    data.append('semester_no', semNo);
    data.append('sgpa', parseFloat(newSgpa));
    data.append('total_credits', parseFloat(newCredits));
    fetch('save_result.php', { method:'POST', body: data })
      .then(r => r.text())
      .then(txt => {
        const t = (txt || '').trim();
        if (t === 'OK') window.location.reload();
        else if (t.startsWith('ERROR:')) alert('Update failed: ' + t.substring(6));
        else alert('Unexpected response: ' + t);
      }).catch(e => { alert('Update failed'); console.error(e); });
  }
  </script>
</body>
</html>
