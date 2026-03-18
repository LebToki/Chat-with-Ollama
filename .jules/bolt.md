## 2024-05-18 - Optimized `count()` inside loops
**Learning:** Using `count($array)` in the condition part of a `for` loop evaluates it on every iteration in PHP. While `count()` is $O(1)$ for arrays, function call overhead is noticeable in tight loops.
**Action:** Always prefer `foreach` loops for iterating over arrays or calculate `count()` before the loop to save the function call overhead.