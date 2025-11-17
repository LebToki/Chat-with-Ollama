/**
 * HTML Sanitizer Utility
 * Provides safe HTML rendering functions to prevent XSS attacks
 */

const HtmlSanitizer = {
    /**
     * Escape HTML special characters
     * @param {string} text - Text to escape
     * @returns {string} Escaped text safe for HTML
     */
    escape: function(text) {
        if (typeof text !== 'string') {
            return String(text);
        }
        
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Safely render markdown-like formatting with enhanced features
     * Supports: bold, italic, code blocks, inline code, lists, headings, cards
     * All other HTML is escaped for security
     * @param {string} content - Content to format
     * @returns {string} Safe HTML string
     */
    renderMarkdown: function(content) {
        if (typeof content !== 'string') {
            return '';
        }

        // First escape all HTML to prevent XSS
        let safe = this.escape(content);
        
        // Store code blocks temporarily to avoid processing their content
        const codeBlockPlaceholder = '___CODE_BLOCK___';
        const codeBlocks = [];
        let codeBlockIndex = 0;
        
        // Extract code blocks first (process before other markdown)
        safe = safe.replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, lang, code) {
            const language = lang ? ` data-language="${lang}"` : '';
            const placeholder = codeBlockPlaceholder + codeBlockIndex + codeBlockPlaceholder;
            codeBlocks[codeBlockIndex] = `<pre class="code-block"><code${language}>${code.trim()}</code></pre>`;
            codeBlockIndex++;
            return placeholder;
        });
        
        // Headings: # Heading -> <h3>Heading</h3> (process before other formatting)
        safe = safe.replace(/^### (.*)$/gm, '<h5 class="md-heading">$1</h5>');
        safe = safe.replace(/^## (.*)$/gm, '<h4 class="md-heading">$1</h4>');
        safe = safe.replace(/^# (.*)$/gm, '<h3 class="md-heading">$1</h3>');
        
        // Horizontal rule: ---
        safe = safe.replace(/^---$/gm, '<hr class="md-hr">');
        
        // Unordered lists: - item or * item (but not if it's part of bold/italic)
        const lines = safe.split('\n');
        const processedLines = [];
        let inList = false;
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const listMatch = line.match(/^[\-\*] (.+)$/);
            
            if (listMatch) {
                if (!inList) {
                    processedLines.push('<ul class="md-list">');
                    inList = true;
                }
                processedLines.push('<li class="md-list-item">' + listMatch[1] + '</li>');
            } else {
                if (inList) {
                    processedLines.push('</ul>');
                    inList = false;
                }
                processedLines.push(line);
            }
        }
        if (inList) {
            processedLines.push('</ul>');
        }
        safe = processedLines.join('\n');
        
        // Bold: **text** -> <strong>text</strong>
        safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
        // Italic: *text* -> <em>text</em> (but not if it's part of **text**)
        safe = safe.replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em>$1</em>');
        
        // Inline code: `text` -> <code>text</code> (only if not already in code block)
        safe = safe.replace(/`([^`\n]+?)`/g, '<code class="inline-code">$1</code>');
        
        // Restore code blocks
        codeBlocks.forEach(function(block, index) {
            safe = safe.replace(codeBlockPlaceholder + index + codeBlockPlaceholder, block);
        });
        
        // Line breaks: \n -> <br> (but preserve double line breaks for paragraphs)
        safe = safe.replace(/\n\n+/g, '</p><p class="md-paragraph">');
        safe = safe.replace(/\n/g, '<br>');
        
        // Wrap in paragraph if not already wrapped in block elements
        if (!safe.match(/^<(h[1-6]|pre|ul|hr|div)/)) {
            safe = '<p class="md-paragraph">' + safe + '</p>';
        }

        return safe;
    },

    /**
     * Create a text node safely (no HTML rendering)
     * @param {string} text - Text to display
     * @returns {Text} Text node
     */
    createTextNode: function(text) {
        return document.createTextNode(text || '');
    },

    /**
     * Set text content safely (escapes HTML)
     * @param {HTMLElement} element - Element to set text on
     * @param {string} text - Text to set
     */
    setTextContent: function(element, text) {
        if (element) {
            element.textContent = text || '';
        }
    },

    /**
     * Sanitize and set innerHTML with markdown support
     * @param {HTMLElement} element - Element to set HTML on
     * @param {string} content - Content to render (will be sanitized)
     * @param {boolean} allowMarkdown - Whether to allow markdown formatting (default: true)
     */
    setSafeHTML: function(element, content, allowMarkdown = true) {
        if (!element) {
            return;
        }

        if (allowMarkdown) {
            element.innerHTML = this.renderMarkdown(content);
        } else {
            // Just escape, no markdown
            element.textContent = content || '';
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HtmlSanitizer;
}

