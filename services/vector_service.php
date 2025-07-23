<?php
/**
 * Vector Service
 * Handles all vector operations including similarity calculations and RAG document retrieval
 */

require_once __DIR__ . '/database_service.php';

class VectorService {
    
    /**
     * Calculate cosine similarity between two vectors
     * 
     * @param array $vector1 First vector
     * @param array $vector2 Second vector
     * @return float Similarity score between 0 and 1
     */
    public static function calculate_cosine_similarity($vector1, $vector2) {
        if (count($vector1) !== count($vector2)) {
            return 0;
        }
        
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vector1); $i++) {
            $dot_product += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] * $vector1[$i];
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Retrieve documents using vector similarity for RAG
     * 
     * @param array $query_embedding The query vector embedding
     * @param int $limit Maximum number of documents to return
     * @return array Array of documents with similarity scores
     */
    public static function retrieve_documents_by_vector($query_embedding, $limit = null) {
        if ($limit === null) {
            $limit = defined('TOP_K_DOCUMENTS') ? TOP_K_DOCUMENTS : 5;
        }
        
        try {
            if (!DatabaseService::is_available()) {
                error_log("retrieve_documents_by_vector: No database connection");
                return [];
            }
            
            // Validate query embedding
            if (!is_array($query_embedding) || empty($query_embedding)) {
                error_log("retrieve_documents_by_vector: Invalid query embedding");
                return [];
            }
            
            // Check if documents table exists
            if (!DatabaseService::table_exists('documents')) {
                error_log("retrieve_documents_by_vector: Documents table does not exist");
                return [];
            }
            
            // Get all documents with embeddings
            $stmt = DatabaseService::execute_query("
                SELECT id, content, metadata, embedding 
                FROM documents 
                WHERE embedding IS NOT NULL 
                ORDER BY created_at DESC 
                LIMIT 100
            ");
            
            if (!$stmt) {
                error_log("retrieve_documents_by_vector: Failed to execute query");
                return [];
            }
            
            $documents = $stmt->fetchAll();
            
            if (empty($documents)) {
                error_log("retrieve_documents_by_vector: No documents found in database");
                return [];
            }
            
            // Calculate similarities
            $similarities = [];
            foreach ($documents as $doc) {
                $doc_embedding = json_decode($doc['embedding'], true);
                if (is_array($doc_embedding)) {
                    $similarity = self::calculate_cosine_similarity($query_embedding, $doc_embedding);
                    $similarities[] = [
                        'id' => $doc['id'],
                        'content' => $doc['content'],
                        'metadata' => $doc['metadata'],
                        'similarity' => $similarity
                    ];
                }
            }
            
            // Sort by similarity descending
            usort($similarities, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });
            
            // Return top results
            return array_slice($similarities, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Document retrieval error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search documents by text similarity (fallback when no embeddings available)
     * 
     * @param string $query_text The search query text
     * @param int $limit Maximum number of documents to return
     * @return array Array of documents
     */
    public static function search_documents_by_text($query_text, $limit = null) {
        if ($limit === null) {
            $limit = defined('TOP_K_DOCUMENTS') ? TOP_K_DOCUMENTS : 5;
        }
        
        try {
            if (!DatabaseService::is_available()) {
                return [];
            }
            
            if (!DatabaseService::table_exists('documents')) {
                return [];
            }
            
            // Use full-text search if available, otherwise use LIKE
            $stmt = DatabaseService::execute_query("
                SELECT id, content, metadata 
                FROM documents 
                WHERE content LIKE :query 
                ORDER BY created_at DESC 
                LIMIT :limit
            ", [
                'query' => '%' . $query_text . '%',
                'limit' => $limit
            ]);
            
            if (!$stmt) {
                return [];
            }
            
            $documents = $stmt->fetchAll();
            
            // Add dummy similarity score for consistency
            return array_map(function($doc) {
                return [
                    'id' => $doc['id'],
                    'content' => $doc['content'],
                    'metadata' => $doc['metadata'],
                    'similarity' => 0.5 // Default similarity for text search
                ];
            }, $documents);
            
        } catch (Exception $e) {
            error_log("Text search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate vector embedding format
     * 
     * @param mixed $embedding The embedding to validate
     * @return bool True if valid embedding
     */
    public static function is_valid_embedding($embedding) {
        return is_array($embedding) && !empty($embedding) && is_numeric($embedding[0]);
    }
    
    /**
     * Normalize vector to unit length
     * 
     * @param array $vector The vector to normalize
     * @return array Normalized vector
     */
    public static function normalize_vector($vector) {
        $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector)));
        
        if ($magnitude == 0) {
            return $vector;
        }
        
        return array_map(function($x) use ($magnitude) { return $x / $magnitude; }, $vector);
    }
}

