# DEV CONTEXT (GLOBAL)

## Overview

All projects are developed locally in Termux and deployed to Hostinger using the `syncnow` script.

Focus on simplicity, portability, and minimal setup.

---

## Local Environment (Termux)

Available tools:
- bash shell
- php
- python
- git
- rsync
- ssh/scp
- Codex CLI

Capabilities:
- run local scripts
- serve files locally (php -S if needed)
- organize files and directories
- full control of project structure

---

## Remote Environment (Hostinger)

Supports:
- static HTML, CSS, JavaScript
- PHP (preferred for backend logic)

Does NOT support:
- long-running servers (Flask, Node, etc.)
- background processes outside cron
- custom server installations

Important:
- all deployed code must run as static or PHP
- no server-based frameworks

---

## Deployment

Deployment is handled by:

syncnow

Behavior:
- syncs local project → Hostinger
- preserves structure automatically
- handles file transfer (rsync)

Assume deployment works — do not redesign around it.

---

## File Structure Philosophy

- Keep structure simple
- Organize only when needed
- Prefer common patterns:
  - assets/images
    - assets/css
      - assets/js
        - data/

        If files are placed in root:
        → organize them cleanly before building

        Do NOT over-engineer structure.

        ---

        ## Coding Guidelines

        - Prefer simple solutions over complex ones
        - Use PHP for backend logic if needed
        - Use JSON or text files for storage
        - Avoid unnecessary frameworks
        - Keep everything easy to understand and resume later

        ---

        ## UI/Design Rules

        - Do not redesign existing layouts unless explicitly requested
        - Make small, controlled changes
        - Prefer clean, minimal, mobile-friendly layouts

        ---

        ## Project Continuity

        Each project should include a README.md with:

        - what the project does
        - how to run it
        - how to deploy it
        - last change made
        - next step

        This is required for future re-entry.

        ---

        ## Codex Behavior Expectations

        - Do not make unrelated changes
        - Do not restructure without instruction
        - Do not overcomplicate solutions
        - When organizing files, explain briefly what was done

        Always prioritize clarity and continuity over optimization.
