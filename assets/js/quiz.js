/**
 * ZonaTech NG - Quiz System JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        ZonaTechQuiz.init();
    });
    
    const ZonaTechQuiz = {
        currentQuiz: null,
        answers: {},
        timer: null,
        timeRemaining: 0,
        
        init: function() {
            this.initQuestionViewer();
            this.initQuizStarter();
        },
        
        // Question Viewer
        initQuestionViewer: function() {
            const self = this;
            
            // Subject filter change
            $(document).on('change', '#exam-type-select', function() {
                self.loadSubjects($(this).val());
            });
            
            $(document).on('change', '#subject-select', function() {
                const examType = $('#exam-type-select').val();
                const subject = $(this).val();
                if (examType && subject) {
                    self.loadYears(examType, subject);
                }
            });
            
            // Load questions button
            $(document).on('click', '#load-questions-btn', function() {
                const examType = $('#exam-type-select').val();
                const subject = $('#subject-select').val();
                const year = $('#year-select').val();
                
                if (!examType || !subject || !year) {
                    ZonaTechNotify.show('Please select exam type, subject and year.', 'warning');
                    return;
                }
                
                self.loadQuestions(examType, subject, year);
            });
        },
        
        // Load subjects for exam type
        loadSubjects: function(examType) {
            if (!examType) {
                $('#subject-select').html('<option value="">Select Subject</option>');
                return;
            }
            
            $('#subject-select').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_subjects',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType
                },
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">Select Subject</option>';
                        response.data.subjects.forEach(function(subject) {
                            options += `<option value="${subject}">${subject}</option>`;
                        });
                        $('#subject-select').html(options);
                    }
                }
            });
        },
        
        // Load years for subject
        loadYears: function(examType, subject) {
            $('#year-select').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_years',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject
                },
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">Select Year</option>';
                        response.data.years.forEach(function(year) {
                            options += `<option value="${year}">${year}</option>`;
                        });
                        $('#year-select').html(options);
                    }
                }
            });
        },
        
        // Load questions
        loadQuestions: function(examType, subject, year) {
            const self = this;
            const container = $('#questions-container');
            
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_questions',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject,
                    year: year
                },
                success: function(response) {
                    if (response.success) {
                        self.renderQuestions(response.data);
                    } else {
                        if (response.data.require_payment) {
                            self.showPaymentPrompt(response.data);
                        } else {
                            container.html(`<div class="alert alert-error">${response.data.message}</div>`);
                        }
                    }
                },
                error: function() {
                    container.html('<div class="alert alert-error">Failed to load questions. Please try again.</div>');
                }
            });
        },
        
        // Render questions
        renderQuestions: function(data) {
            const container = $('#questions-container');
            let html = `
                <div class="questions-header glass-card">
                    <h3>${data.exam_type} ${data.subject} - ${data.year}</h3>
                    <p>Total Questions: ${data.total}</p>
                    <button class="btn btn-primary" id="start-quiz-btn" 
                            data-exam="${data.exam_type.toLowerCase()}" 
                            data-subject="${data.subject}" 
                            data-year="${data.year}">
                        <i class="fas fa-play"></i> Take Quiz
                    </button>
                </div>
                <div class="questions-list">
            `;
            
            data.questions.forEach(function(q, index) {
                const correctAnswer = q.correct_answer ? q.correct_answer.toUpperCase() : '';
                const options = [
                    { letter: 'A', text: q.option_a },
                    { letter: 'B', text: q.option_b },
                    { letter: 'C', text: q.option_c },
                    { letter: 'D', text: q.option_d }
                ];
                
                html += `
                    <div class="question-card animate-card">
                        <span class="question-number">${index + 1}</span>
                        <p class="question-text">${q.question_text}</p>
                        <div class="question-options">
                `;
                
                options.forEach(function(opt) {
                    const isCorrect = opt.letter === correctAnswer;
                    const correctClass = isCorrect ? 'correct' : '';
                    const icon = isCorrect ? '<i class="fas fa-check-circle" style="color: var(--zona-success); margin-left: auto;"></i>' : '';
                    
                    html += `
                        <div class="option-item ${correctClass}">
                            <span class="option-letter">${opt.letter}</span>
                            <span class="option-text">${opt.text}</span>
                            ${icon}
                        </div>
                    `;
                });
                
                html += '</div>';
                
                // Show explanation if available
                if (q.explanation && q.explanation.trim()) {
                    html += `
                        <div class="explanation" style="padding: 1rem; background: rgba(139, 92, 246, 0.1); border-left: 3px solid var(--zona-purple); border-radius: 0 0.5rem 0.5rem 0; margin-top: 1rem;">
                            <strong style="color: var(--zona-purple);"><i class="fas fa-lightbulb"></i> Explanation:</strong>
                            <p style="margin: 0.5rem 0 0; color: var(--zona-text-secondary);">${q.explanation}</p>
                        </div>
                    `;
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            container.html(html);
        },
        
        // Show payment prompt
        showPaymentPrompt: function(data) {
            const container = $('#questions-container');
            container.html(`
                <div class="glass-card text-center" style="padding: 3rem;">
                    <i class="fas fa-lock" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>
                    <h3>Access Required</h3>
                    <p>${data.message}</p>
                    <p class="text-purple" style="font-size: 1.5rem; font-weight: 700; margin: 1rem 0;">
                        ₦${zonatech_ajax.subject_price.toLocaleString()}
                    </p>
                    <button class="btn btn-primary btn-lg" id="buy-subject-btn"
                            data-exam="${data.exam_type}"
                            data-subject="${data.subject}">
                        <i class="fas fa-shopping-cart"></i> Purchase Access
                    </button>
                </div>
            `);
        },
        
        // Initialize quiz starter
        initQuizStarter: function() {
            const self = this;
            
            $(document).on('click', '#start-quiz-btn', function() {
                const examType = $(this).data('exam');
                const subject = $(this).data('subject');
                
                self.startQuiz(examType, subject);
            });
            
            // Answer selection
            $(document).on('click', '.quiz-mode .option-item', function() {
                const questionId = $(this).closest('.question-card').data('question-id');
                const answer = $(this).data('answer');
                
                $(this).closest('.question-options').find('.option-item').removeClass('selected');
                $(this).addClass('selected');
                
                self.answers[questionId] = answer;
                self.updateProgress();
            });
            
            // Submit quiz
            $(document).on('click', '#submit-quiz-btn', function() {
                if (Object.keys(self.answers).length === 0) {
                    ZonaTechNotify.show('Please answer at least one question.', 'warning');
                    return;
                }
                
                if (!confirm('Are you sure you want to submit the quiz?')) return;
                
                self.submitQuiz();
            });
            
            // View corrections
            $(document).on('click', '#view-corrections-btn', function() {
                const resultId = $(this).data('result-id');
                self.viewCorrections(resultId);
            });
        },
        
        // Start quiz - no longer requires year
        startQuiz: function(examType, subject) {
            const self = this;
            const container = $('#questions-container');
            
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_start_quiz',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject,
                    question_count: 50 // Default 50 questions per quiz
                },
                success: function(response) {
                    if (response.success) {
                        self.currentQuiz = response.data;
                        self.answers = {};
                        self.timeRemaining = response.data.time_limit;
                        self.renderQuiz(response.data);
                        self.startTimer();
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                        ZonaTechNotify.show(response.data.message, 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Quiz start error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to start quiz. Please check your connection and try again.</div>`);
                    ZonaTechNotify.show('Failed to start quiz. Please try again.', 'error', 5000);
                }
            });
        },
        
        // Render quiz - no year display
        renderQuiz: function(data) {
            const container = $('#questions-container');
            let html = `
                <div class="quiz-mode">
                    <div class="quiz-header glass-card">
                        <div class="quiz-info">
                            <h3>${data.exam_type} ${data.subject} Quiz</h3>
                            <p>Questions: ${data.total}</p>
                        </div>
                        <div class="quiz-timer">
                            <i class="fas fa-clock"></i>
                            <span id="quiz-timer">--:--</span>
                        </div>
                    </div>
                    <div class="quiz-progress mb-2">
                        <div class="progress-bar">
                            <div class="progress-fill" id="quiz-progress" style="width: 0%;"></div>
                        </div>
                        <small><span id="answered-count">0</span>/${data.total} answered</small>
                    </div>
                    <div class="questions-list">
            `;
            
            data.questions.forEach(function(q, index) {
                html += `
                    <div class="question-card animate-card" data-question-id="${q.id}">
                        <span class="question-number">${index + 1}</span>
                        <p class="question-text">${q.question_text}</p>
                        <div class="question-options">
                            <div class="option-item" data-answer="A">
                                <span class="option-letter">A</span>
                                <span class="option-text">${q.option_a}</span>
                            </div>
                            <div class="option-item" data-answer="B">
                                <span class="option-letter">B</span>
                                <span class="option-text">${q.option_b}</span>
                            </div>
                            <div class="option-item" data-answer="C">
                                <span class="option-letter">C</span>
                                <span class="option-text">${q.option_c}</span>
                            </div>
                            <div class="option-item" data-answer="D">
                                <span class="option-letter">D</span>
                                <span class="option-text">${q.option_d}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                    <div class="quiz-actions text-center mt-3">
                        <button class="btn btn-primary btn-lg" id="submit-quiz-btn">
                            <i class="fas fa-check"></i> Submit Quiz
                        </button>
                    </div>
                </div>
            `;
            
            container.html(html);
        },
        
        // Start timer
        startTimer: function() {
            const self = this;
            
            this.timer = setInterval(function() {
                self.timeRemaining--;
                
                const minutes = Math.floor(self.timeRemaining / 60);
                const seconds = self.timeRemaining % 60;
                
                $('#quiz-timer').text(
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                );
                
                if (self.timeRemaining <= 60) {
                    $('#quiz-timer').css('color', 'var(--zona-error)');
                }
                
                if (self.timeRemaining <= 0) {
                    clearInterval(self.timer);
                    ZonaTechNotify.show('Time is up! Submitting your quiz...', 'warning');
                    self.submitQuiz();
                }
            }, 1000);
        },
        
        // Update progress
        updateProgress: function() {
            const total = this.currentQuiz.total;
            const answered = Object.keys(this.answers).length;
            const percent = (answered / total) * 100;
            
            $('#quiz-progress').css('width', percent + '%');
            $('#answered-count').text(answered);
        },
        
        // Submit quiz - no year required
        submitQuiz: function() {
            const self = this;
            clearInterval(this.timer);
            
            const container = $('#questions-container');
            container.html('<div class="loading"><div class="spinner"></div><p class="loading-text">Submitting quiz...</p></div>');
            
            const timeTaken = this.currentQuiz.time_limit - this.timeRemaining;
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_submit_quiz',
                    nonce: zonatech_ajax.nonce,
                    exam_type: this.currentQuiz.exam_type.toLowerCase(),
                    subject: this.currentQuiz.subject,
                    answers: JSON.stringify(this.answers),
                    time_taken: timeTaken
                },
                success: function(response) {
                    if (response.success) {
                        self.showResults(response.data);
                        ZonaTechNotify.show('Quiz submitted successfully! Your results are ready.', 'success', 6000);
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                        ZonaTechNotify.show(response.data.message, 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Quiz submit error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to submit quiz. Please check your connection and try again.</div>`);
                    ZonaTechNotify.show('Failed to submit quiz. Please try again.', 'error', 5000);
                }
            });
        },
        
        // Show results
        showResults: function(data) {
            const container = $('#questions-container');
            const gradeColor = data.score >= 50 ? 'var(--zona-success)' : 'var(--zona-error)';
            
            container.html(`
                <div class="result-card">
                    <div class="result-score" style="background: ${data.score >= 50 ? 'var(--zona-success)' : 'var(--zona-error)'};">
                        <span class="score-value">${data.score}%</span>
                        <span class="score-label">Score</span>
                    </div>
                    <h2 class="result-grade">Grade: ${data.grade}</h2>
                    <p>${data.message}</p>
                    <div class="result-stats">
                        <div class="result-stat correct">
                            <span class="stat-value">${data.correct}</span>
                            <span class="stat-label">Correct</span>
                        </div>
                        <div class="result-stat wrong">
                            <span class="stat-value">${data.wrong}</span>
                            <span class="stat-label">Wrong</span>
                        </div>
                        <div class="result-stat">
                            <span class="stat-value">${data.total}</span>
                            <span class="stat-label">Total</span>
                        </div>
                    </div>
                    <div class="result-actions mt-3">
                        <button class="btn btn-primary" id="view-corrections-btn" data-result-id="${data.result_id}">
                            <i class="fas fa-eye"></i> View Corrections
                        </button>
                        <a href="${window.location.href}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                    </div>
                </div>
            `);
        },
        
        // View corrections
        viewCorrections: function(resultId) {
            const container = $('#questions-container');
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_corrections',
                    nonce: zonatech_ajax.nonce,
                    result_id: resultId
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.corrections.length === 0) {
                            container.html(`
                                <div class="glass-card text-center" style="padding: 3rem;">
                                    <i class="fas fa-trophy" style="font-size: 3rem; color: var(--zona-success); margin-bottom: 1rem;"></i>
                                    <h3>Perfect Score!</h3>
                                    <p>${response.data.message}</p>
                                    <a href="${window.location.href}" class="btn btn-primary mt-2">
                                        <i class="fas fa-redo"></i> Take Another Quiz
                                    </a>
                                </div>
                            `);
                            return;
                        }
                        
                        let html = `
                            <div class="corrections-header glass-card mb-2">
                                <h3><i class="fas fa-check-double"></i> Corrections</h3>
                                <p>Review the questions you got wrong</p>
                            </div>
                            <div class="corrections-list">
                        `;
                        
                        response.data.corrections.forEach(function(c, index) {
                            html += `
                                <div class="question-card">
                                    <span class="question-number">${index + 1}</span>
                                    <p class="question-text">${c.question}</p>
                                    <div class="question-options">
                            `;
                            
                            ['A', 'B', 'C', 'D'].forEach(function(letter) {
                                const isCorrect = letter === c.correct_answer;
                                const isUserAnswer = letter === c.your_answer;
                                let className = '';
                                
                                if (isCorrect) className = 'correct';
                                else if (isUserAnswer) className = 'wrong';
                                
                                html += `
                                    <div class="option-item ${className}">
                                        <span class="option-letter">${letter}</span>
                                        <span class="option-text">${c.options[letter]}</span>
                                        ${isCorrect ? '<i class="fas fa-check" style="color: var(--zona-success); margin-left: auto;"></i>' : ''}
                                        ${isUserAnswer && !isCorrect ? '<i class="fas fa-times" style="color: var(--zona-error); margin-left: auto;"></i>' : ''}
                                    </div>
                                `;
                            });
                            
                            if (c.explanation) {
                                html += `
                                    <div class="explanation mt-2" style="padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 0.5rem;">
                                        <strong><i class="fas fa-lightbulb"></i> Explanation:</strong>
                                        <p style="margin: 0.5rem 0 0;">${c.explanation}</p>
                                    </div>
                                `;
                            }
                            
                            html += `
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                            </div>
                            <div class="text-center mt-3">
                                <a href="${window.location.href}" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Try Again
                                </a>
                            </div>
                        `;
                        
                        container.html(html);
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Corrections error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to load corrections. Please try again.</div>`);
                }
            });
        }
    };
    
    window.ZonaTechQuiz = ZonaTechQuiz;
    
})(jQuery);