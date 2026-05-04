# Workout Tracker

Plain PHP + JSON workout tracker built from the frozen `workout_prototype.html` reference. The goal is to keep the approved UI intact while making workout edits persist on Hostinger shared hosting.

## Run Locally

```sh
php -S localhost:8000
```

Open `http://localhost:8000/index.php`.

## Deploy

```sh
syncnow
```

## Key Files

- `workout_prototype.html` - frozen visual and interaction reference
- `index.php` - app UI, JSON loading, and browser-side auto-save wiring
- `save.php` - PHP endpoint that validates and saves current workout data
- `data/current-week.json` - starter JSON data with separate `original` and `current` sections

## What Currently Saves

- Reps
- Weight
- Difficulty
- Notes

Edits are debounced and saved through `fetch()` to `save.php`.

## What Does Not Work Yet

- Add workout
- Profiles
- Analytics
- Week navigation
- Authentication

Those controls are intentionally static for now unless already handled by the prototype.

## Last Thing Done

Converted the approved prototype into a PHP app with JSON persistence, original/current data separation, read-only snapshot mode, and debounced auto-save status.

## Next Step

Deploy with `syncnow`, then confirm Hostinger allows PHP to write to `data/current-week.json`.
