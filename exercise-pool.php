<?php
$profiles = [
  'chris' => 'Chris',
  'dustin' => 'Dustin'
];
$requestedProfile = isset($_GET['profile']) ? strtolower((string) $_GET['profile']) : 'chris';
$activeProfile = array_key_exists($requestedProfile, $profiles) ? $requestedProfile : 'chris';
$poolFile = __DIR__ . '/data/exercise-pool.json';
$cssPath = 'assets/css/app.css';
$cssFile = __DIR__ . '/' . $cssPath;
$cssVersion = file_exists($cssFile) ? filemtime($cssFile) : time();

if (!file_exists($poolFile)) {
  file_put_contents($poolFile, json_encode(default_pool_groups(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
}

$poolData = normalize_pool_data(json_decode(file_get_contents($poolFile), true));

function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function default_group_names() {
  return ['Chest / Push', 'Back / Pull', 'Legs', 'Arms', 'Shoulders', 'Core', 'General'];
}

function default_pool_groups() {
  return [
    'groups' => array_map(function ($groupName) {
      return ['name' => $groupName, 'exercises' => []];
    }, default_group_names())
  ];
}

function normalize_pool_data($rawData) {
  $groups = [];
  foreach (default_group_names() as $groupName) {
    $groups[$groupName] = ['name' => $groupName, 'exercises' => []];
  }

  if (is_array($rawData) && isset($rawData['groups']) && is_array($rawData['groups'])) {
    foreach ($rawData['groups'] as $group) {
      if (!is_array($group)) continue;
      $groupName = isset($group['name']) && trim((string) $group['name']) !== '' ? trim((string) $group['name']) : 'General';
      if (!isset($groups[$groupName])) {
        $groups[$groupName] = ['name' => $groupName, 'exercises' => []];
      }
      $groups[$groupName]['exercises'] = normalize_group_exercises(isset($group['exercises']) && is_array($group['exercises']) ? $group['exercises'] : []);
    }
  } elseif (is_array($rawData)) {
    foreach ($rawData as $exercise) {
      if (!is_array($exercise)) continue;
      $groupName = isset($exercise['group']) && trim((string) $exercise['group']) !== '' ? trim((string) $exercise['group']) : 'General';
      if (!isset($groups[$groupName])) {
        $groups[$groupName] = ['name' => $groupName, 'exercises' => []];
      }
      $groups[$groupName]['exercises'][] = normalize_pool_exercise($exercise);
    }
  }

  return ['groups' => array_values($groups)];
}

function normalize_group_exercises($exercises) {
  $clean = [];
  foreach ($exercises as $exercise) {
    if (is_array($exercise)) {
      $clean[] = normalize_pool_exercise($exercise);
    }
  }
  return $clean;
}

function normalize_pool_exercise($exercise) {
  $name = isset($exercise['name']) && trim((string) $exercise['name']) !== '' ? trim((string) $exercise['name']) : 'Exercise';
  return [
    'id' => isset($exercise['id']) && trim((string) $exercise['id']) !== '' ? trim((string) $exercise['id']) : slug_id($name),
    'name' => $name,
    'mainSets' => isset($exercise['mainSets']) && is_array($exercise['mainSets']) ? $exercise['mainSets'] : [],
    'warmUpSets' => isset($exercise['warmUpSets']) && is_array($exercise['warmUpSets']) ? $exercise['warmUpSets'] : []
  ];
}

function slug_id($value) {
  $id = strtolower(trim((string) $value));
  $id = preg_replace('/[^a-z0-9]+/', '-', $id);
  return trim($id, '-') ?: 'exercise';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Exercise Pool</title>
  <link rel="stylesheet" href="<?= h($cssPath) ?>?v=<?= (int) $cssVersion ?>" />
</head>
<body class="pool-page">
  <main class="pool-shell">
    <header class="pool-hero">
      <div>
        <div class="pool-eyebrow">Workout Tracker</div>
        <h1>Exercise Pool</h1>
        <p>Manage your master exercise library by group.</p>
      </div>
      <a class="pool-back" href="index.php?profile=<?= h($activeProfile) ?>">Back</a>
    </header>

    <section class="pool-control-panel" aria-label="Exercise pool controls">
      <div class="pool-view-heading">
        <button class="pool-detail-back" id="groupBackButton" type="button">Back to Groups</button>
        <div>
          <div class="pool-view-title" id="poolViewTitle">Groups</div>
          <div class="pool-view-subtitle" id="poolViewSubtitle"></div>
        </div>
      </div>
      <div class="pool-control-actions">
        <button class="pool-action-btn add" id="addGroupButton" type="button">Add Group</button>
        <button class="pool-action-btn add" id="addExerciseButton" type="button">Add Exercise</button>
        <div class="pool-save-cluster">
          <button class="pool-action-btn save-pool-button" id="savePoolButton" type="button">Save</button>
          <div class="pool-save-meta">
            <span class="pool-status" id="poolStatus">Ready</span>
            <span class="pool-last-saved" id="poolLastSaved">Not saved yet</span>
          </div>
        </div>
      </div>
      <form class="pool-add-group-form" id="addGroupForm">
        <input type="text" id="newGroupName" placeholder="New group name" maxlength="120" />
        <button class="pool-action-btn add" type="submit">Add</button>
      </form>
    </section>

    <section class="pool-groups" id="groupList" aria-label="Exercise groups"></section>
    <section class="pool-exercises" id="exerciseList" aria-label="Group exercises"></section>
  </main>

  <script>
    const poolData = <?= json_encode($poolData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const groupList = document.getElementById('groupList');
    const exerciseList = document.getElementById('exerciseList');
    const groupBackButton = document.getElementById('groupBackButton');
    const addGroupButton = document.getElementById('addGroupButton');
    const addExerciseButton = document.getElementById('addExerciseButton');
    const addGroupForm = document.getElementById('addGroupForm');
    const newGroupName = document.getElementById('newGroupName');
    const poolViewTitle = document.getElementById('poolViewTitle');
    const poolViewSubtitle = document.getElementById('poolViewSubtitle');
    const poolStatus = document.getElementById('poolStatus');
    const poolLastSaved = document.getElementById('poolLastSaved');
    const savePoolButton = document.getElementById('savePoolButton');
    const lastSavedStorageKey = 'exercisePoolLastSavedAt';
    let selectedGroupIndex = null;
    let expandedExerciseIndex = null;
    let poolIsDirty = false;
    let poolChangeVersion = 0;
    let saveCompleteTimer = null;

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char]));
    }

    function slugify(value) {
      return String(value || 'exercise').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80) || 'exercise';
    }

    function allExercises() {
      return poolData.groups.flatMap(group => Array.isArray(group.exercises) ? group.exercises : []);
    }

    function uniqueExerciseId(name) {
      const used = new Set(allExercises().map(exercise => exercise.id));
      const base = slugify(name);
      let id = base;
      let suffix = 2;
      while (used.has(id)) {
        id = `${base}-${suffix}`;
        suffix += 1;
      }
      return id;
    }

    function setSaveButtonState(state) {
      savePoolButton.classList.remove('has-unsaved-changes', 'is-saving', 'is-saved', 'has-save-error');

      if (state === 'dirty') {
        savePoolButton.classList.add('has-unsaved-changes');
        savePoolButton.textContent = 'Save Changes';
        return;
      }

      if (state === 'saving') {
        savePoolButton.classList.add('is-saving');
        savePoolButton.textContent = 'Saving...';
        return;
      }

      if (state === 'saved') {
        savePoolButton.classList.add('is-saved');
        savePoolButton.textContent = 'Saved';
        return;
      }

      if (state === 'error') {
        savePoolButton.classList.add('has-unsaved-changes', 'has-save-error');
        savePoolButton.textContent = 'Save Changes';
        return;
      }

      savePoolButton.textContent = 'Save';
    }

    function setDirty(isDirty) {
      if (isDirty) {
        poolChangeVersion += 1;
        clearTimeout(saveCompleteTimer);
        setSaveButtonState('dirty');
      }
      poolIsDirty = isDirty;
      if (poolIsDirty) {
        poolStatus.textContent = 'Unsaved changes';
      } else if (poolStatus.textContent === 'Unsaved changes') {
        poolStatus.textContent = 'Ready';
        setSaveButtonState('clean');
      }
    }

    function formatSavedTime(timestamp) {
      const savedAt = new Date(Number(timestamp));
      if (Number.isNaN(savedAt.getTime())) return '';
      return savedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function updateLastSaved(timestamp) {
      const formattedTime = formatSavedTime(timestamp);
      poolLastSaved.textContent = formattedTime ? `Saved ${formattedTime}` : 'Not saved yet';
    }

    function rememberLastSaved() {
      const timestamp = Date.now();
      localStorage.setItem(lastSavedStorageKey, String(timestamp));
      updateLastSaved(timestamp);
    }

    function setRowsHtml(sets, exerciseIndex, type) {
      return sets.map((set, setIndex) => `
        <div class="pool-set-row">
          <div class="pool-field">
            <label>Reps</label>
            <input type="number" min="1" inputmode="numeric" data-field="reps" data-exercise-index="${exerciseIndex}" data-set-type="${type}" data-set-index="${setIndex}" value="${Number(set.reps) || 1}">
          </div>
          <div class="pool-field">
            <label>Weight</label>
            <input type="number" min="0" inputmode="numeric" data-field="weight" data-exercise-index="${exerciseIndex}" data-set-type="${type}" data-set-index="${setIndex}" value="${Number(set.weight) || 0}">
          </div>
          <button class="pool-set-delete" type="button" data-action="delete-set" data-exercise-index="${exerciseIndex}" data-set-type="${type}" data-set-index="${setIndex}" aria-label="Delete set">×</button>
        </div>
      `).join('');
    }

    function render() {
      const isDetail = selectedGroupIndex !== null;
      const selectedGroup = isDetail ? poolData.groups[selectedGroupIndex] : null;
      document.body.classList.toggle('pool-detail-mode', isDetail);
      groupBackButton.style.display = isDetail ? 'inline-block' : 'none';
      addGroupButton.style.display = isDetail ? 'none' : 'inline-block';
      addExerciseButton.style.display = isDetail ? 'inline-block' : 'none';
      addGroupForm.classList.remove('open');

      poolViewTitle.textContent = isDetail ? selectedGroup.name : 'Groups';
      poolViewSubtitle.textContent = isDetail
        ? `${selectedGroup.exercises.length} ${selectedGroup.exercises.length === 1 ? 'exercise' : 'exercises'}`
        : `${poolData.groups.length} groups`;

      renderGroups();
      renderExercises();
    }

    function renderGroups() {
      groupList.style.display = selectedGroupIndex === null ? 'grid' : 'none';
      if (selectedGroupIndex !== null) return;

      groupList.innerHTML = poolData.groups.map((group, groupIndex) => `
        <button class="pool-group-pill" type="button" data-group-index="${groupIndex}">
          <span>${escapeHtml(group.name)}</span>
          <small>${group.exercises.length}</small>
        </button>
      `).join('');
    }

    function renderExercises() {
      exerciseList.style.display = selectedGroupIndex === null ? 'none' : 'grid';
      if (selectedGroupIndex === null) return;
      const group = poolData.groups[selectedGroupIndex];

      if (!group.exercises.length) {
        exerciseList.innerHTML = '<div class="pool-empty">No exercises in this group yet.</div>';
        return;
      }

      exerciseList.innerHTML = group.exercises.map((exercise, exerciseIndex) => {
        const isExpanded = expandedExerciseIndex === exerciseIndex;
        return `
          <article class="pool-card${isExpanded ? ' expanded' : ''}" data-exercise-index="${exerciseIndex}">
            <button class="pool-summary" type="button" data-action="toggle-exercise" data-exercise-index="${exerciseIndex}" aria-expanded="${isExpanded ? 'true' : 'false'}">
              <span class="pool-summary-cell">
                <span class="pool-summary-label">Group</span>
                <span class="pool-summary-value">${escapeHtml(group.name)}</span>
              </span>
              <span class="pool-summary-cell">
                <span class="pool-summary-label">Name</span>
                <span class="pool-summary-value">${escapeHtml(exercise.name || 'New Exercise')}</span>
              </span>
            </button>
            ${isExpanded ? `
              <div class="pool-card-body">
                <div class="pool-form-grid">
                  <div class="pool-field">
                    <label>Name</label>
                    <input type="text" data-field="name" data-exercise-index="${exerciseIndex}" value="${escapeHtml(exercise.name)}">
                  </div>
                  <div class="pool-field">
                    <label>Group</label>
                    <input type="text" value="${escapeHtml(group.name)}" readonly>
                  </div>
                </div>
                <div class="pool-set-section">
                  <div class="pool-set-heading">
                    <span>Default Main Sets</span>
                    <button class="pool-add-set" type="button" data-action="add-set" data-exercise-index="${exerciseIndex}" data-set-type="mainSets">Add Set</button>
                  </div>
                  ${setRowsHtml(exercise.mainSets || [], exerciseIndex, 'mainSets')}
                </div>
                <div class="pool-set-section">
                  <div class="pool-set-heading">
                    <span>Default Warm-up Sets</span>
                    <button class="pool-add-set" type="button" data-action="add-set" data-exercise-index="${exerciseIndex}" data-set-type="warmUpSets">Add Set</button>
                  </div>
                  ${setRowsHtml(exercise.warmUpSets || [], exerciseIndex, 'warmUpSets')}
                </div>
                <button class="pool-delete" type="button" data-action="delete-exercise" data-exercise-index="${exerciseIndex}">Delete Exercise</button>
              </div>
            ` : ''}
          </article>
        `;
      }).join('');
    }

    function savePool() {
      poolStatus.textContent = 'Saving...';
      savePoolButton.disabled = true;
      setSaveButtonState('saving');
      const saveVersion = poolChangeVersion;

      poolData.groups.forEach(group => {
        group.name = String(group.name || '').trim() || 'General';
        if (!Array.isArray(group.exercises)) group.exercises = [];
        group.exercises.forEach(exercise => {
          exercise.name = String(exercise.name || '').trim() || 'New Exercise';
          if (!exercise.id) exercise.id = uniqueExerciseId(exercise.name);
          if (!Array.isArray(exercise.mainSets)) exercise.mainSets = [];
          if (!Array.isArray(exercise.warmUpSets)) exercise.warmUpSets = [];
        });
      });

      fetch('save.php?mode=exercise-pool', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ groups: poolData.groups })
      })
        .then(response => {
          if (!response.ok) throw new Error('Save error');
          return response.json();
        })
        .then(result => {
          if (!result.ok) throw new Error('Save error');
          poolStatus.textContent = 'Saved';
          rememberLastSaved();
          savePoolButton.disabled = false;
          setSaveButtonState('saved');
          saveCompleteTimer = setTimeout(() => {
            if (saveVersion === poolChangeVersion) {
              setDirty(false);
            }
            if (!poolIsDirty) {
              setSaveButtonState('clean');
            }
          }, 550);
          render();
        })
        .catch(() => {
          poolStatus.textContent = 'Save error';
          savePoolButton.disabled = false;
          setSaveButtonState('error');
        });
    }

    groupList.addEventListener('click', event => {
      const button = event.target.closest('.pool-group-pill');
      if (!button) return;
      selectedGroupIndex = Number(button.dataset.groupIndex);
      expandedExerciseIndex = null;
      render();
    });

    groupBackButton.addEventListener('click', () => {
      selectedGroupIndex = null;
      expandedExerciseIndex = null;
      render();
    });

    addGroupButton.addEventListener('click', () => {
      addGroupForm.classList.toggle('open');
      if (addGroupForm.classList.contains('open')) {
        newGroupName.focus();
      }
    });

    addGroupForm.addEventListener('submit', event => {
      event.preventDefault();
      const groupName = newGroupName.value.trim();
      if (!groupName) return;
      const existingIndex = poolData.groups.findIndex(group => group.name.toLowerCase() === groupName.toLowerCase());
      if (existingIndex >= 0) {
        selectedGroupIndex = existingIndex;
        newGroupName.value = '';
        render();
        return;
      }
      poolData.groups.push({ name: groupName, exercises: [] });
      selectedGroupIndex = poolData.groups.length - 1;
      expandedExerciseIndex = null;
      newGroupName.value = '';
      setDirty(true);
      render();
    });

    addExerciseButton.addEventListener('click', () => {
      if (selectedGroupIndex === null) return;
      const group = poolData.groups[selectedGroupIndex];
      group.exercises.push({
        id: uniqueExerciseId('New Exercise'),
        name: 'New Exercise',
        mainSets: [{ reps: 10, weight: 0 }],
        warmUpSets: []
      });
      expandedExerciseIndex = group.exercises.length - 1;
      setDirty(true);
      render();
      const card = exerciseList.querySelector(`[data-exercise-index="${expandedExerciseIndex}"]`);
      if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const input = card.querySelector('input[data-field="name"]');
        if (input) {
          input.focus();
          input.select();
        }
      }
    });

    exerciseList.addEventListener('input', event => {
      const input = event.target.closest('input');
      if (!input || selectedGroupIndex === null || input.readOnly) return;
      const group = poolData.groups[selectedGroupIndex];
      const exercise = group.exercises[Number(input.dataset.exerciseIndex)];
      if (!exercise) return;
      const field = input.dataset.field;

      if (field === 'name') {
        exercise.name = input.value;
        setDirty(true);
        return;
      }

      const sets = exercise[input.dataset.setType];
      if (!sets || !sets[Number(input.dataset.setIndex)]) return;
      const minimum = field === 'reps' ? 1 : 0;
      sets[Number(input.dataset.setIndex)][field] = Math.max(minimum, Number(input.value) || minimum);
      setDirty(true);
    });

    exerciseList.addEventListener('click', event => {
      const button = event.target.closest('button[data-action]');
      if (!button || selectedGroupIndex === null) return;
      const group = poolData.groups[selectedGroupIndex];
      const exerciseIndex = Number(button.dataset.exerciseIndex);
      const action = button.dataset.action;

      if (action === 'toggle-exercise') {
        expandedExerciseIndex = expandedExerciseIndex === exerciseIndex ? null : exerciseIndex;
        render();
        return;
      }

      if (action === 'delete-exercise') {
        group.exercises.splice(exerciseIndex, 1);
        expandedExerciseIndex = null;
        setDirty(true);
        render();
        return;
      }

      const exercise = group.exercises[exerciseIndex];
      if (!exercise) return;
      const setType = button.dataset.setType;
      if (!Array.isArray(exercise[setType])) exercise[setType] = [];

      if (action === 'add-set') {
        exercise[setType].push({ reps: 10, weight: 0 });
        setDirty(true);
        render();
      }

      if (action === 'delete-set') {
        exercise[setType].splice(Number(button.dataset.setIndex), 1);
        setDirty(true);
        render();
      }
    });

    savePoolButton.addEventListener('click', savePool);
    updateLastSaved(localStorage.getItem(lastSavedStorageKey));
    setSaveButtonState('clean');
    render();
  </script>
</body>
</html>
