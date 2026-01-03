<?php
/**
 * Question Importer Class
 * 
 * Handles importing past questions and answers from text/document files.
 * Supports formats like JAMB, WAEC, NECO past questions with separate answer keys.
 * Supports PDF, TXT, and DOCX file uploads with automatic text extraction.
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
        add_action('wp_ajax_zonatech_upload_file', array($this, 'ajax_upload_file'));
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
        
        // Clean up common PDF artifacts
        $content = $this->clean_pdf_text($content);
        
        // Split into lines
        $lines = explode("\n", $content);
        
        $current_question = null;
        $current_question_number = null;
        $collecting_option = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Check if line starts with a question number (e.g., "81. " or "81 " or "81)")
            // Also handle cases where the number is at start of line possibly after whitespace
            if (preg_match('/^(\d{1,3})\s*[.\):\s]\s*(.*)$/i', $line, $matches)) {
                $potential_number = intval($matches[1]);
                $rest_of_line = trim($matches[2]);
                
                // Verify this looks like a question (has some text and is a reasonable number)
                // Questions usually start from 1 and shouldn't jump by more than 100
                $is_likely_question = (
                    strlen($rest_of_line) > 5 && 
                    $potential_number >= 1 && 
                    $potential_number <= 500 &&
                    // Make sure it's not just an option that starts with a number
                    !preg_match('/^[A-Ea-e]\s*[.\)]/i', $rest_of_line)
                );
                
                if ($is_likely_question) {
                    // Save previous question if exists
                    if ($current_question !== null && !empty($current_question['question_text'])) {
                        $questions[$current_question_number] = $current_question;
                    }
                    
                    $current_question_number = $potential_number;
                    
                    // Check if options are inline with the question (common in CSV format)
                    // Pattern: "Question text A. option1 B. option2 C. option3 D. option4"
                    $inline_options = $this->extract_inline_options($rest_of_line);
                    
                    if ($inline_options !== null) {
                        // Question has inline options
                        $current_question = array(
                            'number' => $current_question_number,
                            'question_text' => $inline_options['question_text'],
                            'option_a' => $inline_options['option_a'],
                            'option_b' => $inline_options['option_b'],
                            'option_c' => $inline_options['option_c'],
                            'option_d' => $inline_options['option_d'],
                            'option_e' => isset($inline_options['option_e']) ? $inline_options['option_e'] : '',
                            'correct_answer' => ''
                        );
                        $collecting_option = null;
                    } else {
                        // Regular format - question on its own line
                        $question_text = $this->clean_question_text($rest_of_line);
                        
                        $current_question = array(
                            'number' => $current_question_number,
                            'question_text' => $question_text,
                            'option_a' => '',
                            'option_b' => '',
                            'option_c' => '',
                            'option_d' => '',
                            'option_e' => '',
                            'correct_answer' => ''
                        );
                        $collecting_option = null;
                    }
                    continue;
                }
            }
            
            // Check if line is an option (A., B., C., D., E. or A), B), C), D), E))
            if ($current_question !== null) {
                // Match options like "A. text", "A) text", "A text", or just "A. text"
                // Now including option E for 5-option questions
                if (preg_match('/^([A-Ea-e])\s*[.\)]\s*(.*)$/i', $line, $matches)) {
                    $option_letter = strtoupper($matches[1]);
                    $option_text = trim($matches[2]);
                    
                    switch ($option_letter) {
                        case 'A':
                            $current_question['option_a'] = $option_text;
                            $collecting_option = 'option_a';
                            break;
                        case 'B':
                            $current_question['option_b'] = $option_text;
                            $collecting_option = 'option_b';
                            break;
                        case 'C':
                            $current_question['option_c'] = $option_text;
                            $collecting_option = 'option_c';
                            break;
                        case 'D':
                            $current_question['option_d'] = $option_text;
                            $collecting_option = 'option_d';
                            break;
                        case 'E':
                            $current_question['option_e'] = $option_text;
                            $collecting_option = 'option_e';
                            break;
                    }
                } else if ($current_question !== null && !empty($current_question['question_text'])) {
                    // If it's not an option and we have a current question
                    if (empty($current_question['option_a'])) {
                        // Still collecting question text
                        $current_question['question_text'] .= ' ' . $line;
                    } else if ($collecting_option !== null && !empty($current_question[$collecting_option])) {
                        // Continue collecting the current option (multi-line option)
                        $current_question[$collecting_option] .= ' ' . $line;
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
     * Extract options that appear inline with question text
     * Handles format like: "Question text A. opt1 B. opt2 C. opt3 D. opt4"
     * 
     * @param string $text The text containing question and inline options
     * @return array|null Array with question_text and options, or null if no inline options found
     */
    private function extract_inline_options($text) {
        // Look for pattern where A., B., C., D. (and optionally E.) appear in the same text
        // Check if we have at least A, B, C, D options inline
        if (!preg_match('/\b[Aa]\s*[.\)]/i', $text) || 
            !preg_match('/\b[Bb]\s*[.\)]/i', $text) || 
            !preg_match('/\b[Cc]\s*[.\)]/i', $text)) {
            return null; // Not enough options found inline
        }
        
        // Extract using regex - find where each option starts
        // Pattern to match option markers: "A.", "B.", "C.", "D.", "E." (case insensitive)
        $pattern = '/\s+([Aa])\s*[.\)]\s*/';
        
        // Find the position of option A
        if (!preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        
        $question_text = trim(substr($text, 0, $match[0][1]));
        $options_text = substr($text, $match[0][1]);
        
        // Now parse individual options from the options text
        // Split by option markers but keep the markers
        $options = array(
            'question_text' => $question_text,
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'option_e' => ''
        );
        
        // Extract each option
        // Pattern: letter followed by . or ) then text until next option letter or end
        if (preg_match('/[Aa]\s*[.\)]\s*(.*?)(?=\s+[Bb]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_a'] = trim($m[1]);
        }
        if (preg_match('/[Bb]\s*[.\)]\s*(.*?)(?=\s+[Cc]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_b'] = trim($m[1]);
        }
        if (preg_match('/[Cc]\s*[.\)]\s*(.*?)(?=\s+[Dd]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_c'] = trim($m[1]);
        }
        if (preg_match('/[Dd]\s*[.\)]\s*(.*?)(?=\s+[Ee]\s*[.\)]|$)/is', $options_text, $m)) {
            $options['option_d'] = trim($m[1]);
        }
        if (preg_match('/[Ee]\s*[.\)]\s*(.*?)$/is', $options_text, $m)) {
            $options['option_e'] = trim($m[1]);
        }
        
        // Validate we have at least A, B, C, D
        if (empty($options['option_a']) && empty($options['option_b'])) {
            return null;
        }
        
        return $options;
    }
    
    /**
     * Parse answer key from text content
     * Format expected:
     * - Tabular format: "1. D    2. A    3. C    4. D" (multiple answers per line)
     * - Look for SOLUTIONS/ANSWERS sections before parsing
     * 
     * @param string $content The raw text content
     * @return array Answers indexed by question number
     */
    public function parse_answers($content) {
        $answers = array();
        
        // Normalize line endings and spaces
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Split into lines
        $lines = explode("\n", $content);
        
        // Track if we're in an answer key section
        $in_answer_section = false;
        $consecutive_answers = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if this line indicates we're in an answer key section
            if (preg_match('/\b(ANSWER\s*KEY|ANSWERS?|SOLUTIONS?|CORRECT\s*ANSWERS?)\s*:?\s*/i', $line)) {
                $in_answer_section = true;
                continue;
            }
            
            // Method 1: Check for multiple answers per line (tabular format)
            // e.g., "1. D    2. A    3. C    4. D    5. B    6. D"
            preg_match_all('/(\d{1,3})\.\s*([A-Ea-e])(?=\s|$|,)/i', $line, $matches, PREG_SET_ORDER);
            
            if (count($matches) >= 3) {
                // This is definitely an answer line with multiple answers
                $in_answer_section = true;
                foreach ($matches as $match) {
                    $question_number = intval($match[1]);
                    $answer = strtoupper($match[2]);
                    if ($question_number >= 1 && $question_number <= 500) {
                        $answers[$question_number] = $answer;
                    }
                }
                $consecutive_answers = 0;
                continue;
            }
            
            // Method 2: Check for single answer per line format
            // e.g., "1. A" or "1. D" on its own line
            // Only match if line is JUST the answer pattern (nothing else significant)
            if (preg_match('/^(\d{1,3})\.\s*([A-Ea-e])\s*$/i', $line, $match)) {
                $question_number = intval($match[1]);
                $answer = strtoupper($match[2]);
                
                // Validate question number is reasonable
                if ($question_number >= 1 && $question_number <= 500) {
                    // Check if this could be part of an answer key section
                    // by looking for consecutive answer patterns
                    if ($in_answer_section || isset($answers[$question_number - 1]) || $consecutive_answers > 0) {
                        $answers[$question_number] = $answer;
                        $consecutive_answers++;
                    } else if ($question_number <= 3) {
                        // If it's one of the first few questions, accept it as answer
                        $answers[$question_number] = $answer;
                        $consecutive_answers++;
                    }
                }
            } else {
                // Not an answer line - reset consecutive counter if not in dedicated section
                if (!$in_answer_section) {
                    $consecutive_answers = 0;
                }
            }
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
     * Parse questions and answers by year sections
     * Detects year headers like "USE OF ENGLISH 2010" or "2012 JAMB"
     * 
     * @param string $content Full document content
     * @param int $start_year Only include years >= this value (default 2010)
     * @return array Array of ['year' => int, 'subject' => string, 'exam_type' => string, 'questions' => array, 'answers' => array]
     */
    public function parse_by_year($content, $start_year = 2010) {
        $year_sections = array();
        
        // Normalize line endings
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // Find all year section headers
        // Patterns like: "USE OF ENGLISH 2010" or "2012 TYPE YELLOW" or "JAMB 2011"
        $year_pattern = '/^.*?(USE OF ENGLISH|MATHEMATICS|PHYSICS|CHEMISTRY|BIOLOGY|ECONOMICS|GOVERNMENT|LITERATURE|GEOGRAPHY|ACCOUNTING|COMMERCE|CIVIC|AGRICULTURAL|COMPUTER|HISTORY|ENGLISH)\s*(19\d{2}|20[0-2]\d).*$/mi';
        $year_pattern2 = '/^.*?(19\d{2}|20[0-2]\d)\s*(USE OF ENGLISH|MATHEMATICS|PHYSICS|CHEMISTRY|BIOLOGY|ECONOMICS|GOVERNMENT|LITERATURE|GEOGRAPHY|ACCOUNTING|COMMERCE|CIVIC|AGRICULTURAL|COMPUTER|HISTORY|ENGLISH|JAMB|TYPE).*$/mi';
        
        // Split content by year markers
        preg_match_all($year_pattern, $content, $matches1, PREG_OFFSET_CAPTURE);
        preg_match_all($year_pattern2, $content, $matches2, PREG_OFFSET_CAPTURE);
        
        // Combine and sort by offset
        $year_markers = array();
        
        foreach ($matches1[0] as $i => $match) {
            $year = intval($matches1[2][$i][0]);
            $subject = $this->normalize_subject($matches1[1][$i][0]);
            if ($year >= $start_year) {
                $year_markers[] = array(
                    'offset' => $match[1],
                    'year' => $year,
                    'subject' => $subject,
                    'exam_type' => 'jamb'
                );
            }
        }
        
        foreach ($matches2[0] as $i => $match) {
            $year = intval($matches2[1][$i][0]);
            $subject_or_type = $matches2[2][$i][0];
            $subject = $this->normalize_subject($subject_or_type);
            if ($year >= $start_year) {
                // Check if already added at similar offset (avoid duplicates)
                $is_duplicate = false;
                foreach ($year_markers as $existing) {
                    if (abs($existing['offset'] - $match[1]) < 100 && $existing['year'] === $year) {
                        $is_duplicate = true;
                        break;
                    }
                }
                if (!$is_duplicate) {
                    $year_markers[] = array(
                        'offset' => $match[1],
                        'year' => $year,
                        'subject' => $subject,
                        'exam_type' => 'jamb'
                    );
                }
            }
        }
        
        // Sort by offset
        usort($year_markers, function($a, $b) {
            return $a['offset'] - $b['offset'];
        });
        
        // Extract content for each year section
        $count = count($year_markers);
        for ($i = 0; $i < $count; $i++) {
            $start = $year_markers[$i]['offset'];
            $end = ($i + 1 < $count) ? $year_markers[$i + 1]['offset'] : strlen($content);
            
            $section_content = substr($content, $start, $end - $start);
            
            // Parse questions and answers for this section
            $questions = $this->parse_questions($section_content);
            $answers = $this->parse_answers($section_content);
            
            // Merge answers into questions
            if (!empty($answers)) {
                $questions = $this->merge_questions_with_answers($questions, $answers);
            }
            
            $year_sections[] = array(
                'year' => $year_markers[$i]['year'],
                'subject' => $year_markers[$i]['subject'],
                'exam_type' => $year_markers[$i]['exam_type'],
                'questions' => $questions,
                'answers' => $answers
            );
        }
        
        return $year_sections;
    }
    
    /**
     * Normalize subject name to consistent format
     */
    private function normalize_subject($subject) {
        $subject = strtoupper(trim($subject));
        $mapping = array(
            'USE OF ENGLISH' => 'Use of English',
            'ENGLISH' => 'Use of English',
            'ENGLISH LANGUAGE' => 'English Language',
            'MATHEMATICS' => 'Mathematics',
            'PHYSICS' => 'Physics',
            'CHEMISTRY' => 'Chemistry',
            'BIOLOGY' => 'Biology',
            'ECONOMICS' => 'Economics',
            'GOVERNMENT' => 'Government',
            'LITERATURE' => 'Literature in English',
            'GEOGRAPHY' => 'Geography',
            'ACCOUNTING' => 'Accounting',
            'COMMERCE' => 'Commerce',
            'CIVIC' => 'Civic Education',
            'AGRICULTURAL' => 'Agricultural Science',
            'COMPUTER' => 'Computer Studies',
            'HISTORY' => 'History',
            'JAMB' => 'Use of English',
            'TYPE' => 'Use of English'
        );
        return isset($mapping[$subject]) ? $mapping[$subject] : 'Use of English';
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
     * @param bool $allow_without_answers If true, import questions even without answers (answer set to '?')
     * @return array Result with success count and errors
     */
    public function import_to_database($questions, $exam_type, $subject, $year, $allow_without_answers = false) {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        $success_count = 0;
        $errors = array();
        $skipped = 0;
        $missing_answers = 0;
        
        foreach ($questions as $number => $question) {
            // Validate question has required fields
            if (empty($question['question_text'])) {
                $errors[] = "Question {$number}: Missing question text";
                continue;
            }
            
            // Check if we have at least options A-D (E is optional)
            if (empty($question['option_a']) || empty($question['option_b']) || 
                empty($question['option_c']) || empty($question['option_d'])) {
                $errors[] = "Question {$number}: Missing one or more options (A, B, C, D required)";
                continue;
            }
            
            if (empty($question['correct_answer'])) {
                $missing_answers++;
                if (!$allow_without_answers) {
                    $errors[] = "Question {$number}: Missing correct answer (no answer key found)";
                    continue;
                } else {
                    // Set a placeholder answer that can be updated later
                    $question['correct_answer'] = 'A'; // Default to A, admin must review
                }
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
            
            // Handle option E by appending to explanation if present
            $explanation = '';
            if (!empty($question['option_e'])) {
                $explanation = 'E. ' . $question['option_e'];
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
                'explanation' => $explanation
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
            'total_parsed' => count($questions),
            'missing_answers' => $missing_answers
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
     * AJAX handler for file upload
     */
    public function ajax_upload_file() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }
        
        $file = $_FILES['file'];
        $file_type = isset($_POST['file_type']) ? sanitize_text_field($_POST['file_type']) : 'questions';
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
                UPLOAD_ERR_EXTENSION => 'File upload blocked by extension.',
            );
            $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error.';
            wp_send_json_error(array('message' => $error_msg));
        }
        
        // Extract text from file
        $result = $this->extract_text_from_file($file);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $text_content = $result['content'];
        $detected_metadata = array();
        
        // Try to detect metadata from content
        $detected_metadata = $this->detect_metadata_from_content($text_content, $file['name']);
        
        wp_send_json_success(array(
            'content' => $text_content,
            'detected' => $detected_metadata,
            'file_type' => $file_type,
            'message' => 'File processed successfully.' . (!empty($detected_metadata['confidence']) ? ' ' . ucfirst($detected_metadata['confidence']) . ' confidence in detected metadata.' : '')
        ));
    }
    
    /**
     * Detect exam metadata from content and filename
     * 
     * @param string $content Text content
     * @param string $filename Original filename
     * @return array Detected metadata
     */
    public function detect_metadata_from_content($content, $filename = '') {
        $detected = array(
            'exam_type' => '',
            'subject' => '',
            'year' => '',
            'confidence' => 'low'
        );
        
        $text = strtolower($content . ' ' . $filename);
        $confidence_score = 0;
        
        // Detect exam type
        if (preg_match('/\bjamb\b/i', $text) || preg_match('/\butme\b/i', $text)) {
            $detected['exam_type'] = 'jamb';
            $confidence_score++;
        } else if (preg_match('/\bwaec\b/i', $text) || preg_match('/\bwassce\b/i', $text)) {
            $detected['exam_type'] = 'waec';
            $confidence_score++;
        } else if (preg_match('/\bneco\b/i', $text) || preg_match('/\bssce\b/i', $text)) {
            $detected['exam_type'] = 'neco';
            $confidence_score++;
        }
        
        // Detect year (look for 4-digit years between 1990 and current year)
        $current_year = intval(date('Y'));
        // Build dynamic regex pattern for years 1990-current year
        if (preg_match_all('/\b(19[9][0-9]|20[0-9]{2})\b/', $text, $year_matches)) {
            // Get the most likely year (prefer years in valid range)
            foreach ($year_matches[1] as $year) {
                $year_int = intval($year);
                if ($year_int >= 1990 && $year_int <= $current_year) {
                    $detected['year'] = $year;
                    $confidence_score++;
                    break;
                }
            }
        }
        
        // Detect subject
        $subjects_map = array(
            'use of english' => 'Use of English',
            'english language' => 'English Language',
            'mathematics' => 'Mathematics',
            'maths' => 'Mathematics',
            'further mathematics' => 'Further Mathematics',
            'further maths' => 'Further Mathematics',
            'physics' => 'Physics',
            'chemistry' => 'Chemistry',
            'biology' => 'Biology',
            'economics' => 'Economics',
            'government' => 'Government',
            'literature' => 'Literature in English',
            'literature in english' => 'Literature in English',
            'commerce' => 'Commerce',
            'accounting' => 'Accounting',
            'financial accounting' => 'Financial Accounting',
            'geography' => 'Geography',
            'agricultural science' => 'Agricultural Science',
            'agric' => 'Agricultural Science',
            'computer science' => 'Computer Science',
            'computer studies' => 'Computer Studies',
            'data processing' => 'Data Processing',
            'civic education' => 'Civic Education',
            'civics' => 'Civic Education',
            'history' => 'History',
            'christian religious studies' => 'Christian Religious Studies',
            'crs' => 'Christian Religious Studies',
            'islamic religious studies' => 'Islamic Religious Studies',
            'irs' => 'Islamic Religious Studies',
            'home economics' => 'Home Economics',
            'food and nutrition' => 'Food & Nutrition',
            'fine arts' => 'Fine Arts',
            'music' => 'Music',
            'french' => 'French',
            'arabic' => 'Arabic',
            'hausa' => 'Hausa',
            'igbo' => 'Igbo',
            'yoruba' => 'Yoruba',
            'physical education' => 'Physical Education',
            'health education' => 'Health Education',
            'health science' => 'Health Science',
            'technical drawing' => 'Technical Drawing'
        );
        
        foreach ($subjects_map as $pattern => $subject) {
            if (stripos($text, $pattern) !== false) {
                $detected['subject'] = $subject;
                $confidence_score++;
                break;
            }
        }
        
        // Set confidence level
        if ($confidence_score >= 3) {
            $detected['confidence'] = 'high';
        } else if ($confidence_score >= 2) {
            $detected['confidence'] = 'medium';
        } else {
            $detected['confidence'] = 'low';
        }
        
        return $detected;
    }
    
    /**
     * Extract text from uploaded file (PDF, TXT, DOCX)
     * 
     * @param array $file $_FILES array element
     * @return array|WP_Error Extracted text and metadata or error
     */
    public function extract_text_from_file($file) {
        $file_type = wp_check_filetype($file['name']);
        $ext = strtolower($file_type['ext']);
        $allowed_types = array('txt', 'text', 'pdf', 'docx', 'doc');
        
        if (!in_array($ext, $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Unsupported file type. Allowed: PDF, TXT, DOCX');
        }
        
        $content = '';
        
        switch ($ext) {
            case 'txt':
            case 'text':
                $content = $this->extract_from_txt($file['tmp_name']);
                break;
            case 'pdf':
                $content = $this->extract_from_pdf($file['tmp_name']);
                break;
            case 'docx':
                $content = $this->extract_from_docx($file['tmp_name']);
                break;
            case 'doc':
                $content = $this->extract_from_doc($file['tmp_name']);
                break;
        }
        
        if (is_wp_error($content)) {
            return $content;
        }
        
        if (empty(trim($content))) {
            return new WP_Error('empty_content', 'Could not extract any text from the file. Please ensure the file contains readable text.');
        }
        
        // Convert encoding if needed
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        
        return array(
            'content' => $content,
            'file_type' => $ext
        );
    }
    
    /**
     * Extract text from TXT file
     */
    private function extract_from_txt($filepath) {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read text file.');
        }
        return $content;
    }
    
    /**
     * Extract text from PDF file
     * Tries pdftotext command first (best quality), falls back to PHP parsing
     */
    private function extract_from_pdf($filepath) {
        // Validate filepath is a real file
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return new WP_Error('read_error', 'Could not read PDF file.');
        }
        
        // First, try using pdftotext command-line tool if available (best quality)
        $pdftotext_path = $this->find_pdftotext();
        if ($pdftotext_path && is_executable($pdftotext_path)) {
            // pdftotext_path is already validated to be from safe paths
            $escaped_path = escapeshellarg($filepath);
            $escaped_pdftotext = escapeshellarg($pdftotext_path);
            $output = shell_exec("$escaped_pdftotext -layout $escaped_path - 2>/dev/null");
            if (!empty(trim($output))) {
                // Clean up common PDF watermarks and artifacts
                $output = $this->clean_pdf_text($output);
                return $output;
            }
        }
        
        // Fallback to PHP-based extraction
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read PDF file.');
        }
        
        // Simple PDF text extraction
        $text = '';
        
        // Try to find text streams in PDF
        // Look for text between BT (begin text) and ET (end text) markers
        if (preg_match_all('/BT\s*(.+?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $text_block) {
                // Extract text from Tj and TJ operators
                if (preg_match_all('/\(([^)]+)\)\s*Tj/s', $text_block, $tj_matches)) {
                    $text .= implode(' ', $tj_matches[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $text_block, $TJ_matches)) {
                    foreach ($TJ_matches[1] as $TJ_content) {
                        if (preg_match_all('/\(([^)]+)\)/', $TJ_content, $inner_matches)) {
                            $text .= implode('', $inner_matches[1]);
                        }
                    }
                    $text .= "\n";
                }
            }
        }
        
        // Also try to extract raw text patterns
        // This catches some PDFs that store text differently
        if (empty(trim($text))) {
            // Try to find readable text sequences
            if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $stream_matches)) {
                foreach ($stream_matches[1] as $stream) {
                    // Decompress if using FlateDecode
                    $decompressed = @gzuncompress($stream);
                    if ($decompressed !== false) {
                        $stream = $decompressed;
                    }
                    
                    // Extract text between parentheses
                    if (preg_match_all('/\(([^)]{2,})\)/', $stream, $paren_matches)) {
                        foreach ($paren_matches[1] as $match) {
                            // Filter to printable characters
                            $filtered = preg_replace('/[^\x20-\x7E\n\r]/', '', $match);
                            if (strlen($filtered) > 2) {
                                $text .= $filtered . ' ';
                            }
                        }
                    }
                }
            }
        }
        
        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/([.?!])\s+/', "$1\n", $text);
        
        // If still no text, provide helpful message
        if (empty(trim($text))) {
            return new WP_Error(
                'pdf_extraction_failed', 
                'Could not extract text from this PDF. The PDF may use complex encoding. Please install poppler-utils on your server for better PDF support, or copy the text manually and paste it into the Questions Text field.'
            );
        }
        
        return $this->clean_pdf_text($text);
    }
    
    /**
     * Find pdftotext executable path
     */
    private function find_pdftotext() {
        // Only check known safe paths for pdftotext
        $safe_paths = array(
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
        );
        
        foreach ($safe_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Clean PDF text by removing common watermarks and artifacts
     */
    private function clean_pdf_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // Remove common watermark patterns found in Nigerian exam PDFs
        $patterns = array(
            '/myschoolgist\.com/i',  // myschoolgist.com watermark
            '/ysc\s*ho\s*ol\s*gis\s*t/i',  // broken myschoolgist text
            '/ww\s*w\.m/i',  // www.m partial
            '/Download\s+MySchoolGist[^\n]*/i',  // Download links
            '/https?:\/\/[^\s\n]+/i',  // URLs
            '/\n\s*\n\s*\n+/',  // Multiple blank lines
        );
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, "\n", $text);
        }
        
        // Clean up extra whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extract text from DOCX file
     */
    private function extract_from_docx($filepath) {
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_extension', 'ZipArchive extension is required to process DOCX files. Please contact your server administrator.');
        }
        
        // DOCX is a ZIP file containing XML
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return new WP_Error('read_error', 'Could not open DOCX file.');
        }
        
        // Get the main document content
        $xml_content = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if ($xml_content === false) {
            return new WP_Error('read_error', 'Could not read DOCX content.');
        }
        
        // Parse XML and extract text
        $text = '';
        
        // Remove XML tags but preserve structure
        $xml_content = str_replace('</w:p>', "\n", $xml_content);
        $xml_content = str_replace('</w:t>', ' ', $xml_content);
        $text = strip_tags($xml_content);
        
        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extract text from DOC file (older format)
     */
    private function extract_from_doc($filepath) {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return new WP_Error('read_error', 'Could not read DOC file.');
        }
        
        // DOC files are binary, try to extract readable text
        // This is a simple approach that works for many DOC files
        $text = '';
        
        // Try to find text patterns
        if (preg_match_all('/[\x20-\x7E]{4,}/', $content, $matches)) {
            $text = implode(' ', $matches[0]);
        }
        
        if (empty(trim($text))) {
            return new WP_Error(
                'doc_extraction_failed',
                'Could not extract text from this DOC file. Please save it as DOCX or copy the text manually.'
            );
        }
        
        return $text;
    }
}

// Initialize
ZonaTech_Question_Importer::get_instance();
