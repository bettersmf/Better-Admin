# Better Admin 0.1.0-dev3k

## Stage 4B — Bulk Theme Cleanup Polish

- Removes the literal `\n` artifacts from the Theme Settings cleanup panel and confirmation view.
- Keeps protected themes fully readable instead of dimming the whole row.
- Adds compact **Core Theme · Protected** and **Current Forum Theme · Protected** badges.
- Tightens checkbox, theme name, version, and row alignment.
- Reworks the controls into a responsive action bar with selection controls grouped separately from deletion.
- Leaves all Stage 4A deletion logic, Curve/default-theme protections, SMF native removal calls, and security validation unchanged.

# Better Admin 0.1.0-dev3j

## Stage 4A — Bulk Theme Cleanup

- Adds a **Bulk Theme Cleanup** panel to SMF's Theme Settings / Manage and Install page.
- Adds checkboxes for every removable installed theme.
- **Curve (theme 1) is permanently protected** and never selectable.
- The forum's current overall/default theme is permanently protected and never selectable.
- Adds **Select All Unused Themes**, **Clear Selection**, and **Delete Selected Themes** controls.
- Shows an explicit confirmation page before removing anything.
- Re-validates every theme server-side before deletion, so protected themes cannot be removed with a crafted request.
- Uses SMF's native `remove_theme()` and `remove_dir()` routines, preserving SMF's own member/board cleanup behavior.
- Keeps the dev3i permanent forum Delete-icon fix intact.
- No Theme Lab files or SMF core files are modified.


# Better Admin 0.1.0-dev3i

## dev3i — Permanent Delete icon ownership

- Restores Better Admin's own protection for the promoted Delete icon instead of relying on Theme Lab.
- Keeps the surrounding theme-styled quick button intact while forcing the icon tile/background itself transparent.
- Covers normal, hover, focus, and active states so custom-theme rules cannot resurrect the red-on-red square.
- Loads Better Admin's topic-view CSS from the default theme with a fresh dev3i asset seed to avoid stale cached CSS.
- No moderation permissions, delete handlers, bulk actions, review logic, or Package Manager behavior changed.

# Better Admin 0.1.0-dev3g

## dev3g — Package Manager alert styling fix

- Loads Better Admin Package Manager CSS explicitly from the default theme so generated/custom themes cannot miss the alert styles.
- Removes the literal `\n\n` text caused by single-quoted template output.
- Success notices render as green status cells and fade automatically; errors render as red status cells and remain until dismissed.


## Stage 3F - Package Manager alert transport/render fix

- Carries operation notices through the Package Manager redirect URL, with the session flash retained as fallback.
- Renders success/error feedback server-side as a template layer instead of depending on deferred DOM JavaScript.
- Success notices fade automatically; failure notices remain until dismissed.

## Stage 3A Package Manager alert render fix

- Fixes Package Manager success/failure notices not appearing after redirects.
- Removes a nested `DOMContentLoaded` listener that SMF was already wrapping, which prevented the alert code from ever running.
- Places alerts above the Package Manager intro for immediate visibility.
- Fixes the same deferred-JavaScript issue for automatic scrolling to the just-installed/highlighted package.
- Keeps success notices auto-fading and error notices persistent until dismissed.

# Better Admin 0.1.0-dev3d

## Stage 3A Package Manager display polish

- Removes literal `\n` text that could appear between Package Manager operation buttons.
- Keeps the new stable package ordering and post-install return/highlight behavior unchanged.


## dev2n - Stage 2B Bulk Restore

- Adds **Restore Selected Replies** to the reply bulk-management bar.
- The restore button only enables when at least one selected reply is currently Under Review.
- The Under Review button only enables when at least one selected reply is not already reviewed.
- Mixed selections are safe: restore only removes review records for selected replies that are actually reviewed; normal replies are left unchanged.
- The topic starter remains permanently excluded server-side and in the UI.


## 0.1.0-dev2g

- Stage 2A delete-icon polish: removes theme-added square/tile decoration behind the Delete icon and restores SMF's native red X on a transparent background.
- No behavior or permission changes.

# Better Admin 0.1.0-dev2b

## Stage 1 - Bulk Topic Management

- Staff-only bulk topic selection on board topic listings with more than one topic.
- Bulk Delete and Move use SMF native moderation handlers.
- Disable for Review hides selected topics from regular members while leaving them visible to authorized staff.
- Restore from Review returns quarantined topics to normal visibility.

## Stage 2A - Visible Topic/Post Controls

- Removes SMF's post-level More... dropdown from topic view.
- Promotes the actions SMF would have placed under More... directly into the post quick-button row.
- Renames Modify to Edit.
- Renames Remove Topic / Remove to Delete.
- Renames Report to moderator to Report.
- Preserves SMF's native URLs, confirmation prompts, and permission checks.
- Preserves less-common moderation actions such as Split, Warn, Restore, Approve, and Unapprove by exposing them directly when SMF says they are available.

