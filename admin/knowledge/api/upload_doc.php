<?php
/** 
 * Upload a PDF/TXT/MD or raw text and index it into MariaDB
 * Updated for MariaDB 10.6 
 */

// Start output buffering to prevent header issues
ob_start();

// Include the database connection
require_once __DIR__ . '/../../../config.php';

// Clear any output that might have been generated
ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

/* helpers */
function generateEmbedding(string $text): array {
    $apiKey = OPENAI_API_KEY;
    
    if (!$apiKey) {
        error_log("ERROR: OPENAI_API_KEY not configured");
        throw new Exception("OpenAI API key not configured.");
    }
    
    $data = [
        'model' => OPENAI_EMBED_MODEL,
        'input' => $text
    ];
    
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Authorization: Bearer $apiKey"
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(OPENAI_EMBED_URL, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        error_log("Failed to generate embedding: " . ($error['message'] ?? 'Unknown error'));
        throw new Exception('Failed to generate embedding: ' . ($error['message'] ?? 'API call failed'));
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['data'][0]['embedding'])) {
        error_log("Invalid embedding response: " . json_encode($result));
        throw new Exception('Invalid embedding response from OpenAI');
    }
    
    return $result['data'][0]['embedding'];
}

function extractPdfWithGPT4(string $pdfPath): ?string {
    error_log("Starting GPT-4 Vision PDF extraction for: $pdfPath");
    
    $apiKey = OPENAI_API_KEY;
    if (!$apiKey) {
        error_log("ERROR: OPENAI_API_KEY not configured");
        throw new Exception("OpenAI API key not configured");
    }
    
    try {
        // Check if Imagick is available
        if (class_exists('Imagick')) {
            error_log("Using Imagick to convert PDF to images");
            
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath);
            
            $pageCount = $imagick->getNumberImages();
            error_log("PDF has $pageCount pages");
            
            $allText = '';
            
            // Process each page
            for ($i = 0; $i < min($pageCount, 5); $i++) { // Limit to first 5 pages for cost
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');
                $imageData = $imagick->getImageBlob();
                $base64Image = base64_encode($imageData);
                
                error_log("Processing page " . ($i + 1) . " of $pageCount");
                
                $data = [
                    'model' => OPENAI_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Extract all text from this image. Return only the text content, no descriptions.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:image/png;base64,$base64Image"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 2000
                ];
                
                $options = [
                    'http' => [
                        'header' => [
                            "Content-Type: application/json",
                            "Authorization: Bearer $apiKey"
                        ],
                        'method' => 'POST',
                        'content' => json_encode($data),
                        'timeout' => 30
                    ]
                ];
                
                $context = stream_context_create($options);
                $response = @file_get_contents(OPENAI_API_URL, false, $context);
                
                if ($response === false) {
                    $error = error_get_last();
                    error_log("Failed to call OpenAI API: " . ($error['message'] ?? 'Unknown error'));
                    continue;
                }
                
                $result = json_decode($response, true);
                
                if (isset($result['choices'][0]['message']['content'])) {
                    $allText .= $result['choices'][0]['message']['content'] . "\n\n";
                } else {
                    error_log("Unexpected API response: " . json_encode($result));
                }
            }
            
            $imagick->destroy();
            
            if (!empty($allText)) {
                error_log("Successfully extracted " . strlen($allText) . " characters from PDF");
                return $allText;
            }
            
        } else {
            error_log("Imagick not available, trying alternative method");
            
            // Alternative: Try to use system commands to convert PDF to text
            $pdftotext = @shell_exec('which pdftotext 2>/dev/null');
            if ($pdftotext) {
                $text = @shell_exec("pdftotext -enc UTF-8 " . escapeshellarg($pdfPath) . " -");
                if ($text) {
                    return $text;
                }
            }
            
            // Last resort: try to extract any readable text from the PDF binary
            $pdfContent = file_get_contents($pdfPath);
            preg_match_all('/\(([^\)]+)\)/', $pdfContent, $matches);
            if (!empty($matches[1])) {
                $text = implode(' ', $matches[1]);
                error_log("Extracted basic text from PDF binary: " . strlen($text) . " chars");
                return $text;
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in PDF extraction: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    return null;
}

function chunkText(string $txt, int $tok = 500): array {
    $chars = max(200, $tok * 4);
    $chunks = [];
    for ($i = 0; $i < mb_strlen($txt); $i += $chars) {
        $chunk = mb_substr($txt, $i, $chars + 200);
        if (!empty(trim($chunk))) {
            $chunks[] = $chunk;
        }
    }
    return $chunks;
}

try {
    // Enable detailed error logging for debugging
    error_log("=== Upload Doc Debug Start ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("FILES data: " . json_encode($_FILES));
    
    /* ─ decide source: file OR textarea ───────────────────────────── */
    $text = null;
    $filename = '';
    $mime = '';
    $maxChunk = isset($_POST['max_chunk']) ? (int)$_POST['max_chunk'] : 500;

    /* --- case A: raw textarea text -------------------------------- */
    if (isset($_POST['raw_text']) && trim($_POST['raw_text']) !== '') {
        $text = trim($_POST['raw_text']);
        $filename = 'manual_' . date('Ymd_His') . '.txt';
        $mime = 'text/plain';
    }

    /* --- case B: file upload -------------------------------------- */
    if (!$text && isset($_FILES['doc']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['doc']['tmp_name'];
        $filename = basename($_FILES['doc']['name']);
        $mime = function_exists('mime_content_type') ? mime_content_type($tmp)
            : (function($f) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                $m = finfo_file($fi, $f);
                finfo_close($fi);
                return $m;
            })($tmp);

        $raw = file_get_contents($tmp);
        switch (true) {
            case str_starts_with($mime, 'text/'):
                $text = $raw;
                break;
            case $mime === 'application/pdf':
                error_log("Processing PDF file: $filename");
                
                // Try pdftotext first (faster if available)
                $pdftxt = @shell_exec('where pdftotext 2>nul') ?: @shell_exec('which pdftotext');
                error_log("pdftotext path: " . ($pdftxt ? trim($pdftxt) : "not found"));
                
                if ($pdftxt && trim($pdftxt)) {
                    $cmd = escapeshellcmd(trim($pdftxt)) . ' -enc UTF-8 ' . escapeshellarg($tmp) . ' -';
                    error_log("Running command: $cmd");
                    $text = @shell_exec($cmd);
                    error_log("pdftotext result length: " . ($text ? strlen($text) : "null"));
                }
                
                // If pdftotext failed or not available, use GPT-4o mini
                if (empty($text) || trim($text) === '') {
                    error_log("pdftotext not available or failed, using GPT-4o mini for PDF extraction");
                    try {
                        $text = extractPdfWithGPT4($tmp);
                        error_log("GPT-4o mini extraction result length: " . ($text ? strlen($text) : "null"));
                    } catch (Exception $e) {
                        error_log("GPT-4o mini extraction failed: " . $e->getMessage());
                        throw $e;
                    }
                }
                break;
        }
        $text = $text ?: strip_tags($raw);
        error_log("Final text extraction result: " . ($text ? "Got " . strlen($text) . " chars" : "NULL/empty"));
    }

    /* ─ abort if nothing ─────────────────────────────────────────── */
    if (empty($text) || !is_string($text) || trim($text) === '') {
        error_log("ERROR: No text extracted from document");
        error_log("Text var type: " . gettype($text));
        error_log("Text value: " . var_export($text, true));
        http_response_code(400);
        echo json_encode(['error' => 'No text extracted or provided. Please ensure the document contains readable text.']);
        exit;
    }
    $text = preg_replace('/\R+/u', "\n", $text);
    error_log("Text after cleanup: " . strlen($text) . " chars");

    /* ─ get database connection ─────────────────────────────────── */
    $pdo = getDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    /* ─ index: delete old version if same filename ───────────────── */
    $stmt = $pdo->prepare("DELETE FROM documents WHERE JSON_EXTRACT(metadata, '$.filename') = :f");
    $stmt->execute(['f' => $filename]);

    /* ─ insert chunks ─────────────────────────────────────────────── */
    error_log("Starting chunk insertion for file: $filename");
    $stmt = $pdo->prepare("INSERT INTO documents(content, metadata, embedding) VALUES(:c, :m, :e)");
    $idx = 0;
    
    $chunks = chunkText($text, $maxChunk);
    error_log("Created " . count($chunks) . " chunks");
    
    foreach ($chunks as $c) {
        error_log("Processing chunk $idx, length: " . strlen($c));
        
        try {
            $embedding = generateEmbedding($c);
            error_log("Generated embedding for chunk $idx");
            
            $metadata = [
                'filename' => $filename,
                'chunk' => $idx,
                'mime' => $mime,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $stmt->execute([
                'c' => $c,
                'm' => json_encode($metadata),
                'e' => json_encode($embedding)
            ]);
            error_log("Inserted chunk $idx into database");
            
        } catch (Exception $e) {
            error_log("Error processing chunk $idx: " . $e->getMessage());
            throw $e;
        }
        
        $idx++;
    }
    
    echo json_encode([
        'status' => 'ok',
        'filename' => $filename,
        'chunks' => $idx
    ]);

} catch (Exception $e) {
    error_log("=== Upload Doc Error ===");
    error_log("Error message: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("=== End Error ===");
    
    http_response_code(500);
    
    // In development, show detailed error
    $errorResponse = [
        'error' => 'Failed to process document: ' . $e->getMessage(),
        'details' => [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ];
    
    echo json_encode($errorResponse);
}

// Ensure clean output
if (ob_get_level()) {
    ob_end_flush();
}
