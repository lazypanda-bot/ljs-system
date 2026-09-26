@extends('layouts.app')

@section('title', 'Lifestyle Match')

@section('content')
<div class="lifestyle-page">
    <div class="lifestyle-page-container">

        <a href="{{ route('properties') }}" class="lifestyle-back">&larr; Back to Properties</a>

        {{-- QUIZ --}}
        <div id="lifestyleQuiz" class="lifestyle-quiz">
            <div class="lifestyle-quiz-header">
                <span class="lifestyle-quiz-icon"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                <h1>Lifestyle Match</h1>
                <p>Answer a few questions about your lifestyle and we'll find the perfect properties for you.</p>
            </div>

            <div class="lifestyle-quiz-body">
                <aside class="lifestyle-progress">
                    <div class="lifestyle-progress-header">
                        <span>Your Progress</span>
                        <span id="lifestyleStepIndicator">Step 1 of 8</span>
                    </div>
                    <ol id="lifestyleStepsList" class="lifestyle-steps-list"></ol>
                </aside>

                <div class="lifestyle-question-panel">
                    <div class="lifestyle-question-meta">
                        <span id="lifestyleQuestionEyebrow" class="lifestyle-question-eyebrow"></span>
                        <h2 id="lifestyleQuestionTitle"></h2>
                        <p id="lifestyleQuestionSubtitle"></p>
                    </div>

                    <div id="lifestyleOptions" class="lifestyle-options"></div>

                    <div class="lifestyle-question-actions">
                        <button type="button" id="lifestyleBackBtn" class="lifestyle-btn-secondary">&larr; Back</button>
                        <button type="button" id="lifestyleNextBtn" class="lifestyle-btn-primary">Next Question &rarr;</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- RESULTS --}}
        <div id="lifestyleResults" class="lifestyle-results" hidden>
            <div class="lifestyle-results-header">
                <span class="lifestyle-results-check"><i data-lucide="circle-check" aria-hidden="true"></i></span>
                <h1>Your Lifestyle Match is Ready!</h1>
                <p>Based on your answers, we've found properties that best fit your lifestyle and preferences.</p>
            </div>

            <div class="lifestyle-results-body">
                <div class="lifestyle-results-main">
                    <div class="lifestyle-results-main-header">
                        <div>
                            <h3>Your Top Lifestyle Matches</h3>
                            <p>These properties match your lifestyle and preferences the most.</p>
                        </div>
                        <a href="{{ route('properties') }}" class="lifestyle-btn-secondary lifestyle-view-all">View All Properties &rarr;</a>
                    </div>

                    <div id="lifestyleMatchesGrid" class="properties-grid lifestyle-matches-grid"></div>

                    <div class="lifestyle-why-matches">
                        <span class="lifestyle-why-icon"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                        <div>
                            <strong>Why these matches?</strong>
                            <p>These properties align with your lifestyle preferences, including your ideal location, budget, must-have features, and the type of environment you value most.</p>
                        </div>
                    </div>

                    <div class="lifestyle-explore-more">
                        <a href="{{ route('properties') }}" class="lifestyle-btn-primary">Explore More Properties</a>
                    </div>
                </div>

                <aside class="lifestyle-results-sidebar">
                    <div class="lifestyle-score-card">
                        <div class="lifestyle-score-ring" id="lifestyleScoreRing">
                            <span id="lifestyleScoreValue">0%</span>
                        </div>
                        <strong>Great Match!</strong>
                        <p>We found great matches based on your lifestyle and preferences.</p>
                    </div>

                    <div class="lifestyle-preferences-card">
                        <h4>Your Key Preferences</h4>
                        <ul id="lifestylePreferencesList" class="lifestyle-preferences-list"></ul>
                    </div>

                    <div class="lifestyle-retake-card">
                        <p>Not exactly what you were looking for?</p>
                        <button type="button" id="lifestyleRetakeBtn" class="lifestyle-btn-secondary"><i data-lucide="rotate-ccw" aria-hidden="true"></i> Retake Lifestyle Quiz</button>
                    </div>
                </aside>
            </div>
        </div>

    </div>
</div>
@endsection
