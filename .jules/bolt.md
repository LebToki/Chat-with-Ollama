## 2024-05-18 - Optimized `count()` inside loops
**Learning:** Using `count($array)` in the condition part of a `for` loop evaluates it on every iteration in PHP. While `count()` is $O(1)$ for arrays, function call overhead is noticeable in tight loops.
**Action:** Always prefer `foreach` loops for iterating over arrays or calculate `count()` before the loop to save the function call overhead.

## 2026-03-19 - SQLite Database Initialization Static Check Optimization
**Learning:** Re-running ~15 `CREATE IF NOT EXISTS` queries on every request adds measurable overhead, even if they do nothing. However, completely skipping the script if the main table exists might prevent migrations or new table creations from running.
**Action:** Use a `static $initialized = false;` flag in `initializeSchema()` so the initialization code only runs once per PHP runtime process, rather than relying strictly on the database state.
