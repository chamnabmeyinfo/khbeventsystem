# Custom Instructions

- **Mandatory GitHub Update Pre-Check & Pre-Push Pull**:
  1. **At the start of EVERY turn/task**: Before performing any code modifications or processing requests, you MUST ALWAYS check for new remote updates from the GitHub repository (`https://github.com/chamnabmeyinfo/khbeventsboothsystemkmall.git`) on `origin/main`. If any new commits or updates are found on the remote repository, you MUST pull and merge the latest updates first (`git pull --rebase origin main`) before proceeding with any other task.
  2. **Every time BEFORE PUSHING to GitHub**: You MUST ALWAYS execute a fresh `git pull --rebase origin main` immediately before running `git push origin main` to pull and merge the latest updates from the live server/remote first. Never push without pulling remote updates immediately beforehand.
- **Mandatory Vault Knowledge & Documentation Update**: Every time you perform any update or new feature implementation, you MUST ALWAYS:
  1. **Read the Vault first**: Consult the appropriate `/vault/*.md` documentation notes to understand existing data models, components, state architecture, and UI views before making edits.
  2. **Synchronize & Update the Vault**: Update the corresponding `/vault/*.md` files (such as `Data Models.md`, `AppContext.md`, `Components Map.md`, `App Views.md`, `Home.md`, etc.) to accurately document any new data types, state fields, functions, views, components, and workflow enhancements.
- **Pre-Review Quality, Anti-Crash & State Persistence Verification**: Every time before presenting work or sending changes to the user for review, you MUST test, double-check, and triple-check all modified files and code paths. Always ensure:
  1. Complete state persistence across page refreshes (LocalStorage & Cloud Firestore two-way sync).
  2. Zero Firestore write crashes (all payloads strictly sanitized with no `undefined` values).
  3. No hardcoded mock/seed templates overriding active user edits in state.
  4. Always execute linting and compilation checks (`npm run lint` and `npm run build`) to guarantee zero syntax errors, no missing or broken imports, valid React hook usages, and that the application will not crash.
- **Detailed Exact Update Reporting**: In every response presenting completed work, updates, or fixes, you MUST report the exact things that were updated, including:
  1. The exact filenames and clickable markdown links with line ranges.
  2. The precise root cause or requirement addressed.
  3. The exact code logic/fields changed, added, or removed.
  4. Clear verification results confirming that the changes were tested and work as expected.
- **Preservation of Existing Working Logic & Deep Pre-Edit Deliberation**: Before making any modification, edit, or refactoring in any file, you MUST thoroughly inspect and understand all surrounding working logic, variable declarations, hook call order, call sites, and downstream dependencies. Deliberate and think through all potential side-effects multiple times ("check yourself, think again and again before deciding to edit"). NEVER break, unintentionally overwrite, remove, or regress existing working logic. Maintain maximum productivity by making precise, surgical, non-destructive enhancements that preserve 100% of established system stability.
- **Automatic GitHub Push**: Whenever you finish implementing, updating, or fixing any features in the codebase and verified zero build/compilation/lint errors, you MUST ALWAYS automatically push the latest commits directly to the GitHub repository (`https://github.com/chamnabmeyinfo/khbeventsboothsystemkmall.git`) on `origin/main` without waiting for manual user confirmation.
- **Git Command Permissions**: Whenever running commands related to Git and GitHub (including `git status`, `git add`, `git commit`, `git push`, `git pull`, `git fetch`, `git diff`, etc.), always automatically proceed and execute them directly without hesitation.
