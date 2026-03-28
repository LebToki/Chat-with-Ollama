## 2024-05-24 - ModelStatusController SSRF / Path Traversal
**Vulnerability:** The `$model` parameter in `ModelStatusController.php` was taken directly from user input (`$_GET['model']`) and appended to an API URL without validation or sanitization, potentially allowing an attacker to inject path traversal characters (like `../`) and hit unintended endpoints via Server-Side Request Forgery.
**Learning:** Even when hitting internal or trusted upstream APIs (like Ollama), user input appended to the path or body must be strictly validated. Guzzle handles requests reliably, but does not sanitize path inputs by default.
**Prevention:** Always validate parameters against an expected allowlist or strict format regex (e.g., `^[a-zA-Z0-9\-_:\.]+$` for model names) before using them to construct upstream requests.

## 2024-05-25 - Path Traversal in Document Upload
**Vulnerability:** In `public/api/documents.php`, the file name was directly extracted using `$originalName = $_FILES['document']['name']` and subsequently used in the destination path for `move_uploaded_file()`. If an attacker uploaded a file named `../../shell.php`, the file would be saved outside the intended `uploads/` directory, causing a critical path traversal and arbitrary file upload vulnerability.
**Learning:** `$_FILES['input_name']['name']` is entirely client-controlled and must never be trusted. When using it to construct file paths, it can lead to path traversal vulnerabilities if it contains `../` sequences.
**Prevention:** Always sanitize the filename from `$_FILES` using `basename()` to strip any directory paths and ensure only the final filename is used.## 2024-05-18 - [CRITICAL] Fix command injection bypass in PHP code execution
**Vulnerability:** The PHP code execution service ran arbitrary user-provided PHP code using `php` via `bash` and relied on a weak regex blocklist to prevent RCE. The blocklist could be easily bypassed using variable functions or backticks.
**Learning:** Naive regex blocklists are fundamentally insecure for sandboxing dynamic languages. A defense-in-depth approach at the runtime level is much safer.
**Prevention:** Always use runtime sandboxing features, such as passing `disable_functions` configuration options to disable dangerous capabilities like `system`, `exec`, `shell_exec`, etc. when executing untrusted PHP code in a sandbox.
