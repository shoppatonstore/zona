<?php
/**
 * Question Importer Class
 * 
 * Handles importing past questions and answers from text/document files.
 * Supports formats like JAMB, WAEC, NECO past questions with separate answer keys.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Question_Importer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_import_questions', array($this, 'ajax_import_questions'));
        add_action('wp_ajax_zonatech_preview_import', array($this, 'ajax_preview_import'));
    }
    
    /**
     * Parse questions from text content
     * Format expected: 
     * - Numbered questions (e.g., "81. Question text here?")
     * - Options on separate lines (e.g., "A. option text" or "A option text")
     * 
     * @param string $content The raw text content
     * @return array Parsed questions
     */
    public function parse_questions($content) {
        $questions = array();
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Split into lines
        $lines = explode("\n", $content);
        
        $current_question = null;
        $current_question_number = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Check if line starts with a question number (e.g., "81. " or "81 ")
            if (preg_match('/^(\d+)\s*[.\)]\s*(.+)$/i', $line, $matches)) {
                // Save previous question if exists
                if ($current_question !== null && !empty($current_question['question_text'])) {
                    $questions[$current_question_number] = $current_question;
                }
                
                $current_question_number = intval($matches[1]);
                $question_text = trim($matches[2]);
                
                // Check if the question text contains an option at the end (A. B. C. D.)
                // This handles single-line format
                $question_text = $this->clean_question_text($question_text);
                
                $current_question = array(
                    'number' => $current_question_number,
                    'question_text' => $question_text,
                    'option_a' => '',
                    'option_b' => '',
                    'option_c' => '',
                    'option_d' => '',
                    'correct_answer' => ''
                );
                continue;
            }
            
            // Check if line is an option (A., B., C., D. or A), B), C), D))
            if ($current_question !== null) {
                // Match options like "A. text", "A) text", "A text", or just "A. text"
                if (preg_match('/^([A-Da-d])\s*[.\)]\s*(.+)$/i', $line, $matches)) {
                    $option_letter = strtoupper($matches[1]);
                    $option_text = trim($matches[2]);
                    
                    switch ($option_letter) {
                        case 'A':
                            $current_question['option_a'] = $option_text;
                            break;
                        case 'B':
                            $current_question['option_b'] = $option_text;
                            break;
                        case 'C':
                            $current_question['option_c'] = $option_text;
                            break;
                        case 'D':
                            $current_question['option_d'] = $option_text;
                            break;
                    }
                } else if ($current_question !== null && !empty($current_question['question_text'])) {
                    // If it's not an option and we have a current question, 
                    // it might be a continuation of the question text
                    // But only if we haven't started collecting options yet
                    if (empty($current_question['option_a'])) {
                        $current_question['question_text'] .= ' ' . $line;
                    }
                }
            }
        }
        
        // Don't forget the last question
        if ($current_question !== null && !empty($current_question['question_text'])) {
            $questions[$current_question_number] = $current_question;
        }
        
        return $questions;
    }
    
    /**
     * Parse answer key from text content
     * Format expected:
     * - "1. A", "2. B", "3. C" etc.
     * - Or tabular format: "1. D    2. A    3. C    4. D"
     * 
     * @param string $content The raw text content
     * @return array Answers indexed by question number
     */
    public function parse_answers($content) {
        $answers = array();
        
        // Normalize line endings and spaces
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Try to find answer patterns
        // Pattern 1: "1. A" or "1. D" format (with periods or without)
        // Pattern 2: Tabular format with multiple answers per line
        
        // First, try to extract all answer patterns
        preg_match_all('/(\d+)\s*[.\):]?\s*([A-Da-d])\b/i', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $question_number = intval($match[1]);
            $answer = strtoupper($match[2]);
            $answers[$question_number] = $answer;
        }
        
        return $answers;
    }
    
    /**
     * Clean question text by removing trailing option markers
     * 
     * @param string $text Question text
     * @return string Cleaned question text
     */
    private function clean_question_text($text) {
        // Remove trailing option markers that might be on the same line
        $text = preg_replace('/\s+[A-D]\s*[.\)]\s*$/', '', $text);
        return trim($text);
    }
    
    /**
     * Merge questions with answers
     * 
     * @param array $questions Parsed questions
     * @param array $answers Parsed answers
     * @return array Questions with answers filled in
     */
    public function merge_questions_with_answers($questions, $answers) {
        foreach ($questions as $number => &$question) {
            if (isset($answers[$number])) {
                $question['correct_answer'] = $answers[$number];
            }
        }
        return $questions;
    }
    
    /**
     * Import questions into database
     * 
     * @param array $questions Parsed questions
     * @param string $exam_type Exam type (jamb, waec, neco)
     * @param string $subject Subject name
     * @param int $year Year
     * @return array Result with success count and errors
     */
    public function import_to_database($questions, $exam_type, $subject, $year) {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        $success_count = 0;
        $errors = array();
        $skipped = 0;
        
        foreach ($questions as $number => $question) {
            // Validate question has required fields
            if (empty($question['question_text'])) {
                $errors[] = "Question {$number}: Missing question text";
                continue;
            }
            
            if (empty($question['option_a']) || empty($question['option_b']) || 
                empty($question['option_c']) || empty($question['option_d'])) {
                $errors[] = "Question {$number}: Missing one or more options";
                continue;
            }
            
            if (empty($question['correct_answer'])) {
                $errors[] = "Question {$number}: Missing correct answer";
                continue;
            }
            
            // Check if question already exists (by question text, exam type, subject, year)
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_questions 
                 WHERE exam_type = %s AND subject = %s AND year = %d 
                 AND question_text = %s",
                $exam_type,
                $subject,
                $year,
                $question['question_text']
            ));
            
            if ($exists > 0) {
                $skipped++;
                continue;
            }
            
            // Insert question
            $result = $wpdb->insert($table_questions, array(
                'exam_type' => $exam_type,
                'subject' => $subject,
                'year' => $year,
                'question_text' => sanitize_textarea_field($question['question_text']),
                'option_a' => sanitize_text_field($question['option_a']),
                'option_b' => sanitize_text_field($question['option_b']),
                'option_c' => sanitize_text_field($question['option_c']),
                'option_d' => sanitize_text_field($question['option_d']),
                'correct_answer' => sanitize_text_field($question['correct_answer']),
                'explanation' => ''
            ));
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = "Question {$number}: Database error - " . $wpdb->last_error;
            }
        }
        
        return array(
            'success_count' => $success_count,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_parsed' => count($questions)
        );
    }
    
    /**
     * AJAX handler for previewing import
     */
    public function ajax_preview_import() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $questions_text = isset($_POST['questions_text']) ? wp_unslash($_POST['questions_text']) : '';
        $answers_text = isset($_POST['answers_text']) ? wp_unslash($_POST['answers_text']) : '';
        
        if (empty($questions_text)) {
            wp_send_json_error(array('message' => 'Questions text is required.'));
        }
        
        $questions = $this->parse_questions($questions_text);
        
        if (!empty($answers_text)) {
            $answers = $this->parse_answers($answers_text);
            $questions = $this->merge_questions_with_answers($questions, $answers);
        }
        
        if (empty($questions)) {
            wp_send_json_error(array('message' => 'No questions could be parsed from the provided text.'));
        }
        
        // Convert to simple array for JSON
        $preview_questions = array_values($questions);
        
        wp_send_json_success(array(
            'questions' => $preview_questions,
            'count' => count($preview_questions),
            'message' => count($preview_questions) . ' questions parsed successfully.'
        ));
    }
    
    /**
     * AJAX handler for importing questions
     */
    public function ajax_import_questions() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $questions_text = isset($_POST['questions_text']) ? wp_unslash($_POST['questions_text']) : '';
        $answers_text = isset($_POST['answers_text']) ? wp_unslash($_POST['answers_text']) : '';
        $exam_type = sanitize_text_field($_POST['exam_type'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        
        // Validation
        if (empty($questions_text)) {
            wp_send_json_error(array('message' => 'Questions text is required.'));
        }
        
        if (empty($exam_type)) {
            wp_send_json_error(array('message' => 'Exam type is required.'));
        }
        
        if (empty($subject)) {
            wp_send_json_error(array('message' => 'Subject is required.'));
        }
        
        if ($year < 1990 || $year > intval(date('Y')) + 1) {
            wp_send_json_error(array('message' => 'Invalid year. Please enter a year between 1990 and ' . (date('Y') + 1) . '.'));
        }
        
        // Parse questions
        $questions = $this->parse_questions($questions_text);
        
        if (empty($questions)) {
            wp_send_json_error(array('message' => 'No questions could be parsed from the provided text. Please check the format.'));
        }
        
        // Parse and merge answers if provided
        if (!empty($answers_text)) {
            $answers = $this->parse_answers($answers_text);
            $questions = $this->merge_questions_with_answers($questions, $answers);
        }
        
        // Import to database
        $result = $this->import_to_database($questions, $exam_type, $subject, $year);
        
        $message = sprintf(
            'Import complete: %d questions imported, %d skipped (duplicates), %d errors.',
            $result['success_count'],
            $result['skipped'],
            count($result['errors'])
        );
        
        if ($result['success_count'] > 0) {
            wp_send_json_success(array(
                'message' => $message,
                'details' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No questions were imported. ' . (!empty($result['errors']) ? implode('; ', array_slice($result['errors'], 0, 5)) : ''),
                'details' => $result
            ));
        }
    }
    
    /**
     * Extract text from uploaded file
     * 
     * @param array $file $_FILES array element
     * @return string|WP_Error Extracted text or error
     */
    public function extract_text_from_file($file) {
        $file_type = wp_check_filetype($file['name']);
        $allowed_types = array('txt', 'text');
        
        if (!in_array(strtolower($file_type['ext']), $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Only .txt files are allowed.');
        }
        
        // Read file content
        $content = file_get_contents($file['tmp_name']);
        
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read file content.');
        }
        
        // Convert encoding if needed
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        
        return $content;
    }
}

// Initialize
ZonaTech_Question_Importer::get_instance();
