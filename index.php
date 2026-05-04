<?php
$dataFile = __DIR__ . '/data/current-week.json';

// Backend wiring: create/load the JSON store used by the locked prototype UI.
if (!file_exists($dataFile)) {
  $dataDir = dirname($dataFile);
  if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
  }
  file_put_contents($dataFile, json_encode(default_week_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
}

$weekData = json_decode(file_get_contents($dataFile), true);
if (!is_array($weekData) || !isset($weekData['original'], $weekData['current'])) {
  http_response_code(500);
  echo 'Workout data is missing or invalid.';
  exit;
}

function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function json_attr($value) {
  return h(json_encode($value, JSON_UNESCAPED_SLASHES));
}

function sets_for_attr($sets) {
  return array_map(function ($set) {
    return [(int) $set['reps'], (int) $set['weight']];
  }, $sets);
}

function default_week_data() {
  $exercises = [
    [
      'name' => 'Bench Press',
      'day' => 'Monday',
      'group' => 'Chest / Push',
      'mainSets' => [['reps' => 10, 'weight' => 135], ['reps' => 8, 'weight' => 155], ['reps' => 6, 'weight' => 165]],
      'warmUpSets' => [['reps' => 12, 'weight' => 45], ['reps' => 8, 'weight' => 95]],
      'difficulty' => 'Medium',
      'notes' => ''
    ],
    [
      'name' => 'Lat Pulldown',
      'day' => 'Wednesday',
      'group' => 'Back / Pull',
      'mainSets' => [['reps' => 12, 'weight' => 90], ['reps' => 10, 'weight' => 105], ['reps' => 8, 'weight' => 120]],
      'warmUpSets' => [['reps' => 12, 'weight' => 45], ['reps' => 8, 'weight' => 70]],
      'difficulty' => 'Medium',
      'notes' => ''
    ],
    [
      'name' => 'Leg Press',
      'day' => 'Friday',
      'group' => 'Legs',
      'mainSets' => [['reps' => 12, 'weight' => 180], ['reps' => 10, 'weight' => 230], ['reps' => 8, 'weight' => 270]],
      'warmUpSets' => [['reps' => 12, 'weight' => 90], ['reps' => 8, 'weight' => 140]],
      'difficulty' => 'Medium',
      'notes' => ''
    ],
    [
      'name' => 'Arm Curls',
      'day' => 'Saturday',
      'group' => 'Arms',
      'mainSets' => [['reps' => 12, 'weight' => 25], ['reps' => 10, 'weight' => 30], ['reps' => 8, 'weight' => 35]],
      'warmUpSets' => [['reps' => 12, 'weight' => 10], ['reps' => 8, 'weight' => 15]],
      'difficulty' => 'Medium',
      'notes' => ''
    ]
  ];

  $week = [
    'profileName' => 'Chris',
    'weekLabel' => 'May 4',
    'dayDateLabel' => 'Monday',
    'exercises' => $exercises
  ];

  return ['original' => $week, 'current' => $week];
}

$current = $weekData['current'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Workout Tracker</title>
  <style>
    :root {
      --bg: #0f172a;
      --panel-soft: #17233d;
      --card: #f8fafc;
      --card-muted: #e2e8f0;
      --text: #0f172a;
      --text-soft: #64748b;
      --white: #ffffff;
      --accent: #38bdf8;
      --accent-2: #22c55e;
      --shadow: 0 18px 45px rgba(0, 0, 0, 0.22);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: radial-gradient(circle at top, #1e3a5f 0%, var(--bg) 48%, #020617 100%);
      color: var(--white);
      min-height: 100vh;
    }

    body.edit-mode {
      overflow: hidden;
      touch-action: none;
    }

    .app-shell {
      width: min(430px, 100%);
      margin: 0 auto;
      min-height: 100vh;
      padding: 18px 16px 92px;
    }

    .top-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .profile-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.13);
      border-radius: 999px;
      padding: 7px 12px 7px 7px;
      backdrop-filter: blur(12px);
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, var(--accent), #818cf8);
      font-weight: 800;
      color: #020617;
    }

    .profile-name { font-size: 14px; font-weight: 700; }

    .icon-button {
      width: 42px;
      height: 42px;
      border: 0;
      border-radius: 50%;
      color: var(--white);
      background: rgba(255, 255, 255, 0.1);
      font-size: 20px;
      cursor: pointer;
    }

    .hero { margin-bottom: 18px; }

    .eyebrow {
      color: #bae6fd;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    h1 {
      margin: 0;
      font-size: 34px;
      line-height: 1.05;
      letter-spacing: -0.05em;
    }

    .hero p {
      color: #cbd5e1;
      margin: 10px 0 0;
      font-size: 15px;
      line-height: 1.45;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin: 18px 0;
    }

    .summary-card {
      background: rgba(255, 255, 255, 0.11);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 18px;
      padding: 14px 10px;
      text-align: center;
      backdrop-filter: blur(10px);
    }

    .summary-value {
      font-size: 22px;
      font-weight: 850;
      letter-spacing: -0.03em;
    }

    .summary-label {
      color: #cbd5e1;
      font-size: 12px;
      margin-top: 4px;
    }

    .week-card {
      background: var(--card);
      color: var(--text);
      border-radius: 30px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .week-header {
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--card-muted);
      transition: background 0.2s ease, border-color 0.2s ease;
      position: relative;
      background: #bae6fd;
    }

    
    .week-header.unlocked {
      background: #fee2e2;
      border-bottom-color: #fecaca;
    }

    

    

    

    body.snapshot-mode .exercise-meta {
      color: #cbd5e1;
    }

    body.snapshot-mode .workout-item {
      background: #111827;
      color: #e2e8f0;
      border-color: #1f2937;
    }

    body.snapshot-mode .set-pill {
      background: #1f2937;
      color: #e2e8f0;
      border: 1px solid #334155;
    }

    body.snapshot-mode .notes-btn,
    body.snapshot-mode .difficulty-select,
    body.snapshot-mode .warmup-badge {
      filter: brightness(0.9);
    }

    body.snapshot-mode .snapshot-toggle {
      display: inline-block;
      background: #e2e8f0;
      color: #0f172a;
      border: 1px solid #94a3b8;
      margin-left: auto; /* push group to far right */
    }

    body.snapshot-mode .small-btn,
    body.snapshot-mode .lock-toggle {
      display: none; /* don't take space */
    }

    .original-big {
      display: none;
      color: var(--text);
      font-size: 11px;
      font-weight: 900;
      line-height: 1.05;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      min-width: 54px;
      background: #bae6fd;
      border-radius: 999px;
      padding: 6px 10px;
      cursor: pointer;
    }

    body.snapshot-mode .original-big {
      display: block;
    }

    body.snapshot-mode .notes-btn,
    body.snapshot-mode .difficulty-select,
    body.snapshot-mode .set-pill,
    body.snapshot-mode .primary-action,
    body.snapshot-mode .secondary-action {
      pointer-events: none;
      cursor: default;
    }

    .week-title {
      font-size: 18px;
      font-weight: 850;
      letter-spacing: -0.03em;
    }

    .week-subtitle {
      color: var(--text);
      font-size: 13px;
      margin-top: 3px;
    }

    .week-footer {
      min-height: 58px;
      padding: 14px 20px;
      border-top: 1px solid var(--card-muted);
      background: #bae6fd;
    }

    .save-status {
      color: #0369a1;
      font-size: 12px;
      font-weight: 850;
      min-height: 16px;
      text-align: right;
    }

    .week-nav {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    body.snapshot-mode .week-nav {
      gap: 4px;
      justify-content: flex-end;
      margin-left: auto;
    }

    .small-btn, .lock-toggle, .snapshot-toggle {
      border: 0;
      border-radius: 999px;
      padding: 8px 11px;
      font-weight: 900;
      cursor: pointer;
    }

    .small-btn {
      background: #e0f2fe;
      color: #0369a1;
    }

    .lock-toggle {
      background: #e2e8f0;
      color: #475569;
    }

    .snapshot-toggle {
      background: #111827;
      color: #ffffff;
    }

    .lock-toggle.unlocked {
      background: #fecaca;
      color: #7f1d1d;
      animation: lockPulse 1.8s ease-in-out infinite;
    }

    @keyframes lockPulse {
      0%, 100% {
        background: #fecaca;
        color: #7f1d1d;
        box-shadow: 0 0 0 rgba(127, 29, 29, 0);
      }
      50% {
        background: #991b1b;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(153, 27, 27, 0.16);
      }
    }

    .workout-list {
      padding: 14px;
      display: grid;
      gap: 12px;
      max-height: 350px;
      overflow-y: auto;
      overscroll-behavior: contain;
      padding-right: 8px;
    }

    body.edit-mode .workout-list {
      overflow: hidden;
      touch-action: none;
    }

    .workout-item {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 22px;
      padding: 15px;
      display: grid;
      gap: 12px;
    }

    .workout-main {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: flex-start;
    }

    .exercise-name {
      font-size: 17px;
      font-weight: 850;
      letter-spacing: -0.02em;
      margin-bottom: 4px;
    }

    .exercise-meta {
      color: var(--text-soft);
      font-size: 13px;
    }

    .card-actions {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-shrink: 0;
    }

    .notes-btn, .difficulty-select {
      border: 0;
      border-radius: 999px;
      padding: 7px 10px;
      font-size: 12px;
      font-weight: 850;
      cursor: pointer;
      outline: none;
    }

    .notes-btn {
      background: #e2e8f0;
      color: #475569;
    }

    .notes-btn.has-notes {
      background: #fef3c7;
      color: #92400e;
    }

    .warmup-badge {
      border: 0;
      border-radius: 999px;
      padding: 7px 10px;
      font-size: 12px;
      font-weight: 850;
      background: #dcfce7;
      color: #166534;
      display: none;
    }

    .workout-item.warmup-view {
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    .workout-item.warmup-view .notes-btn,
    .workout-item.warmup-view .difficulty-select {
      display: none;
    }

    .workout-item.warmup-view .warmup-badge {
      display: inline-block;
    }

    

    .difficulty-select.easy { background: #dcfce7; color: #166534; }
    .difficulty-select.medium { background: #fef3c7; color: #92400e; }
    .difficulty-select.hard { background: #fee2e2; color: #991b1b; }

    .set-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
    }

    .set-pill {
      background: #f1f5f9;
      color: #334155;
      border: 0;
      border-radius: 14px;
      padding: 9px 8px;
      text-align: center;
      font-size: 13px;
      font-weight: 750;
      cursor: pointer;
      width: 100%;
    }

    body:not(.edit-mode) .set-pill { cursor: default; }

    .set-pill.editing {
      background: #bae6fd;
      color: #075985;
    }

    .set-editor {
      display: none;
      grid-column: 1 / -1;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      padding: 12px;
      gap: 12px;
    }

    .set-editor.open {
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .edit-group { display: grid; gap: 6px; }

    .edit-label {
      color: #64748b;
      font-size: 11px;
      font-weight: 850;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      text-align: center;
    }

    .stepper {
      display: grid;
      grid-template-columns: 30px 1fr 30px;
      align-items: center;
      gap: 5px;
    }

    .step-btn {
      border: 0;
      border-radius: 10px;
      height: 30px;
      background: #e0f2fe;
      color: #0369a1;
      font-weight: 900;
      cursor: pointer;
    }

    .edit-value {
      background: #ffffff;
      border-radius: 10px;
      padding: 7px 4px;
      text-align: center;
      font-size: 14px;
      font-weight: 850;
      color: #0f172a;
    }

    .notes-overlay {
      position: fixed;
      inset: 0;
      z-index: 20;
      display: none;
      background: rgba(2, 6, 23, 0.82);
      padding: 18px 16px;
    }

    .notes-overlay.open {
      display: grid;
      place-items: center;
    }

    .notes-panel {
      width: min(430px, 100%);
      height: min(78vh, 680px);
      background: #fff7ed;
      color: #0f172a;
      border-radius: 28px;
      box-shadow: 0 24px 80px rgba(0,0,0,0.5);
      display: grid;
      grid-template-rows: auto 1fr auto;
      overflow: hidden;
    }

    .notes-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 18px;
      background: #fef3c7;
      border-bottom: 1px solid #fde68a;
    }

    .notes-title {
      font-weight: 900;
      letter-spacing: -0.03em;
    }

    .close-notes {
      border: 0;
      border-radius: 999px;
      background: #ffffff;
      color: #92400e;
      font-weight: 900;
      padding: 8px 12px;
      cursor: pointer;
    }

    .notes-area {
      width: 100%;
      height: 100%;
      border: 0;
      resize: none;
      outline: none;
      padding: 18px;
      font: inherit;
      font-size: 16px;
      line-height: 1.5;
      background: #fffaf0;
      color: #0f172a;
    }

    .notes-footer {
      padding: 12px 18px;
      color: #92400e;
      background: #fffbeb;
      font-size: 12px;
      font-weight: 750;
    }

    .progress-block {
      background: var(--panel-soft);
      border-radius: 26px;
      padding: 18px;
      margin-top: 18px;
      border: 1px solid rgba(255,255,255,0.1);
    }

    .section-title {
      font-size: 18px;
      font-weight: 850;
      margin: 0 0 12px;
    }

    .mini-chart {
      height: 92px;
      display: flex;
      align-items: end;
      gap: 9px;
      padding-top: 14px;
    }

    .bar {
      flex: 1;
      background: linear-gradient(180deg, var(--accent), #2563eb);
      border-radius: 999px 999px 8px 8px;
      min-height: 24px;
    }

    .quick-actions {
      position: fixed;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: min(430px, 100%);
      padding: 12px 16px 18px;
      background: linear-gradient(180deg, rgba(15,23,42,0), rgba(15,23,42,0.95) 35%, #020617 100%);
    }

    .action-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items: center;
    }

    .primary-action {
      border: 0;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--accent-2), #14b8a6);
      color: #052e16;
      font-size: 16px;
      font-weight: 900;
      padding: 15px 18px;
      box-shadow: 0 12px 25px rgba(34, 197, 94, 0.28);
      cursor: pointer;
    }

    .secondary-action {
      border: 1px solid rgba(255,255,255,0.16);
      border-radius: 18px;
      background: rgba(255,255,255,0.11);
      color: var(--white);
      width: 54px;
      height: 52px;
      font-size: 24px;
      cursor: pointer;
    }

    @media (min-width: 820px) {
      body {
        display: grid;
        place-items: center;
        padding: 30px;
      }

      .app-shell {
        min-height: auto;
        border-radius: 38px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.45);
        background: rgba(15, 23, 42, 0.72);
      }

      .quick-actions {
        position: sticky;
        transform: none;
        left: auto;
        width: 100%;
        padding: 16px 0 0;
      }
    }
  </style>
</head>
<body>
  <main class="app-shell">
    <header class="top-bar">
      <div class="profile-chip">
        <div class="avatar">C</div>
        <div><div class="profile-name"><?= h($current['profileName']) ?></div></div>
      </div>
      <button class="icon-button" aria-label="Settings">☰</button>
    </header>

    <section class="hero">
      <div class="eyebrow">Workout Tracker</div>
      <h1>This week’s training</h1>
      <p>Log sets quickly, review recent sessions, and keep progress visible without making the app feel heavy.</p>
    </section>

    <section class="summary-grid" aria-label="Weekly summary">
      <div class="summary-card"><div class="summary-value">4</div><div class="summary-label">Workouts</div></div>
      <div class="summary-card"><div class="summary-value">18</div><div class="summary-label">Sets</div></div>
      <div class="summary-card"><div class="summary-value">+6%</div><div class="summary-label">Volume</div></div>
    </section>

    <section class="week-card">
      <div class="week-header" id="weekHeader">
        <div>
          <div class="week-title"><?= h($current['weekLabel']) ?></div>
          <div class="week-subtitle"><?= h($current['dayDateLabel']) ?></div>
          
        </div>
        <div class="week-nav">
          <button class="snapshot-toggle" id="snapshotToggle" aria-label="Show original workout snapshot">◐</button>
          <div class="original-big" id="originalBig">Original<br>Sheet</div>
          <button class="small-btn">‹</button>
          <button class="small-btn">›</button>
          <button class="lock-toggle" id="editLock" aria-label="Unlock editing">🔒</button>
        </div>
      </div>

      <div class="workout-list">
        <?php foreach ($current['exercises'] as $index => $exercise): ?>
        <article class="workout-item" data-index="<?= (int) $index ?>" data-exercise="<?= h($exercise['name']) ?>" data-main="<?= json_attr(sets_for_attr($exercise['mainSets'])) ?>" data-warmup="<?= json_attr(sets_for_attr($exercise['warmUpSets'])) ?>">
          <div class="workout-main">
            <div><div class="exercise-name"><?= h($exercise['name']) ?></div><div class="exercise-meta"><?= h($exercise['day']) ?> · <?= h($exercise['group']) ?></div></div>
            <div class="card-actions"><button class="notes-btn<?= trim((string) $exercise['notes']) !== '' ? ' has-notes' : '' ?>">Notes</button><select class="difficulty-select <?= h(strtolower($exercise['difficulty'])) ?>"><option<?= strtolower($exercise['difficulty']) === 'easy' ? ' selected' : '' ?>>Easy</option><option<?= strtolower($exercise['difficulty']) === 'medium' ? ' selected' : '' ?>>Medium</option><option<?= strtolower($exercise['difficulty']) === 'hard' ? ' selected' : '' ?>>Hard</option></select><span class="warmup-badge">Warm-up</span></div>
          </div>
          <div class="set-row">
            <?php foreach ($exercise['mainSets'] as $set): ?>
            <button class="set-pill" data-reps="<?= (int) $set['reps'] ?>" data-weight="<?= (int) $set['weight'] ?>"><?= (int) $set['reps'] ?> × <?= (int) $set['weight'] ?></button><div class="set-editor"></div>
            <?php endforeach; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <div class="week-footer" aria-label="Future controls area"><div class="save-status" id="saveStatus"></div></div>
    </section>

    <section class="progress-block">
      <h2 class="section-title">Bench trend</h2>
      <div class="mini-chart" aria-label="Simple progress chart">
        <div class="bar" style="height: 42%"></div>
        <div class="bar" style="height: 54%"></div>
        <div class="bar" style="height: 49%"></div>
        <div class="bar" style="height: 67%"></div>
        <div class="bar" style="height: 74%"></div>
        <div class="bar" style="height: 82%"></div>
      </div>
    </section>

    <div class="notes-overlay" id="notesOverlay" aria-hidden="true">
      <section class="notes-panel" role="dialog" aria-label="Workout notes">
        <div class="notes-header">
          <div class="notes-title" id="notesTitle">Notes</div>
          <button class="close-notes" id="closeNotes">Done</button>
        </div>
        <textarea class="notes-area" id="notesArea" placeholder="Type workout notes here..."></textarea>
        <div class="notes-footer">Notes are editable while the week is unlocked.</div>
      </section>
    </div>

    <nav class="quick-actions" aria-label="Quick actions">
      <div class="action-row">
        <button class="primary-action">+ Add workout</button>
        <button class="secondary-action" aria-label="Copy previous week">↻</button>
      </div>
    </nav>
  </main>

  <script>
    const workoutData = <?= json_encode($weekData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const editLock = document.getElementById('editLock');
    const snapshotToggle = document.getElementById('snapshotToggle');
    const weekHeader = document.getElementById('weekHeader');
    const notesOverlay = document.getElementById('notesOverlay');
    const notesTitle = document.getElementById('notesTitle');
    const notesArea = document.getElementById('notesArea');
    const closeNotes = document.getElementById('closeNotes');
    const saveStatus = document.getElementById('saveStatus');
    let editMode = false;
    let activeNotesButton = null;
    let saveTimer = null;

    function setSaveStatus(message) {
      saveStatus.textContent = message;
    }

    function scheduleSave() {
      if (document.body.classList.contains('snapshot-mode')) return;
      clearTimeout(saveTimer);
      setSaveStatus('Saving...');
      saveTimer = setTimeout(saveCurrentData, 1400);
    }

    function saveCurrentData() {
      fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current: workoutData.current })
      })
        .then(response => {
          if (!response.ok) throw new Error('Save error');
          return response.json();
        })
        .then(result => {
          if (!result.ok) throw new Error('Save error');
          setSaveStatus('Saved');
        })
        .catch(() => setSaveStatus('Save error'));
    }

    function toPairs(sets) {
      return sets.map(set => [Number(set.reps), Number(set.weight)]);
    }

    function getExerciseData(item, source) {
      return workoutData[source].exercises[Number(item.dataset.index)];
    }

    function updateItemDatasets(item, source) {
      const exercise = getExerciseData(item, source);
      item.dataset.main = JSON.stringify(toPairs(exercise.mainSets));
      item.dataset.warmup = JSON.stringify(toPairs(exercise.warmUpSets));
    }

    function updateDifficultyClass(select) {
      select.classList.remove('easy', 'medium', 'hard');
      select.classList.add(select.value.toLowerCase());
    }

    function renderSetRow(item, sets) {
      const row = item.querySelector('.set-row');
      row.innerHTML = sets.map(([reps, weight]) => `
        <button class="set-pill" data-reps="${reps}" data-weight="${weight}">${reps} × ${weight}</button><div class="set-editor"></div>
      `).join('');
    }

    function showWorkoutView(item, view) {
      const source = document.body.classList.contains('snapshot-mode') ? 'original' : 'current';
      updateItemDatasets(item, source);
      const sets = JSON.parse(view === 'warmup' ? item.dataset.warmup : item.dataset.main);
      item.classList.toggle('warmup-view', view === 'warmup');
      item.dataset.view = view;
      renderSetRow(item, sets);
    }

    document.querySelectorAll('.workout-item').forEach(item => {
      item.dataset.view = 'main';
    });

    function renderWorkoutList(source) {
      document.querySelectorAll('.workout-item').forEach(item => {
        const exercise = getExerciseData(item, source);
        item.dataset.exercise = exercise.name;
        item.querySelector('.exercise-name').textContent = exercise.name;
        item.querySelector('.exercise-meta').textContent = `${exercise.day} · ${exercise.group}`;
        const notesButton = item.querySelector('.notes-btn');
        notesButton.classList.toggle('has-notes', exercise.notes.trim().length > 0);
        const difficultySelect = item.querySelector('.difficulty-select');
        difficultySelect.value = exercise.difficulty;
        updateDifficultyClass(difficultySelect);
        item.classList.remove('warmup-view');
        item.dataset.view = 'main';
        updateItemDatasets(item, source);
        renderSetRow(item, toPairs(exercise.mainSets));
      });
    }

    function renderEditor(editor, pill) {
      editor.innerHTML = `
        <div class="edit-group">
          <div class="edit-label">Reps</div>
          <div class="stepper">
            <button class="step-btn" data-target="reps" data-step="-1">−</button>
            <div class="edit-value reps">${pill.dataset.reps}</div>
            <button class="step-btn" data-target="reps" data-step="1">+</button>
          </div>
        </div>
        <div class="edit-group">
          <div class="edit-label">Pounds</div>
          <div class="stepper">
            <button class="step-btn" data-target="weight" data-step="-5">−</button>
            <div class="edit-value weight">${pill.dataset.weight}</div>
            <button class="step-btn" data-target="weight" data-step="5">+</button>
          </div>
        </div>
      `;
    }

    document.querySelectorAll('.difficulty-select').forEach(select => {
      select.addEventListener('change', () => {
        updateDifficultyClass(select);
        if (document.body.classList.contains('snapshot-mode')) return;
        const item = select.closest('.workout-item');
        getExerciseData(item, 'current').difficulty = select.value;
        scheduleSave();
      });
      updateDifficultyClass(select);
    });

    snapshotToggle.addEventListener('click', () => {
      const enteringSnapshot = !document.body.classList.contains('snapshot-mode');

      if (enteringSnapshot && editMode) {
        editLock.click();
      }

      document.body.classList.toggle('snapshot-mode', enteringSnapshot);
      snapshotToggle.textContent = enteringSnapshot ? '◑' : '◐';
      snapshotToggle.setAttribute('aria-label', enteringSnapshot ? 'Exit original workout snapshot' : 'Show original workout snapshot');

      renderWorkoutList(enteringSnapshot ? 'original' : 'current');
    });

    const originalBig = document.getElementById('originalBig');
    originalBig.addEventListener('click', () => {
      if (!document.body.classList.contains('snapshot-mode')) return;
      document.body.classList.remove('snapshot-mode');
      snapshotToggle.textContent = '◐';
      snapshotToggle.setAttribute('aria-label', 'Show original workout snapshot');
      renderWorkoutList('current');
    });


    editLock.addEventListener('click', () => {
      if (document.body.classList.contains('snapshot-mode')) return;
      editMode = !editMode;
      document.body.classList.toggle('edit-mode', editMode);
      weekHeader.classList.toggle('unlocked', editMode);
      editLock.classList.toggle('unlocked', editMode);
      editLock.textContent = editMode ? '🔓' : '🔒';
      editLock.setAttribute('aria-label', editMode ? 'Lock editing' : 'Unlock editing');

      if (!editMode) {
        document.querySelectorAll('.set-editor.open').forEach(editor => editor.classList.remove('open'));
        document.querySelectorAll('.set-pill.editing').forEach(pill => pill.classList.remove('editing'));
        closeNotesPanel();
      }
    });

    function closeNotesPanel() {
      if (activeNotesButton) {
        const item = activeNotesButton.closest('.workout-item');
        const exercise = getExerciseData(item, 'current');
        if (editMode && !document.body.classList.contains('snapshot-mode')) {
          exercise.notes = notesArea.value.trim();
          activeNotesButton.classList.toggle('has-notes', exercise.notes.length > 0);
          scheduleSave();
        }
      }
      notesOverlay.classList.remove('open');
      notesOverlay.setAttribute('aria-hidden', 'true');
      activeNotesButton = null;
    }

    document.querySelectorAll('.notes-btn').forEach(button => {
      button.addEventListener('click', () => {
        const item = button.closest('.workout-item');
        const source = document.body.classList.contains('snapshot-mode') ? 'original' : 'current';
        const exercise = getExerciseData(item, source);
        if (!editMode && !button.classList.contains('has-notes')) return;
        activeNotesButton = button;
        notesTitle.textContent = `${exercise.name} Notes`;
        notesArea.value = exercise.notes || '';
        notesArea.readOnly = !editMode || source === 'original';
        notesArea.placeholder = editMode && source !== 'original' ? 'Type workout notes here...' : 'No notes yet.';
        notesOverlay.classList.add('open');
        notesOverlay.setAttribute('aria-hidden', 'false');
        if (editMode && source !== 'original') notesArea.focus();
      });
    });

    closeNotes.addEventListener('click', closeNotesPanel);

    notesArea.addEventListener('input', () => {
      if (!editMode || document.body.classList.contains('snapshot-mode') || !activeNotesButton) return;
      const item = activeNotesButton.closest('.workout-item');
      const exercise = getExerciseData(item, 'current');
      exercise.notes = notesArea.value.trim();
      activeNotesButton.classList.toggle('has-notes', exercise.notes.length > 0);
      scheduleSave();
    });

    document.querySelectorAll('.workout-item').forEach(item => {
      let startX = 0;
      let startY = 0;

      item.addEventListener('touchstart', event => {
        if (editMode || document.body.classList.contains('snapshot-mode')) return;
        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
      }, { passive: true });

      item.addEventListener('touchend', event => {
        if (editMode || document.body.classList.contains('snapshot-mode')) return;
        const endX = event.changedTouches[0].clientX;
        const endY = event.changedTouches[0].clientY;
        const diffX = endX - startX;
        const diffY = endY - startY;

        if (Math.abs(diffX) < 45 || Math.abs(diffX) < Math.abs(diffY)) return;

        const nextView = item.dataset.view === 'warmup' ? 'main' : 'warmup';
        showWorkoutView(item, nextView);
      });
    });

    document.addEventListener('click', event => {
      const pill = event.target.closest('.set-pill');
      if (!pill) return;
      if (!editMode || document.body.classList.contains('snapshot-mode')) return;

      const editor = pill.nextElementSibling;
      const row = pill.closest('.set-row');
      renderEditor(editor, pill);

      row.querySelectorAll('.set-editor.open').forEach(openEditor => {
        if (openEditor !== editor) {
          openEditor.classList.remove('open');
          openEditor.previousElementSibling.classList.remove('editing');
        }
      });

      editor.classList.toggle('open');
      pill.classList.toggle('editing', editor.classList.contains('open'));
    });

    document.addEventListener('click', event => {
      const stepButton = event.target.closest('.step-btn');
      if (!stepButton) return;
      if (document.body.classList.contains('snapshot-mode')) return;
      event.stopPropagation();

      const editor = stepButton.closest('.set-editor');
      const pill = editor.previousElementSibling;
      const target = stepButton.dataset.target;
      const step = Number(stepButton.dataset.step);
      const valueEl = editor.querySelector(`.${target}`);
      const minimum = target === 'reps' ? 1 : 0;
      const nextValue = Math.max(minimum, Number(valueEl.textContent) + step);

      valueEl.textContent = nextValue;
      pill.dataset[target] = String(nextValue);
      pill.textContent = `${pill.dataset.reps} × ${pill.dataset.weight}`;

      const item = pill.closest('.workout-item');
      const setIndex = Array.from(item.querySelectorAll('.set-pill')).indexOf(pill);
      const exercise = getExerciseData(item, 'current');
      const sets = item.dataset.view === 'warmup' ? exercise.warmUpSets : exercise.mainSets;
      sets[setIndex][target] = nextValue;
      updateItemDatasets(item, 'current');
      scheduleSave();
    });
  </script>
</body>
</html>
