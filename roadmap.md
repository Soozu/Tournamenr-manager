
# Tournament Manager System Roadmap

## Overview
A Tournament Manager System designed for CSG, allowing Admins and Gamemasters to manage tournaments efficiently without requiring user accounts or APIs.

---

## Phase 1: Planning and Design

1. **Define Requirements**:
   - Admin Features:
     - Create, edit, and delete tournaments.
     - Add teams, players, and match schedules.
   - Gamemaster Features:
     - Update match results.
     - Monitor ongoing matches.
   - Display tournament brackets and schedules.

2. **Create a Database Schema**:
   - Tables:
     - `tournaments` (id, name, date, status).
     - `teams` (id, name, tournament_id, players).
     - `matches` (id, tournament_id, team1_id, team2_id, date, time, result).

3. **Sketch the Interface**:
   - Navigation: Admin Dashboard, Manage Tournaments, Manage Matches, View Results.
   - Forms for data input.

---

## Phase 2: Setup Environment

1. Install **XAMPP** for local development (PHP, MySQL, Apache).
2. Create the database using phpMyAdmin.
3. Structure project files:
   - `index.php` (homepage).
   - `/admin/` (folder for admin functionalities).
   - `/gamemaster/` (folder for gamemaster functionalities).
   - `/css/` and `/js/` (folders for styles and scripts).

---

## Phase 3: Development

### Admin Features:
1. **Dashboard**:
   - Overview of tournaments (ongoing and upcoming).
   - Quick links to create/edit/delete tournaments.

2. **Manage Tournaments**:
   - Add new tournaments.
   - Edit and delete existing tournaments.

3. **Manage Teams**:
   - Add/edit teams and players.
   - Assign teams to tournaments.

4. **Manage Matches**:
   - Schedule matches and assign teams.
   - View and update match results.

### Gamemaster Features:
1. **Match Updates**:
   - View a table of upcoming and ongoing matches.
   - Input scores and mark matches as complete.

2. **Monitor Tournament Progress**:
   - View match schedules and team standings.
   - Filter matches (completed, upcoming, ongoing).

3. **Live Updates (Optional)**:
   - Input scores in real-time.
   - Use AJAX to update the front-end dynamically.

### Tournament Viewer:
- Dynamic tournament brackets.
- List of matches with results and schedule.

---

## Phase 4: Testing

1. Test all functionalities:
   - Adding and managing tournaments, teams, and matches.
   - Updating results as a gamemaster.
2. Test edge cases:
   - Ensure no duplicate entries for teams or matches.
   - Handle invalid inputs gracefully.

---

## Phase 5: Enhancements

1. Add features like:
   - Printable tournament brackets.
   - Filters for past and upcoming tournaments.

2. Improve UI/UX:
   - Use CSS frameworks (e.g., Bootstrap) for responsive design.

---

## Phase 6: Deployment

1. Host the system locally or on a basic web server (no API needed).
2. Provide training or documentation for admins and gamemasters.

---

## File Structure

- `index.php`: Homepage.
- `/admin/`: Admin-related functionalities.
  - `dashboard.php`
  - `manage_tournaments.php`
  - `manage_teams.php`
  - `manage_matches.php`
- `/gamemaster/`: Gamemaster-related functionalities.
  - `match_updates.php`: Update match results.
  - `tournament_view.php`: View tournament progress.
- `/css/` and `/js/`: Static files for design and functionality.

---

## Database Schema

- **`tournaments`**: 
  - Columns: `id`, `name`, `date`, `status`.
- **`teams`**: 
  - Columns: `id`, `name`, `tournament_id`, `players`.
- **`matches`**: 
  - Columns: `id`, `tournament_id`, `team1_id`, `team2_id`, `date`, `time`, `result`.

---

## Notes
This system is designed to be simple, cost-effective, and easy to maintain without relying on external APIs or user accounts.