Stage 2B bulk reply management is intentionally not included yet.


## Stage 2A Polish - dev2b

- Fixes the long-standing red-on-red Delete icon by forcing SMF's existing remove icon to white on destructive buttons.
- Adds dedicated action classes for Edit, Delete, Report, and secondary moderation actions without changing their native SMF handlers.
- Ensures Better Admin CSS is loaded on topic view, not only on board topic listings.
- Adds a small mobile wrap-spacing safeguard for promoted controls.

No Stage 2B bulk-reply behavior is included in this pass.


## Stage 2B foundation (dev2e)

This pass intentionally adds layout only, not bulk deletion behavior yet:

- Adds a **Select All Replies** checkbox strip directly below the topic starter when the topic has more than one reply and the viewer has reply-delete permission.
- Adds one checkbox to each selectable reply; the topic starter never receives a checkbox.
- Prefixes visible reply counters with **Reply**, e.g. `Reply #1`, `Reply #2`.
- No bulk reply action is wired in this pass. Selection is UI-only for placement/testing before deletion logic is introduced.

- Removes the temporary dev2d delete-icon theme workaround; Theme Lab now owns that styling fix.


### dev2f reply-selection rendering fix

- Reworked Stage 2B placement to use SMF's stable `#quickModForm > #msg...` structure directly rather than injected post CSS classes.
- Added a dedicated Stage 2B JavaScript file to avoid stale/cached copies of the earlier reply-selection logic.
- Topic starter remains excluded; Select All Replies is inserted immediately below it.
- Reply counters render as `Reply #1`, `Reply #2`, etc.


## 0.1.0-dev2i — Stage 2B reply checkbox placement polish

- Moves each reply selection checkbox to immediately after the visible `Reply #X` counter.
- Keeps Select All Replies below the topic starter and leaves all Stage 2B behavior unchanged.
- Adds a fresh cache seed so the placement update is picked up immediately.

## 0.1.0-dev2h — Stage 2B asset-load fix

- Loads the reply-selection JavaScript from `integrate_load_theme`, before the page head is rendered.
- Forces the packaged default-theme script and uses a dev2h cache seed to prevent stale Stage 2B JavaScript.
- Keeps the topic starter outside reply selection.
- Adds `Select All Replies`, per-reply checkboxes, and prefixes reply counters with `Reply`.
- Still does not wire bulk deletion; this pass is layout and selection only.

## 0.1.0-dev2j — Stage 2B reply checkbox anchor fix

- Anchors each reply checkbox inside the native floated `Reply #X` counter span.
- Keeps the checkbox immediately after the reply number instead of allowing it to fall to the left edge of the reply header.
- No bulk-delete behavior added yet; this remains a layout-only Stage 2B pass.

## 0.1.0-dev2k — Stage 2B bulk reply delete

- Adds **Delete Selected Replies** beside Select All Replies.
- The delete button stays disabled until at least one reply is selected.
- Uses a confirmation prompt before deletion.
- The topic starter is excluded in both the browser UI and server-side validation, so a crafted request cannot bulk-delete the first post.
- Uses SMF's native `removeMessage()` path for each selected reply so topic counts, last-post metadata, attachments, hooks, and moderation logging remain consistent.
- Bulk reply eligibility now resolves topic ownership correctly for the native `delete_replies` permission.


## 0.1.0-dev2l — Stage 2B single-reply Under Review

- Adds a staff-only **Under Review** action to individual replies.
- The topic starter is never eligible; topic-level review remains a Stage 1 action.
- Reviewed replies remain visible to authorized staff with an **Under Review** badge and **Restore** action.
- Reviewed replies are filtered from ordinary members while leaving the rest of the topic intact.
- Permanently deleting a reply also cleans up its Better Admin review record.

## 0.1.0-dev2m — Stage 2B Bulk Under Review

- Adds **Put Selected Under Review** beside Delete Selected Replies for staff.
- Uses the already-tested reply selection UI; the topic starter remains permanently excluded.
- Validates every submitted message against the current topic server-side before quarantining it.
- Reuses the existing Better Admin reply-review table, badge, staff visibility, and regular-member hiding behavior.
- Bulk restore is intentionally deferred to a later pass.

## 0.1.0-dev3a — Stage 3A: Better Package Manager foundation

- Replaces SMF's transient package-list ID ordering with a stable per-uploaded-package position.
- Existing package files are seeded once; newly discovered/re-uploaded package files append to the end of their package-type list.
- Deleting a package retires only that package instance's position. Re-uploading it later gives it a new position at the end.
- Successful installs/upgrades that do not define their own redirect now return to Package Manager automatically.
- The package that just completed is scrolled into view and briefly highlighted.
- Package-defined post-install redirects are preserved.


## dev3c

- Restores clean whitespace between Package Manager action buttons without reintroducing literal `\n` text.
- No changes to Stage 3 stable package ordering or install redirect behavior.
