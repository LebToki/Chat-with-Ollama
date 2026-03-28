## 2024-05-18 - Optimized `count()` inside loops
**Learning:** Using `count($array)` in the condition part of a `for` loop evaluates it on every iteration in PHP. While `count()` is $O(1)$ for arrays, function call overhead is noticeable in tight loops.
**Action:** Always prefer `foreach` loops for iterating over arrays or calculate `count()` before the loop to save the function call overhead.

## 2026-03-19 - SQLite Database Initialization Static Check Optimization
**Learning:** Re-running ~15 `CREATE IF NOT EXISTS` queries on every request adds measurable overhead, even if they do nothing. However, completely skipping the script if the main table exists might prevent migrations or new table creations from running.
**Action:** Use a `static $initialized = false;` flag in `initializeSchema()` so the initialization code only runs once per PHP runtime process, rather than relying strictly on the database state.

## 2026-03-19 - PHP Sorting Arrays (O(N log N) to O(N log K)) Optimization
**Learning:** In `src/Services/RAGService.php`, computing embeddings for thousands of chunks and then pushing them all to an array before calling `usort()` and `array_slice()` to retrieve the top 5 chunks consumes significant memory and processing time `O(N log N)`.
**Action:** Use `SplPriorityQueue` to maintain a bounded queue of size $K$ (e.g., $K=5$). This reduces the time complexity to `O(N log K)` and massively reduces memory overhead. Note that `SplPriorityQueue` behaves as a max-heap by default in PHP, so inserting elements with a negative similarity score (`-$similarity`) effectively makes it behave as a min-heap where `top()` returns the smallest element.
## 2026-03-19 - Avoid `implode` for string length calculations
**Learning:** Using `strlen(implode($delimiter, $array))` to calculate the size of a joined string allocates a new, potentially large string in memory just to count its characters. Inside a tight loop (e.g., text chunking where $overlap can be large), this causes severe performance degradation and memory churn.
**Action:** Use a fast arithmetic loop to tally the string lengths manually (`sum(strlen($w) + 1) - 1`) instead of allocating a throwaway string. This maintains the exact behavior while dropping execution time drastically.

## 2026-03-21 - RAG Retrieval 2-Phase Query Optimization
**Learning:** Fetching large string content (e.g., `dc.content`) alongside vector embeddings for thousands of rows during the initial nearest-neighbor search causes massive memory/IO overhead, especially when 99% of that data will be discarded after sorting.
**Action:** Use a 2-phase approach for RAG retrieval. Phase 1 fetches only lightweight columns (`chunk_id`, `embedding`) for all matching rows to calculate similarities. Phase 2 fetches the heavy payload (`content`, `filename`) *only* for the top K matching `chunk_id`s, significantly reducing memory consumption and query latency.

## 2026-03-21 - Fast JSON Array Parsing Optimization
**Learning:** For extremely simple, flat numerical arrays stored as JSON strings (like `[0.1,0.2,-0.5]`), using `json_decode()` introduces significant parser overhead when executed thousands of times.
**Action:** Instead of `json_decode()`, use `explode(',', substr($jsonString, 1, -1))` to quickly split the string. PHP's implicit type juggling handles the resulting strings as floats during mathematical operations, providing a measurable performance boost in tight loops like cosine similarity calculations.
