<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'POST required']);
  exit;
}

$profiles = [
  'chris' => 'Chris',
  'dustin' => 'Dustin'
];
$requestedProfile = isset($_GET['profile']) ? strtolower((string) $_GET['profile']) : 'chris';
$activeProfile = array_key_exists($requestedProfile, $profiles) ? $requestedProfile : 'chris';
$input = json_decode(file_get_contents('php://input'), true);
$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'log';

if ($mode === 'exercise-pool') {
  if (!is_array($input) || ((!isset($input['groups']) || !is_array($input['groups'])) && (!isset($input['exercises']) || !is_array($input['exercises'])))) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing exercise pool data']);
    exit;
  }

  $pool = isset($input['groups']) && is_array($input['groups'])
    ? sanitize_exercise_pool_groups($input['groups'], $error)
    : group_flat_exercise_pool(sanitize_exercise_pool($input['exercises'], $error));
  if ($pool === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
  }

  $poolFile = __DIR__ . '/data/exercise-pool.json';
  $json = json_encode($pool, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false || file_put_contents($poolFile, $json . PHP_EOL, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not write exercise pool data']);
    exit;
  }

  echo json_encode(['ok' => true]);
  exit;
}

$dataFile = __DIR__ . '/data/profiles/' . $activeProfile . '/current-week.json';

if (!file_exists($dataFile)) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'Profile workout data not found']);
  exit;
}

$stored = json_decode(file_get_contents($dataFile), true);

// Backend wiring: accept only editable current-state fields and preserve original.
if (!is_array($stored) || !isset($stored['original'], $stored['current'])) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Stored workout data is invalid']);
  exit;
}

