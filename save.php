<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'POST required']);
  exit;
}

$dataFile = __DIR__ . '/data/current-week.json';
$stored = json_decode(file_get_contents($dataFile), true);
$input = json_decode(file_get_contents('php://input'), true);

// Backend wiring: accept only editable current-state fields and preserve original.
if (!is_array($stored) || !isset($stored['original'], $stored['current'])) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Stored workout data is invalid']);
  exit;
}

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
