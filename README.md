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
- `exercise-pool.php` - Exercise Pool manager for the master exercise list
- `assets/css/app.css` - shared admin/page styles used by the Exercise Pool view
- `data/exercise-pool.json` - master list of available exercises
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
- `Exercise Pool` opens the master exercise list.
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

The title now reads `Editing <profile name>`, with `Editing` shown as smaller supporting text and the profile name as the stronger label.

Exercises are collapsed by default so the editor is easier to scan. Tap an exercise row to expand or collapse its full edit form. Use `Reorder`, or long-press an exercise row, to show `Move Up` / `Move Down` controls. Exercise order saves with the active profile when `Save Changes` is tapped.

The Profile Editor uses the same dark gradient, mobile app shell, rounded cards, and pill-style action controls as the Exercise Pool and main app.

Editable now:

- Exercise name
- Day
- Group/category from a dropdown
- Main set reps and weight
- Warm-up set reps and weight
- Add exercise from the Exercise Pool
- Add set
- Delete set
- Delete exercise
- Reorder exercises

All current profile exercises are treated as Monday/current day for now. The editor includes a `Current Day` selector with Monday through Sunday as placeholder options for future workout-week expansion. Monday is currently the only populated day; choosing another day shows an empty state and does not create full multi-day/week behavior yet.

Editor changes are saved only when `Save Changes` is tapped. The editor save updates both `original` and `current` structure for the active profile. It attempts to preserve notes, difficulty, and set reps/weights that had already been changed from the previous original plan.

Opening the Profile Editor updates the URL to `view=editor`. Refreshing the browser keeps the active profile and reopens the editor. Closing the editor removes the view parameter and returns to the main logging screen.

CSS links use `filemtime()` cache busting so stylesheet changes appear immediately during development.

## Exercise Pool

Open the pool from `☰` -> `Exercise Pool`.

The Exercise Pool is the master list of possible exercises. It is stored in `data/exercise-pool.json` as groups:

- group `name`
- group `exercises`
- exercise `id`
- exercise `name`
- optional default `mainSets`
- optional default `warmUpSets`

The Exercise Pool is group-first. The main pool screen shows oval group buttons only, including the built-in groups and any saved custom groups. Tap a group to manage the exercises inside it. Exercise numbers are not used in the Exercise Pool.

The pool page can add groups, add exercises inside a group, delete exercises from a group, edit exercise names, and edit default main or warm-up sets.

Profile Editor and Exercise Pool are intentionally different:

- Exercise Pool controls what exercises are available to choose.
- Profile Editor controls which exercises the active profile uses, plus that profile's day, group/category, and planned sets.

`Add Exercise` in the Profile Editor now opens a picker from the Exercise Pool. The picker refreshes from `data/exercise-pool.json` when opened, so newly saved pool exercises are available without relying on a hardcoded or stale list. Choosing an exercise copies its name, group, and default sets into the active profile draft. The profile still saves only when `Save Changes` is tapped.

Exercise Pool uses the main app's dark gradient visual style with rounded panels and pill-style group buttons. Inside a group, exercise rows expand into edit cards. The pool intentionally has no reorder control.

The pool Save button stays grey when there are no pending edits, changes to `Save Changes` with a subtle blue pulse when dirty, shows `Saving...` during save, briefly shows `Saved` on success, then returns to grey and updates a small `Saved 2:41 PM` timestamp. Save errors keep the dirty state visible and do not update the timestamp.

Deleting an exercise from a profile does not delete it from the Exercise Pool. Deleting an exercise from the Exercise Pool does not remove copies that already exist in profile workout plans.

## What Does Not Work Yet

- Add workout
- New Profile creation
- Analytics
- Week navigation
- Authentication
- Automatic cleanup of profile exercises when a pool exercise is deleted
- Complex template management
- Full multi-day workout-week behavior

Those controls are intentionally static for now unless already handled by the prototype.

## Last Thing Done

Changed the Profile Editor title to `Editing <name>`, normalized current profile/template exercises to Monday, and added a placeholder Current Day selector for future workout-week expansion.

## Next Step

Deploy with `syncnow` and confirm Hostinger can write both `data/exercise-pool.json` and `data/profiles/*/current-week.json`.
