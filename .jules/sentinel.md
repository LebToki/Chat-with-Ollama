## 2024-05-24 - [Command Injection via Double Quotes]
**Vulnerability:** Critical command injection in VoiceService.php. The code used `escapeshellarg()` on user input but then wrapped the `%s` format specifier in double quotes (`"%s"`) in the `sprintf` command for `espeak`.
**Learning:** `escapeshellarg()` wraps its output in single quotes. When placed inside double quotes in a shell command, Bash evaluates variable and command substitutions (e.g., `$(whoami)`) inside the string, defeating the purpose of escaping.
**Prevention:** Never wrap `escapeshellarg()` output in double quotes when constructing shell commands. Ensure the escaped argument stands alone (e.g., `%s` instead of `"%s"`).
