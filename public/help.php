<?php 
require_once __DIR__ . '/icon_helper.php';
require __DIR__ . '/header.php';
$config = require dirname(__DIR__) . '/src/config.php';
$companyName = $config['companyName'] ?? '{{COMPANY_NAME}}';
$companyUrl = $config['companyUrl'] ?? '{{COMPANY_URL}}'; 
?>

<main class="main-content" role="main" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
    <div class="container-fluid" style="padding: 24px 32px; flex: 1; display: flex; flex-direction: column; overflow: hidden;">
        <!-- Description Section -->
        <div style="margin-bottom: 32px;">
            <p style="color: var(--text-secondary); font-size: 16px; margin: 0;">
                Find answers to common questions about Chat with Ollama, from setup and RAG to document processing, embeddings, and more.
            </p>
        </div>

        <!-- Help Content with Accordion -->
        <div class="glass-card" style="flex: 1; overflow-y: auto; overflow-x: hidden; padding: 32px;">
            <div class="accordion help-accordion" id="helpAccordion">
                
                <!-- Getting Started -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingGettingStarted">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGettingStarted" aria-expanded="false" aria-controls="collapseGettingStarted">
                            <?php echo IconHelper::icon('mdi:rocket-launch'); ?>
                            Getting Started
                        </button>
                    </h2>
                    <div id="collapseGettingStarted" class="accordion-collapse collapse" aria-labelledby="headingGettingStarted" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="margin-bottom: 16px; color: var(--text-secondary); line-height: 1.8;">
                                Chat with Ollama is a RAG-powered AI assistant that allows you to chat with various Ollama models while leveraging your own documents for context-aware responses.
                            </p>
                            <h3 style="color: var(--text-primary); font-size: 18px; font-weight: 600; margin: 24px 0 12px 0;">Quick Start</h3>
                            <ol style="padding-left: 24px; margin-bottom: 16px; color: var(--text-secondary); line-height: 1.8;">
                                <li style="margin-bottom: 8px;">Start a new chat by clicking the <strong style="color: var(--accent);">+</strong> button in the sidebar</li>
                                <li style="margin-bottom: 8px;">Type your message in the chat input at the bottom</li>
                                <li style="margin-bottom: 8px;">Enable RAG to use your uploaded documents for context</li>
                                <li style="margin-bottom: 8px;">Select your preferred Ollama model from the header dropdown</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Chat Sessions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingChatSessions">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChatSessions" aria-expanded="false" aria-controls="collapseChatSessions">
                            <?php echo IconHelper::icon('mdi:message-text'); ?>
                            How do I manage chat sessions?
                        </button>
                    </h2>
                    <div id="collapseChatSessions" class="accordion-collapse collapse" aria-labelledby="headingChatSessions" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                Organize your conversations into separate chat sessions. Each session maintains its own conversation history.
                            </p>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Click on any session in the sidebar to resume a conversation</li>
                                <li>Rename sessions by hovering and clicking the rename icon</li>
                                <li>Empty sessions (0 messages) are automatically hidden</li>
                                <li>Create document-specific chats by clicking the chat icon on any document</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Model Selection -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingModelSelection">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModelSelection" aria-expanded="false" aria-controls="collapseModelSelection">
                            <?php echo IconHelper::icon('mdi:robot'); ?>
                            How do I select and manage Ollama models?
                        </button>
                    </h2>
                    <div id="collapseModelSelection" class="accordion-collapse collapse" aria-labelledby="headingModelSelection" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                Choose from various Ollama models available on your server. The default model is <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">llama3.2:latest</code>.
                            </p>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Select models from the dropdown in the header</li>
                                <li>Go to Settings and click "Sync Models" to update the list</li>
                                <li>Set a default model that will be used for new chats</li>
                                <li>Models are cached locally for faster loading</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- RAG Feature -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingRAG">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRAG" aria-expanded="false" aria-controls="collapseRAG">
                            <?php echo IconHelper::icon('mdi:brain'); ?>
                            What is RAG and how does it work?
                        </button>
                    </h2>
                    <div id="collapseRAG" class="accordion-collapse collapse" aria-labelledby="headingRAG" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                RAG (Retrieval-Augmented Generation) enhances responses with context from your uploaded documents. When enabled:
                            </p>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 16px;">
                                <li>The system searches your documents for relevant content</li>
                                <li>Relevant chunks are included as context in your query</li>
                                <li>All RAG operations use <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">nomic-embed-text</code> model for embeddings</li>
                                <li>Toggle RAG on/off using the brain icon in the chat input</li>
                            </ul>
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-top: 16px;">
                                <strong style="color: var(--text-primary);">@ Mentions:</strong> Type <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">@</code> in chat to mention specific documents. RAG will only search chunks from mentioned documents.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Document-Specific Chats -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingDocChats">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocChats" aria-expanded="false" aria-controls="collapseDocChats">
                            <?php echo IconHelper::icon('mdi:file-document'); ?>
                            Can I create document-specific chat sessions?
                        </button>
                    </h2>
                    <div id="collapseDocChats" class="accordion-collapse collapse" aria-labelledby="headingDocChats" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                Absolutely! You can create focused chat sessions linked to specific documents for project or department-based conversations.
                            </p>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Click the chat icon on any document in the Documents page</li>
                                <li>A new chat session is created automatically linked to that document</li>
                                <li>All messages in that session will only use chunks from the linked document</li>
                                <li>Document-linked sessions show a document icon in the sidebar</li>
                                <li>Perfect for focused discussions about specific documents or projects</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Supported File Types -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFileTypes">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFileTypes" aria-expanded="false" aria-controls="collapseFileTypes">
                            <?php echo IconHelper::icon('mdi:file-document-multiple'); ?>
                            Which file types are supported for upload?
                        </button>
                    </h2>
                    <div id="collapseFileTypes" class="accordion-collapse collapse" aria-labelledby="headingFileTypes" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                Upload and process various document formats:
                            </p>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li><strong>Documents:</strong> PDF, DOCX, TXT, Markdown (MD), PPT, PPTX</li>
                                <li><strong>Spreadsheets:</strong> XLSX, CSV</li>
                            </ul>
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-top: 16px;">
                                <strong style="color: var(--text-primary);">Note:</strong> Old PPT format (binary) is not supported. Please convert to PPTX format.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Document Processing -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingProcessing">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProcessing" aria-expanded="false" aria-controls="collapseProcessing">
                            <?php echo IconHelper::icon('mdi:cog'); ?>
                            How does document processing work?
                        </button>
                    </h2>
                    <div id="collapseProcessing" class="accordion-collapse collapse" aria-labelledby="headingProcessing" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 12px;">
                                Documents go through automatic processing:
                            </p>
                            <ol style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li style="margin-bottom: 8px;"><strong>Upload:</strong> Drag & drop or click to browse files</li>
                                <li style="margin-bottom: 8px;"><strong>Text Extraction:</strong> Content is extracted from the file</li>
                                <li style="margin-bottom: 8px;"><strong>Chunking:</strong> Text is split into manageable chunks (1000 chars with 200 char overlap)</li>
                                <li style="margin-bottom: 8px;"><strong>Embedding:</strong> Each chunk is converted to embeddings using <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">nomic-embed-text</code></li>
                                <li style="margin-bottom: 8px;"><strong>Status Updates:</strong> Watch the status change from "processing" to "processed" in real-time</li>
                            </ol>
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-top: 16px;">
                                The system automatically polls for status updates every 3 seconds while documents are processing.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Document Actions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingDocActions">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocActions" aria-expanded="false" aria-controls="collapseDocActions">
                            <?php echo IconHelper::icon('mdi:tools'); ?>
                            What actions can I perform on documents?
                        </button>
                    </h2>
                    <div id="collapseDocActions" class="accordion-collapse collapse" aria-labelledby="headingDocActions" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li><strong>View:</strong> Click the eye icon to preview document chunks inline</li>
                                <li><strong>Rename:</strong> Click the edit icon to rename documents</li>
                                <li><strong>Start Chat:</strong> Click the chat icon to create a document-specific chat session</li>
                                <li><strong>Delete:</strong> Click the delete icon to remove documents (also deletes chunks and embeddings)</li>
                            </ul>
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-top: 16px;">
                                <strong style="color: var(--text-primary);">Search & Filter:</strong> Use the search bar and filters to quickly find documents by name, type, or status. Sort by any column by clicking column headers.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSettings">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="false" aria-controls="collapseSettings">
                            <?php echo IconHelper::icon('mdi:cog'); ?>
                            What settings are available?
                        </button>
                    </h2>
                    <div id="collapseSettings" class="accordion-collapse collapse" aria-labelledby="headingSettings" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <h3 style="color: var(--text-primary); font-size: 16px; font-weight: 600; margin: 16px 0 8px 0;">Model Management</h3>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 16px;">
                                <li>Sync models from your Ollama server</li>
                                <li>Set a default model for new chats</li>
                                <li>View available models and their sizes</li>
                            </ul>
                            <h3 style="color: var(--text-primary); font-size: 16px; font-weight: 600; margin: 16px 0 8px 0;">Date & Time Format</h3>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Select your timezone from a comprehensive list</li>
                                <li>Choose date format (Short, Medium, Long, Full)</li>
                                <li>Choose time format (Short, Medium)</li>
                                <li>Settings are saved to your browser's local storage</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- RAG Workflow -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingRAGWorkflow">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRAGWorkflow" aria-expanded="false" aria-controls="collapseRAGWorkflow">
                            <?php echo IconHelper::icon('mdi:brain'); ?>
                            How does the RAG workflow process documents?
                        </button>
                    </h2>
                    <div id="collapseRAGWorkflow" class="accordion-collapse collapse" aria-labelledby="headingRAGWorkflow" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ol style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li style="margin-bottom: 8px;"><strong>Document Upload:</strong> Upload your documents (PDF, DOCX, etc.)</li>
                                <li style="margin-bottom: 8px;"><strong>Text Extraction:</strong> Content is extracted from files</li>
                                <li style="margin-bottom: 8px;"><strong>Chunking:</strong> Text is split into overlapping chunks for better context</li>
                                <li style="margin-bottom: 8px;"><strong>Embedding Generation:</strong> Each chunk is converted to a vector embedding using <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">nomic-embed-text</code></li>
                                <li style="margin-bottom: 8px;"><strong>Storage:</strong> Embeddings are stored in the database for fast retrieval</li>
                                <li style="margin-bottom: 8px;"><strong>Query Processing:</strong> When you ask a question with RAG enabled:
                                    <ul style="padding-left: 24px; margin-top: 8px;">
                                        <li>Your question is converted to an embedding</li>
                                        <li>Similar chunks are found using cosine similarity</li>
                                        <li>Top 5 most relevant chunks are included as context</li>
                                        <li>The model generates a response using this context</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Best Practices -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingBestPractices">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBestPractices" aria-expanded="false" aria-controls="collapseBestPractices">
                            <?php echo IconHelper::icon('mdi:lightbulb'); ?>
                            What are the best practices for using RAG?
                        </button>
                    </h2>
                    <div id="collapseBestPractices" class="accordion-collapse collapse" aria-labelledby="headingBestPractices" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Upload well-structured documents for better chunking</li>
                                <li>Use descriptive filenames to easily identify documents</li>
                                <li>Wait for documents to finish processing before using RAG</li>
                                <li>Enable RAG when you need context from your documents</li>
                                <li>Disable RAG for general conversations to save processing time</li>
                                <li>Use @ mentions to reference specific documents in any chat</li>
                                <li>Create document-specific chat sessions for focused discussions</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting: Document Processing -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTroubleProcessing">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTroubleProcessing" aria-expanded="false" aria-controls="collapseTroubleProcessing">
                            <?php echo IconHelper::icon('mdi:alert-circle'); ?>
                            What if I face issues with document processing?
                        </button>
                    </h2>
                    <div id="collapseTroubleProcessing" class="accordion-collapse collapse" aria-labelledby="headingTroubleProcessing" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li><strong>Status stuck on "processing":</strong> Check if Ollama is running and <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">nomic-embed-text</code> model is installed</li>
                                <li><strong>Status shows "error":</strong> Check server error logs for details. Common issues include unsupported file format or corrupted files</li>
                                <li><strong>No chunks generated:</strong> Document may be empty or contain only images. Try a text-based document</li>
                                <li><strong>PPT files not working:</strong> Old PPT format (binary) is not supported. Convert to PPTX format</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting: RAG -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTroubleRAG">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTroubleRAG" aria-expanded="false" aria-controls="collapseTroubleRAG">
                            <?php echo IconHelper::icon('mdi:help-circle'); ?>
                            Why isn't RAG working?
                        </button>
                    </h2>
                    <div id="collapseTroubleRAG" class="accordion-collapse collapse" aria-labelledby="headingTroubleRAG" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>Ensure RAG toggle is enabled (brain icon in chat input)</li>
                                <li>Make sure you have at least one processed document</li>
                                <li>Verify <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">nomic-embed-text</code> model is installed: <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">ollama pull nomic-embed-text</code></li>
                                <li>Check Ollama API connection in Settings</li>
                                <li>Try using @ mentions to reference specific documents</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting: Model Loading -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTroubleModel">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTroubleModel" aria-expanded="false" aria-controls="collapseTroubleModel">
                            <?php echo IconHelper::icon('mdi:alert'); ?>
                            What if I see model loading errors?
                        </button>
                    </h2>
                    <div id="collapseTroubleModel" class="accordion-collapse collapse" aria-labelledby="headingTroubleModel" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8;">
                                <li>If you see "model loading" errors, the system will automatically retry up to 5 times</li>
                                <li>Wait a few moments and try again if the model is still loading</li>
                                <li>Check Ollama server status and ensure the model is available</li>
                                <li>Verify the model name is correct and exists on your Ollama server</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- NexusAI Chat Premium -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingNexusAI">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNexusAI" aria-expanded="false" aria-controls="collapseNexusAI">
                            <?php echo IconHelper::icon('mdi:rocket-launch'); ?>
                            Need more features? Check out NexusAI Chat!
                        </button>
                    </h2>
                    <div id="collapseNexusAI" class="accordion-collapse collapse" aria-labelledby="headingNexusAI" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 16px;">
                                <strong style="color: var(--accent);">Chat with Ollama</strong> is the free, open-source edition focused on local AI with Ollama. If you need enterprise features, multi-provider support, and advanced capabilities, check out <strong style="color: var(--accent);">NexusAI Chat</strong>!
                            </p>
                            <h3 style="color: var(--text-primary); font-size: 16px; font-weight: 600; margin: 16px 0 8px 0;">NexusAI Chat Premium Features:</h3>
                            <ul style="padding-left: 24px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 16px;">
                                <li><strong>Multi-Provider AI Engine:</strong> Support for DeepSeek, OpenAI, Anthropic, Gemini, Groq, HuggingFace, TogetherAI, OpenRouter, and more</li>
                                <li><strong>Advanced RAG System:</strong> Enhanced semantic search, cross-document analysis, and intelligent document linking</li>
                                <li><strong>Team Collaboration:</strong> Shared documents, chat sessions, and role-based access control</li>
                                <li><strong>Analytics & Insights:</strong> Usage tracking, cost monitoring, and performance metrics</li>
                                <li><strong>Enterprise Features:</strong> Audit logging, data encryption, compliance ready (GDPR, HIPAA, SOC2)</li>
                                <li><strong>API Access:</strong> Full REST API for system integration</li>
                                <li><strong>White-labeling:</strong> Custom branding and domain support</li>
                            </ul>
                            <div style="margin-top: 20px; padding: 16px; background: rgba(var(--accent-rgb), 0.1); border-radius: 8px; border: 1px solid rgba(var(--accent-rgb), 0.3);">
                                <p style="margin: 0; color: var(--text-primary); font-weight: 600; margin-bottom: 8px;">
                                    Ready to upgrade?
                                </p>
                                <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">
                                    Visit <a href="https://2tinteractive.com" target="_blank" style="color: var(--accent); text-decoration: none; font-weight: 600;">2tinteractive.com</a> to learn more about NexusAI Chat and explore pricing plans.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--glass-border); text-align: center;">
                <p style="color: var(--text-secondary); margin-bottom: 16px;">Need more help? Check the documentation or contact support.</p>
                <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo htmlspecialchars($companyUrl); ?>" target="_blank" style="color: var(--accent); text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <?php echo IconHelper::icon('mdi:web', '', 'font-size: 16px;'); ?>
                        Visit <?php echo htmlspecialchars($companyName); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
