<?php

namespace App\Services;

use App\Database\Database;
use PDO;
use Exception;

class RAGService
{
    private $db;
    private $documentService;
    private $embeddingService;

    public function __construct($embeddingService)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->documentService = new DocumentService();
        $this->embeddingService = $embeddingService;
    }

    public function uploadDocument($file)
    {
        try {
            $documentData = $this->documentService->processDocument($file);
            
            $stmt = $this->db->prepare("
                INSERT INTO documents (filename, original_filename, file_type, file_size, file_path, status)
                VALUES (:filename, :original_filename, :file_type, :file_size, :file_path, 'processing')
            ");
            
            $stmt->execute([
                ':filename' => $documentData['filename'],
                ':original_filename' => $documentData['original_filename'],
                ':file_type' => $documentData['file_type'],
                ':file_size' => $documentData['file_size'],
                ':file_path' => $documentData['file_path']
            ]);
            
            $documentId = $this->db->lastInsertId();
            
            // Process chunks and embeddings in background
            $this->processDocumentChunks($documentId, $documentData['content']);
            
            return $documentId;
        } catch (Exception $e) {
            error_log("Document upload failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function processDocumentChunks($documentId, $content)
    {
        try {
            $chunks = $this->documentService->chunkText($content);
            
            foreach ($chunks as $index => $chunk) {
                $stmt = $this->db->prepare("
                    INSERT INTO document_chunks (document_id, chunk_index, content, token_count)
                    VALUES (:document_id, :chunk_index, :content, :token_count)
                ");
                
                $stmt->execute([
                    ':document_id' => $documentId,
                    ':chunk_index' => $index,
                    ':content' => $chunk,
                    ':token_count' => $this->estimateTokenCount($chunk)
                ]);
                
                $chunkId = $this->db->lastInsertId();
                
                // Generate embedding
                try {
                    $embedding = $this->embeddingService->generateEmbedding($chunk);
                    $this->storeEmbedding($chunkId, $embedding);
                } catch (Exception $e) {
                    error_log("Failed to generate embedding for chunk {$chunkId}: " . $e->getMessage());
                }
            }
            
            // Update document status
            $stmt = $this->db->prepare("
                UPDATE documents 
                SET status = 'processed', processed_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $documentId]);
            
        } catch (Exception $e) {
            error_log("Chunk processing failed: " . $e->getMessage());
            
            $stmt = $this->db->prepare("UPDATE documents SET status = 'error' WHERE id = :id");
            $stmt->execute([':id' => $documentId]);
        }
    }

    private function storeEmbedding($chunkId, $embedding)
    {
        $stmt = $this->db->prepare("
            INSERT INTO embeddings (chunk_id, model_name, embedding)
            VALUES (:chunk_id, :model_name, :embedding)
        ");
        
        $stmt->execute([
            ':chunk_id' => $chunkId,
            ':model_name' => $this->embeddingService->defaultModel,
            ':embedding' => json_encode($embedding)
        ]);
    }

    public function retrieveRelevantChunks($query, $limit = 5, $documentIds = null)
    {
        try {
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
            
            // Build query with optional document filter
            // ⚡ Bolt: Fetching large string content during the initial nearest-neighbor search causes massive memory/IO overhead.
            // Phase 1 only fetches chunk_id and embedding.
            $sql = "
                SELECT ec.chunk_id, ec.embedding
                FROM embeddings ec
                JOIN document_chunks dc ON ec.chunk_id = dc.id
                JOIN documents d ON dc.document_id = d.id
                WHERE d.status = 'processed'
            ";
            
            $params = [];
            if ($documentIds && is_array($documentIds) && !empty($documentIds)) {
                $placeholders = implode(',', array_fill(0, count($documentIds), '?'));
                $sql .= " AND d.id IN ($placeholders)";
                $params = $documentIds;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ⚡ Bolt: Using SplPriorityQueue instead of sorting all elements reduces time complexity from O(N log N) to O(N log K)
            // and significantly reduces memory usage since we only store the top K items.
            $queue = new \SplPriorityQueue();
            $queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

            // ⚡ Bolt: Pre-calculate the norm of the query embedding to avoid 1536 redundant multiplications/additions per chunk
            $queryNorm = 0;
            foreach ($queryEmbedding as $val) {
                $queryNorm += $val * $val;
            }

            foreach ($chunks as $chunk) {
                // ⚡ Bolt: Fast parsing of flat JSON arrays.
                $str = substr($chunk['embedding'], 1, -1);
                $chunkEmbedding = explode(',', $str);

                $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding, $queryNorm);
                
                $item = [
                    'chunk_id' => $chunk['chunk_id'],
                    'similarity' => $similarity
                ];

                // We use negative priority because SplPriorityQueue keeps largest elements at the top,
                // and we want to pop the smallest element when the queue exceeds the limit.
                if ($queue->count() < $limit) {
                    $queue->insert($item, -$similarity);
                } elseif ($similarity > $queue->top()['similarity']) {
                    $queue->insert($item, -$similarity);
                    if ($queue->count() > $limit) {
                        $queue->extract(); // Remove the smallest element
                    }
                }
            }
            
            // Extract the top K elements (they come out smallest first, so we reverse)
            $topItems = [];
            while (!$queue->isEmpty()) {
                $topItems[] = $queue->extract();
            }
            $topItems = array_reverse($topItems);

            if (empty($topItems)) {
                return [];
            }

            // ⚡ Bolt: Phase 2 fetches the heavy content only for the top K matched chunks.
            $topChunkIds = array_column($topItems, 'chunk_id');
            $placeholders = implode(',', array_fill(0, count($topChunkIds), '?'));
            $sqlPhase2 = "
                SELECT ec.chunk_id, dc.content, dc.document_id, d.original_filename
                FROM embeddings ec
                JOIN document_chunks dc ON ec.chunk_id = dc.id
                JOIN documents d ON dc.document_id = d.id
                WHERE ec.chunk_id IN ($placeholders)
            ";
            $stmtPhase2 = $this->db->prepare($sqlPhase2);
            $stmtPhase2->execute($topChunkIds);
            $detailedChunks = $stmtPhase2->fetchAll(PDO::FETCH_ASSOC);

            // Map the details back into the sorted results
            $detailsMap = [];
            foreach ($detailedChunks as $detail) {
                $detailsMap[$detail['chunk_id']] = $detail;
            }

            $result = [];
            foreach ($topItems as $item) {
                $chunkId = $item['chunk_id'];
                if (isset($detailsMap[$chunkId])) {
                    $result[] = [
                        'chunk_id' => $chunkId,
                        'content' => $detailsMap[$chunkId]['content'],
                        'document_id' => $detailsMap[$chunkId]['document_id'],
                        'filename' => $detailsMap[$chunkId]['original_filename'],
                        'similarity' => $item['similarity']
                    ];
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Chunk retrieval failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Optimized retrieval with caching and better query performance
     */
    public function retrieveRelevantChunksOptimized($query, $limit = 5)
    {
        try {
            // Check cache first (simple in-memory cache using query hash)
            $queryHash = md5($query);
            static $cache = [];
            if (isset($cache[$queryHash])) {
                return $cache[$queryHash];
            }
            
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
            
            // Optimized query with LIMIT to reduce memory usage
            // Use vector similarity approximation if available, otherwise fetch all and compute
            // ⚡ Bolt: Phase 1 only fetches chunk_id and embedding.
            $stmt = $this->db->query("
                SELECT ec.chunk_id, ec.embedding
                FROM embeddings ec
                JOIN document_chunks dc ON ec.chunk_id = dc.id
                JOIN documents d ON dc.document_id = d.id
                WHERE d.status = 'processed'
                LIMIT 1000
            ");
            
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ⚡ Bolt: Using SplPriorityQueue instead of array_map + usort + array_slice reduces
            // time complexity from O(N log N) to O(N log K) and avoids allocating a large intermediate array.
            $queue = new \SplPriorityQueue();
            $queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

            // ⚡ Bolt: Pre-calculate the norm of the query embedding to avoid 1536 redundant multiplications/additions per chunk
            $queryNorm = 0;
            foreach ($queryEmbedding as $val) {
                $queryNorm += $val * $val;
            }

            foreach ($chunks as $chunk) {
                // ⚡ Bolt: Fast parsing of flat JSON arrays.
                $str = substr($chunk['embedding'], 1, -1);
                $chunkEmbedding = explode(',', $str);

                $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding, $queryNorm);
                
                $item = [
                    'chunk_id' => $chunk['chunk_id'],
                    'similarity' => $similarity
                ];

                if ($queue->count() < $limit) {
                    $queue->insert($item, -$similarity);
                } elseif ($similarity > $queue->top()['similarity']) {
                    $queue->insert($item, -$similarity);
                    if ($queue->count() > $limit) {
                        $queue->extract();
                    }
                }
            }
            
            $topItems = [];
            while (!$queue->isEmpty()) {
                $topItems[] = $queue->extract();
            }
            $topItems = array_reverse($topItems);

            $result = [];
            if (!empty($topItems)) {
                // ⚡ Bolt: Phase 2 fetches the heavy content only for the top K matched chunks.
                $topChunkIds = array_column($topItems, 'chunk_id');
                $placeholders = implode(',', array_fill(0, count($topChunkIds), '?'));
                $sqlPhase2 = "
                    SELECT ec.chunk_id, dc.content, dc.document_id, d.original_filename
                    FROM embeddings ec
                    JOIN document_chunks dc ON ec.chunk_id = dc.id
                    JOIN documents d ON dc.document_id = d.id
                    WHERE ec.chunk_id IN ($placeholders)
                ";
                $stmtPhase2 = $this->db->prepare($sqlPhase2);
                $stmtPhase2->execute($topChunkIds);
                $detailedChunks = $stmtPhase2->fetchAll(PDO::FETCH_ASSOC);

                $detailsMap = [];
                foreach ($detailedChunks as $detail) {
                    $detailsMap[$detail['chunk_id']] = $detail;
                }

                foreach ($topItems as $item) {
                    $chunkId = $item['chunk_id'];
                    if (isset($detailsMap[$chunkId])) {
                        $result[] = [
                            'chunk_id' => $chunkId,
                            'content' => $detailsMap[$chunkId]['content'],
                            'document_id' => $detailsMap[$chunkId]['document_id'],
                            'filename' => $detailsMap[$chunkId]['original_filename'],
                            'similarity' => $item['similarity']
                        ];
                    }
                }
            }
            
            // Cache result (limit cache size)
            if (count($cache) > 100) {
                array_shift($cache); // Remove oldest
            }
            $cache[$queryHash] = $result;
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Optimized chunk retrieval failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Smart model selection based on query complexity
     */
    public function selectOptimalModel($defaultModel, $query, $availableModels = [])
    {
        // Simple heuristics for model selection
        $queryLength = strlen($query);
        $wordCount = str_word_count($query);
        
        // For short, simple queries, prefer faster/smaller models
        if ($wordCount < 10 && $queryLength < 100) {
            $fastModels = ['tinyllama', 'phi3', 'mistral:7b'];
            foreach ($fastModels as $fastModel) {
                if (in_array($fastModel, $availableModels) || empty($availableModels)) {
                    return $fastModel;
                }
            }
        }
        
        // For complex queries, use default or larger models
        return $defaultModel;
    }

    private function estimateTokenCount($text)
    {
        return (int) ceil(strlen($text) / 4);
    }

    public function getDocuments()
    {
        $stmt = $this->db->query("
            SELECT d.*, 
                   COUNT(dc.id) as chunk_count
            FROM documents d
            LEFT JOIN document_chunks dc ON d.id = dc.document_id
            GROUP BY d.id
            ORDER BY d.uploaded_at DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDocument($documentId)
    {
        // Get document info before deletion to delete physical file
        $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE id = :id");
        $stmt->execute([':id' => $documentId]);
        $document = $stmt->fetch();
        
        // Delete from database (cascade will delete chunks and embeddings automatically)
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = :id");
        $result = $stmt->execute([':id' => $documentId]);
        
        // Delete physical file if it exists
        if ($document && !empty($document['file_path']) && file_exists($document['file_path'])) {
            @unlink($document['file_path']);
        }
        
        return $result;
    }

    public function getDocumentPreview($documentId, $limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT d.*, 
                   COUNT(dc.id) as chunk_count
            FROM documents d
            LEFT JOIN document_chunks dc ON d.id = dc.document_id
            WHERE d.id = :id
            GROUP BY d.id
        ");
        $stmt->execute([':id' => $documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            return null;
        }
        
        // Get chunks for preview
        $stmt = $this->db->prepare("
            SELECT chunk_index, content, token_count
            FROM document_chunks
            WHERE document_id = :document_id
            ORDER BY chunk_index ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'document' => $document,
            'chunks' => $chunks,
            'total_chunks' => (int)$document['chunk_count']
        ];
    }
}
