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
- `data/templates/default-week.json` - default week template copied from the original starter data
- `data/profiles/chris/current-week.json` - Chris profile data
- `data/profiles/dustin/current-week.json` - Dustin profile data
- `data/current-week.json` - legacy backup of the original single-profile data

Each profile file keeps the same shape:

```json
{
  "original": {},
  "current": {}
}
```

## Profiles

Use the top-right `☰` button to open the profile menu.

Menu items:

- `Edit Current Profile` opens the profile/program editor for the active profile.
- `New Profile` is visible but intentionally disabled for now.
- `Chris` switches to `index.php?profile=chris`.
- `Dustin` switches to `index.php?profile=dustin`.

The active profile name is shown in the profile chip. Profile switching is URL-based, and the selected profile is also stored in `localStorage` for later use.

## What Normal Logging Saves

- Reps
- Weight
- Difficulty
- Notes

Normal workout logging uses the existing debounced autosave and saves only the active profile's `current` data through `save.php?profile=...`. It does not overwrite `original`.

## Profile Editor

Open the editor from `☰` -> `Edit Current Profile`.

Editable now:

- Exercise name
- Day
- Group/category
- Main set reps and weight
- Warm-up set reps and weight
- Add exercise
- Add set
- Delete set
- Delete exercise

Editor changes are saved only when `Save Changes` is tapped. The editor save updates both `original` and `current` structure for the active profile. It attempts to preserve notes, difficulty, and set reps/weights that had already been changed from the previous original plan.

## What Does Not Work Yet

- Add workout
- New Profile creation
- Analytics
- Week navigation
- Authentication

Those controls are intentionally static for now unless already handled by the prototype.

## Last Thing Done

Added `Add Exercise` support inside the profile/program editor. New exercises are saved to both `original` and `current` for the active profile.

## Next Step

Deploy with `syncnow` and confirm Hostinger can write profile editor changes to `data/profiles/*/current-week.json`.
