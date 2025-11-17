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
            $sql = "
                SELECT ec.id, ec.chunk_id, ec.embedding, ec.model_name, dc.content, dc.document_id, d.original_filename
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
            
            $scoredChunks = [];
            foreach ($chunks as $chunk) {
                $chunkEmbedding = json_decode($chunk['embedding'], true);
                $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding);
                
                $scoredChunks[] = [
                    'chunk_id' => $chunk['chunk_id'],
                    'content' => $chunk['content'],
                    'document_id' => $chunk['document_id'],
                    'filename' => $chunk['original_filename'],
                    'similarity' => $similarity
                ];
            }
            
            usort($scoredChunks, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            return array_slice($scoredChunks, 0, $limit);
            
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
            $stmt = $this->db->query("
                SELECT ec.id, ec.chunk_id, ec.embedding, ec.model_name, dc.content, dc.document_id, d.original_filename
                FROM embeddings ec
                JOIN document_chunks dc ON ec.chunk_id = dc.id
                JOIN documents d ON dc.document_id = d.id
                WHERE d.status = 'processed'
                LIMIT 1000
            ");
            
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parallel similarity computation (using array_map for better performance)
            $scoredChunks = array_map(function($chunk) use ($queryEmbedding) {
                $chunkEmbedding = json_decode($chunk['embedding'], true);
                $similarity = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding);
                
                return [
                    'chunk_id' => $chunk['chunk_id'],
                    'content' => $chunk['content'],
                    'document_id' => $chunk['document_id'],
                    'filename' => $chunk['original_filename'],
                    'similarity' => $similarity
                ];
            }, $chunks);
            
            // Sort and limit
            usort($scoredChunks, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            $result = array_slice($scoredChunks, 0, $limit);
            
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