if ($mode === 'program') {
  if (!is_array($input) || !isset($input['program']) || !is_array($input['program'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing program data']);
    exit;
  }

  $updated = sanitize_program($input['program'], $stored, $profiles[$activeProfile], $error);
  if ($updated === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
  }

  $stored = $updated;
} else {
  if (!is_array($input) || !isset($input['current']) || !is_array($input['current'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing current workout data']);
    exit;
  }

  $current = sanitize_current($input['current'], $stored['current'], $error);
  if ($current === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
  }

  $stored['current'] = $current;
}

$json = json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents($dataFile, $json . PHP_EOL, LOCK_EX) === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Could not write workout data']);
  exit;
}

echo json_encode(['ok' => true]);

function sanitize_current($incoming, $existing, &$error) {
  $error = '';
  $clean = $existing;

  if (!isset($incoming['exercises']) || !is_array($incoming['exercises']) || count($incoming['exercises']) !== count($existing['exercises'])) {
    $error = 'Workout exercise data is missing or incomplete';
    return null;
  }

  foreach ($clean['exercises'] as $index => $exercise) {
    if (!isset($incoming['exercises'][$index]) || !is_array($incoming['exercises'][$index])) {
      $error = 'Workout exercise data is missing or incomplete';
      return null;
    }

    $incomingExercise = $incoming['exercises'][$index];
    $mainSets = sanitize_sets(
      isset($incomingExercise['mainSets']) ? $incomingExercise['mainSets'] : [],
      $exercise['mainSets'],
      $error
    );
    if ($mainSets === null) return null;

    $warmUpSets = sanitize_sets(
      isset($incomingExercise['warmUpSets']) ? $incomingExercise['warmUpSets'] : [],
      $exercise['warmUpSets'],
      $error
    );
    if ($warmUpSets === null) return null;

    $clean['exercises'][$index]['mainSets'] = $mainSets;
    $clean['exercises'][$index]['warmUpSets'] = $warmUpSets;

    if (isset($incomingExercise['difficulty']) && in_array($incomingExercise['difficulty'], ['Easy', 'Medium', 'Hard'], true)) {
      $clean['exercises'][$index]['difficulty'] = $incomingExercise['difficulty'];
    } else {
      $error = 'Workout difficulty data is invalid';
      return null;
    }

    if (isset($incomingExercise['notes'])) {
      $clean['exercises'][$index]['notes'] = substr(trim((string) $incomingExercise['notes']), 0, 5000);
    }
  }

  return $clean;
}

function sanitize_sets($incomingSets, $existingSets, &$error) {
  $cleanSets = $existingSets;

  if (!is_array($incomingSets) || count($incomingSets) !== count($existingSets)) {
    $error = 'Workout set data is missing or incomplete';
    return null;
  }

  foreach ($cleanSets as $index => $set) {
    if (!isset($incomingSets[$index]) || !is_array($incomingSets[$index])) {
      $error = 'Workout set data is missing or incomplete';
      return null;
    }

    $cleanSets[$index]['reps'] = max(1, (int) ($incomingSets[$index]['reps'] ?? $set['reps']));
    $cleanSets[$index]['weight'] = max(0, (int) ($incomingSets[$index]['weight'] ?? $set['weight']));
  }

  return $cleanSets;
}

function sanitize_program($program, $stored, $profileName, &$error) {
  $error = '';
  if (!isset($program['exercises']) || !is_array($program['exercises'])) {
    $error = 'Program exercise data is missing';
    return null;
  }

  $clean = $stored;
  $originalExercises = isset($stored['original']['exercises']) && is_array($stored['original']['exercises']) ? $stored['original']['exercises'] : [];
  $currentExercises = isset($stored['current']['exercises']) && is_array($stored['current']['exercises']) ? $stored['current']['exercises'] : [];
  $newOriginalExercises = [];
  $newCurrentExercises = [];

  foreach ($program['exercises'] as $index => $exercise) {
    if (!is_array($exercise)) {
      $error = 'Program exercise data is invalid';
      return null;
    }

    $mainSets = sanitize_program_sets(isset($exercise['mainSets']) ? $exercise['mainSets'] : [], $error);
    if ($mainSets === null) return null;
    $warmUpSets = sanitize_program_sets(isset($exercise['warmUpSets']) ? $exercise['warmUpSets'] : [], $error);
    if ($warmUpSets === null) return null;

    $originalExercise = isset($originalExercises[$index]) && is_array($originalExercises[$index]) ? $originalExercises[$index] : [];
    $currentExercise = isset($currentExercises[$index]) && is_array($currentExercises[$index]) ? $currentExercises[$index] : [];

    $newOriginal = [
      'poolId' => clean_text(isset($exercise['poolId']) ? $exercise['poolId'] : '', 120),
      'name' => clean_text(isset($exercise['name']) ? $exercise['name'] : '', 120),
      'day' => clean_text(isset($exercise['day']) ? $exercise['day'] : '', 80),
      'group' => clean_text(isset($exercise['group']) ? $exercise['group'] : '', 120),
      'mainSets' => $mainSets,
      'warmUpSets' => $warmUpSets,
      'difficulty' => 'Medium',
      'notes' => ''
    ];

    if ($newOriginal['name'] === '') {
      $error = 'Exercise name is required';
      return null;
    }

    $newCurrent = $newOriginal;
    $newCurrent['difficulty'] = isset($currentExercise['difficulty']) && in_array($currentExercise['difficulty'], ['Easy', 'Medium', 'Hard'], true)
      ? $currentExercise['difficulty']
      : 'Medium';
    $newCurrent['notes'] = isset($currentExercise['notes']) ? substr(trim((string) $currentExercise['notes']), 0, 5000) : '';
    $newCurrent['mainSets'] = merge_program_sets($mainSets, isset($originalExercise['mainSets']) ? $originalExercise['mainSets'] : [], isset($currentExercise['mainSets']) ? $currentExercise['mainSets'] : []);
    $newCurrent['warmUpSets'] = merge_program_sets($warmUpSets, isset($originalExercise['warmUpSets']) ? $originalExercise['warmUpSets'] : [], isset($currentExercise['warmUpSets']) ? $currentExercise['warmUpSets'] : []);

    $newOriginalExercises[] = $newOriginal;
    $newCurrentExercises[] = $newCurrent;
  }

  $clean['original']['profileName'] = $profileName;
  $clean['current']['profileName'] = $profileName;
  $clean['original']['exercises'] = $newOriginalExercises;
  $clean['current']['exercises'] = $newCurrentExercises;

  return $clean;
}

function sanitize_program_sets($sets, &$error) {
  if (!is_array($sets)) {
    $error = 'Program set data is invalid';
    return null;
  }

  $cleanSets = [];
  foreach ($sets as $set) {
    if (!is_array($set)) {
      $error = 'Program set data is invalid';
      return null;
    }

    $cleanSets[] = [
      'reps' => max(1, (int) ($set['reps'] ?? 1)),
      'weight' => max(0, (int) ($set['weight'] ?? 0))
    ];
  }

  return $cleanSets;
}

function merge_program_sets($plannedSets, $oldOriginalSets, $oldCurrentSets) {
  $merged = [];

  foreach ($plannedSets as $index => $plannedSet) {
    $oldOriginal = isset($oldOriginalSets[$index]) && is_array($oldOriginalSets[$index]) ? $oldOriginalSets[$index] : null;
    $oldCurrent = isset($oldCurrentSets[$index]) && is_array($oldCurrentSets[$index]) ? $oldCurrentSets[$index] : null;

    if ($oldOriginal !== null && $oldCurrent !== null && set_changed_from_original($oldCurrent, $oldOriginal)) {
      $merged[] = [
        'reps' => max(1, (int) ($oldCurrent['reps'] ?? $plannedSet['reps'])),
        'weight' => max(0, (int) ($oldCurrent['weight'] ?? $plannedSet['weight']))
      ];
    } else {
      $merged[] = $plannedSet;
    }
  }

  return $merged;
}

function set_changed_from_original($currentSet, $originalSet) {
  return (int) ($currentSet['reps'] ?? 0) !== (int) ($originalSet['reps'] ?? 0)
    || (int) ($currentSet['weight'] ?? 0) !== (int) ($originalSet['weight'] ?? 0);
}

function clean_text($value, $limit) {
  return substr(trim((string) $value), 0, $limit);
}

function sanitize_exercise_pool($exercises, &$error) {
  $error = '';
  $clean = [];
  $seenIds = [];

  foreach ($exercises as $exercise) {
    if (!is_array($exercise)) {
      $error = 'Exercise pool item is invalid';
      return null;
    }

    $name = clean_text(isset($exercise['name']) ? $exercise['name'] : '', 120);
    if ($name === '') {
      $error = 'Exercise name is required';
      return null;
    }

    $id = clean_id(isset($exercise['id']) ? $exercise['id'] : '');
    if ($id === '') {
      $id = clean_id($name);
    }
    $baseId = $id;
    $suffix = 2;
    while (isset($seenIds[$id])) {
      $id = $baseId . '-' . $suffix;
      $suffix += 1;
    }
    $seenIds[$id] = true;

    $mainSets = sanitize_program_sets(isset($exercise['mainSets']) ? $exercise['mainSets'] : [], $error);
    if ($mainSets === null) return null;
    $warmUpSets = sanitize_program_sets(isset($exercise['warmUpSets']) ? $exercise['warmUpSets'] : [], $error);
    if ($warmUpSets === null) return null;

    $clean[] = [
      'id' => $id,
      'name' => $name,
      'group' => clean_text(isset($exercise['group']) ? $exercise['group'] : 'General', 120) ?: 'General',
      'mainSets' => $mainSets,
      'warmUpSets' => $warmUpSets
    ];
  }

  return $clean;
}

function clean_id($value) {
  $id = strtolower(trim((string) $value));
  $id = preg_replace('/[^a-z0-9]+/', '-', $id);
  $id = trim($id, '-');
  return substr($id, 0, 80);
}

function sanitize_exercise_pool_groups($groups, &$error) {
  $error = '';
  $cleanGroups = [];
  $seenGroupNames = [];
  $seenIds = [];

  foreach ($groups as $group) {
    if (!is_array($group)) {
      $error = 'Exercise pool group is invalid';
      return null;
    }

    $groupName = clean_text(isset($group['name']) ? $group['name'] : '', 120);
    if ($groupName === '') {
      $error = 'Group name is required';
      return null;
    }

    $groupKey = strtolower($groupName);
    if (isset($seenGroupNames[$groupKey])) {
      continue;
    }
    $seenGroupNames[$groupKey] = true;

    $cleanExercises = [];
    $exercises = isset($group['exercises']) && is_array($group['exercises']) ? $group['exercises'] : [];
    foreach ($exercises as $exercise) {
      if (!is_array($exercise)) {
        $error = 'Exercise pool item is invalid';
        return null;
      }

      $name = clean_text(isset($exercise['name']) ? $exercise['name'] : '', 120);
      if ($name === '') {
        $error = 'Exercise name is required';
        return null;
      }

      $id = clean_id(isset($exercise['id']) ? $exercise['id'] : '');
      if ($id === '') {
        $id = clean_id($name);
      }
      $baseId = $id;
      $suffix = 2;
      while (isset($seenIds[$id])) {
        $id = $baseId . '-' . $suffix;
        $suffix += 1;
      }
      $seenIds[$id] = true;

      $mainSets = sanitize_program_sets(isset($exercise['mainSets']) ? $exercise['mainSets'] : [], $error);
      if ($mainSets === null) return null;
      $warmUpSets = sanitize_program_sets(isset($exercise['warmUpSets']) ? $exercise['warmUpSets'] : [], $error);
      if ($warmUpSets === null) return null;

      $cleanExercises[] = [
        'id' => $id,
        'name' => $name,
        'mainSets' => $mainSets,
        'warmUpSets' => $warmUpSets
      ];
    }

    $cleanGroups[] = [
      'name' => $groupName,
      'exercises' => $cleanExercises
    ];
  }

  return ['groups' => $cleanGroups];
}

function group_flat_exercise_pool($exercises) {
  if ($exercises === null) {
    return null;
  }

  $groups = [];
  foreach ($exercises as $exercise) {
    $groupName = isset($exercise['group']) && trim((string) $exercise['group']) !== '' ? trim((string) $exercise['group']) : 'General';
    if (!isset($groups[$groupName])) {
      $groups[$groupName] = ['name' => $groupName, 'exercises' => []];
    }
    $groups[$groupName]['exercises'][] = [
      'id' => $exercise['id'],
      'name' => $exercise['name'],
      'mainSets' => $exercise['mainSets'],
      'warmUpSets' => $exercise['warmUpSets']
    ];
  }

  return ['groups' => array_values($groups)];
}
