(function () {
    var quiz = document.getElementById('lifestyleQuiz');
    if (!quiz) {
        return; // Not on the lifestyle-match page
    }

    var STEPS = [
        {
            key: 'lifestyle',
            type: 'single',
            title: 'Which lifestyle best describes you?',
            subtitle: 'This helps us understand what environment suits you best.',
            options: [
                { value: 'nature_lover', icon: '&#127795;', label: 'Nature Lover', description: 'I enjoy peace, greenery, and open spaces.' },
                { value: 'city_explorer', icon: '&#127961;', label: 'City Explorer', description: 'I love the buzz of city life and convenient access to everything.' },
                { value: 'family_oriented', icon: '&#128106;', label: 'Family Oriented', description: 'I prioritize a safe, comfortable neighborhood for my family.' },
                { value: 'work_business', icon: '&#128188;', label: 'Work & Business', description: 'I need easy access to business districts and work hubs.' },
                { value: 'beach_lover', icon: '&#127958;', label: 'Beach Lover', description: 'I love coastal living and being close to the beach.' },
                { value: 'peace_seeker', icon: '&#127968;', label: 'Peace Seeker', description: 'I prefer a quiet and relaxing environment.' },
            ],
        },
        {
            key: 'location',
            type: 'single',
            title: 'Where do you prefer to live?',
            subtitle: 'Pick the setting that feels most like home.',
            options: [
                { value: 'near_coast', icon: '&#127958;', label: 'Near the Coast', description: 'Close to the beach and waterfront living.' },
                { value: 'city_center', icon: '&#127961;', label: 'City Center', description: 'Right where the action is, close to everything.' },
                { value: 'quiet_suburb', icon: '&#127968;', label: 'Quiet Suburb', description: 'A calm neighborhood away from the noise.' },
                { value: 'business_district', icon: '&#128188;', label: 'Business District', description: 'Close to offices and commercial hubs.' },
                { value: 'countryside', icon: '&#127795;', label: 'Countryside', description: 'Wide open spaces and a slower pace of life.' },
            ],
        },
        {
            key: 'property_type',
            type: 'single',
            title: 'What type of property?',
            subtitle: 'What are you looking to move into?',
            options: [
                { value: 'House', icon: '&#127968;', label: 'House', description: 'A standalone home with its own lot.' },
                { value: 'Condo', icon: '&#127961;', label: 'Condo', description: 'A unit within a condominium building.' },
                { value: 'Townhouse', icon: '&#127960;', label: 'Townhouse', description: 'A multi-level home sharing walls with neighbors.' },
                { value: 'Lot', icon: '&#127793;', label: 'Lot', description: 'Vacant land, ready to build on.' },
            ],
        },
        {
            key: 'budget',
            type: 'single',
            title: "What's your budget?",
            subtitle: 'Select the range that fits you best.',
            options: [
                { value: 'under_1m', icon: '&#8369;', label: 'Under ₱1M', description: 'Budget-friendly options.' },
                { value: '1m_5m', icon: '&#8369;', label: '₱1M - ₱5M', description: 'Mid-range properties.' },
                { value: '5m_10m', icon: '&#8369;', label: '₱5M - ₱10M', description: 'Upscale properties.' },
                { value: '10m_20m', icon: '&#8369;', label: '₱10M - ₱20M', description: 'Premium properties.' },
                { value: 'over_20m', icon: '&#8369;', label: '₱20M+', description: 'Luxury properties.' },
            ],
        },
        {
            key: 'household',
            type: 'single',
            title: 'Tell us about your family.',
            subtitle: 'This helps us find properties with enough space.',
            options: [
                { value: 'just_me', icon: '&#128100;', label: 'Just Me', description: 'A cozy space for one.' },
                { value: 'couple', icon: '&#128107;', label: 'Couple', description: 'A space to share with a partner.' },
                { value: 'small_family', icon: '&#128106;', label: 'Small Family', description: '3-4 members, a bit more room.' },
                { value: 'large_family', icon: '&#128103;&#8205;&#128106;&#8205;&#128102;&#8205;&#128102;', label: 'Large Family', description: '5+ members, plenty of space needed.' },
            ],
        },
        {
            key: 'must_haves',
            type: 'multi',
            title: 'What features are important?',
            subtitle: 'Select any that matter to you (optional).',
            options: [
                { value: 'Ocean View', icon: '&#127754;', label: 'Ocean View', description: 'A view of the water.' },
                { value: 'Outdoor Space', icon: '&#127794;', label: 'Outdoor Space', description: 'A yard, garden, or patio.' },
                { value: 'Parking', icon: '&#128663;', label: 'Parking', description: 'Dedicated parking space.' },
                { value: 'Home Office', icon: '&#128421;', label: 'Home Office', description: 'A dedicated room to work from home.' },
                { value: 'Pet-Friendly', icon: '&#128054;', label: 'Pet-Friendly', description: 'Comfortable for pets.' },
                { value: 'Accessible', icon: '&#9855;', label: 'Accessible', description: 'Step-free, accessible design.' },
            ],
        },
        {
            key: 'amenities',
            type: 'multi',
            title: 'What amenities do you prefer?',
            subtitle: 'Select any that matter to you (optional).',
            options: [
                { value: 'Pool', icon: '&#127946;', label: 'Pool', description: 'Swimming pool access.' },
                { value: 'Garden', icon: '&#127800;', label: 'Garden', description: 'Landscaped garden area.' },
                { value: 'Gym', icon: '&#127947;', label: 'Gym', description: 'On-site fitness center.' },
                { value: 'Security', icon: '&#128274;', label: 'Security', description: '24/7 security personnel.' },
                { value: 'Playground', icon: '&#127942;', label: 'Playground', description: 'Kid-friendly play area.' },
                { value: 'Clubhouse', icon: '&#127959;', label: 'Clubhouse', description: 'Shared community clubhouse.' },
            ],
        },
        {
            key: 'finish',
            type: 'summary',
            title: 'Almost done!',
            subtitle: "Here's a quick summary of your answers.",
        },
    ];

    var STEP_META = [
        { label: 'Lifestyle', hint: 'Your daily lifestyle' },
        { label: 'Location Preference', hint: 'Where do you prefer to live?' },
        { label: 'Property Type', hint: 'What type of property?' },
        { label: 'Budget Range', hint: "What's your budget?" },
        { label: 'Family & Household', hint: 'Tell us about your family' },
        { label: 'Must-Have Features', hint: 'What features are important?' },
        { label: 'Amenities', hint: 'What amenities do you prefer?' },
        { label: 'Finish', hint: 'Almost done!' },
    ];

    var state = {
        step: 0,
        answers: {
            lifestyle: null,
            location: null,
            property_type: null,
            budget: null,
            household: null,
            must_haves: [],
            amenities: [],
        },
    };

    var stepsList = document.getElementById('lifestyleStepsList');
    var stepIndicator = document.getElementById('lifestyleStepIndicator');
    var eyebrow = document.getElementById('lifestyleQuestionEyebrow');
    var titleEl = document.getElementById('lifestyleQuestionTitle');
    var subtitleEl = document.getElementById('lifestyleQuestionSubtitle');
    var optionsEl = document.getElementById('lifestyleOptions');
    var backBtn = document.getElementById('lifestyleBackBtn');
    var nextBtn = document.getElementById('lifestyleNextBtn');
    var quizPanel = document.getElementById('lifestyleQuiz');
    var resultsPanel = document.getElementById('lifestyleResults');
    var retakeBtn = document.getElementById('lifestyleRetakeBtn');

    function renderStepsList() {
        stepsList.innerHTML = '';
        STEP_META.forEach(function (meta, index) {
            var li = document.createElement('li');
            li.className = 'lifestyle-step-item';
            if (index === state.step) {
                li.classList.add('is-active');
            } else if (index < state.step) {
                li.classList.add('is-complete');
            }

            var bubble = document.createElement('span');
            bubble.className = 'lifestyle-step-bubble';
            bubble.innerHTML = index < state.step ? '&#10003;' : String(index + 1);

            var text = document.createElement('span');
            text.className = 'lifestyle-step-text';
            text.innerHTML = '<strong>' + meta.label + '</strong><small>' + meta.hint + '</small>';

            li.appendChild(bubble);
            li.appendChild(text);
            stepsList.appendChild(li);
        });
    }

    function isMultiSelected(key, value) {
        return state.answers[key].indexOf(value) !== -1;
    }

    function toggleMulti(key, value) {
        var list = state.answers[key];
        var index = list.indexOf(value);
        if (index === -1) {
            list.push(value);
        } else {
            list.splice(index, 1);
        }
    }

    function renderOptions(step) {
        optionsEl.innerHTML = '';

        if (step.type === 'summary') {
            optionsEl.classList.add('lifestyle-summary');
            var list = document.createElement('ul');
            list.className = 'lifestyle-summary-list';

            STEPS.slice(0, -1).forEach(function (s) {
                var answer = state.answers[s.key];
                var display;

                if (Array.isArray(answer)) {
                    display = answer.length ? answer.join(', ') : 'None selected';
                } else if (answer) {
                    var matched = s.options.filter(function (o) { return o.value === answer; })[0];
                    display = matched ? matched.label : answer;
                } else {
                    display = 'Not answered';
                }

                var li = document.createElement('li');
                li.innerHTML = '<strong>' + STEP_META[STEPS.indexOf(s)].label + ':</strong> <span>' + display + '</span>';
                list.appendChild(li);
            });

            optionsEl.appendChild(list);
            return;
        }

        optionsEl.classList.remove('lifestyle-summary');

        step.options.forEach(function (option) {
            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'lifestyle-option-card';

            var selected = step.type === 'multi'
                ? isMultiSelected(step.key, option.value)
                : state.answers[step.key] === option.value;

            if (selected) {
                card.classList.add('is-selected');
            }

            card.innerHTML =
                '<span class="lifestyle-option-check">' + (step.type === 'multi' ? '&#10003;' : '&#10003;') + '</span>' +
                '<span class="lifestyle-option-icon">' + option.icon + '</span>' +
                '<strong class="lifestyle-option-label">' + option.label + '</strong>' +
                '<span class="lifestyle-option-description">' + option.description + '</span>';

            card.addEventListener('click', function () {
                if (step.type === 'multi') {
                    toggleMulti(step.key, option.value);
                } else {
                    state.answers[step.key] = option.value;
                }
                renderStep();
            });

            optionsEl.appendChild(card);
        });
    }

    function canAdvance(step) {
        if (step.type === 'single') {
            return !!state.answers[step.key];
        }
        return true;
    }

    function renderStep() {
        var step = STEPS[state.step];

        renderStepsList();

        stepIndicator.textContent = 'Step ' + (state.step + 1) + ' of ' + STEPS.length;
        eyebrow.textContent = step.type === 'summary' ? 'Almost there' : 'Question ' + (state.step + 1) + ' of ' + (STEPS.length - 1);
        titleEl.textContent = step.title;
        subtitleEl.textContent = step.subtitle;

        renderOptions(step);

        backBtn.disabled = state.step === 0;
        backBtn.style.visibility = state.step === 0 ? 'hidden' : 'visible';

        if (step.type === 'summary') {
            nextBtn.textContent = 'Get My Matches';
            nextBtn.disabled = false;
        } else {
            nextBtn.textContent = 'Next Question →';
            nextBtn.disabled = !canAdvance(step);
        }
    }

    backBtn.addEventListener('click', function () {
        if (state.step > 0) {
            state.step -= 1;
            renderStep();
        }
    });

    nextBtn.addEventListener('click', function () {
        var step = STEPS[state.step];

        if (step.type === 'summary') {
            submitQuiz();
            return;
        }

        if (!canAdvance(step)) {
            return;
        }

        if (state.step < STEPS.length - 1) {
            state.step += 1;
            renderStep();
        }
    });

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function submitQuiz() {
        nextBtn.disabled = true;
        nextBtn.textContent = 'Finding matches...';

        fetch('/lifestyle-match/results', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(state.answers),
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                renderResults(data);
                quizPanel.hidden = true;
                resultsPanel.hidden = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function () {
                nextBtn.disabled = false;
                nextBtn.textContent = 'Get My Matches';
                alert('Something went wrong finding your matches. Please try again.');
            });
    }

    function formatCurrency(value) {
        return '₱ ' + Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function renderResults(data) {
        var grid = document.getElementById('lifestyleMatchesGrid');
        grid.innerHTML = '';

        (data.matches || []).forEach(function (property) {
            var card = document.createElement('article');
            card.className = 'property-card';
            card.innerHTML =
                '<div class="property-card-img-container">' +
                    '<img src="' + property.image + '" alt="' + property.name + '">' +
                    '<div class="property-badges">' +
                        '<span class="badge-sale lifestyle-badge-match">' + property.score + '% Match</span>' +
                    '</div>' +
                '</div>' +
                '<div class="property-card-body">' +
                    '<div class="property-price">' + formatCurrency(property.price) + '</div>' +
                    '<h3 class="property-title">' + property.name + '</h3>' +
                    '<p class="property-location">' + (property.location || 'Cebu, Philippines') + '</p>' +
                    '<hr class="property-divider">' +
                    '<div class="property-meta">' +
                        '<span>' + property.bedrooms + ' beds</span>' +
                        '<span>' + property.bathrooms + ' baths</span>' +
                        '<span>' + property.floorArea + ' sqm</span>' +
                    '</div>' +
                    '<div class="property-action">' +
                        '<a href="' + property.url + '" class="property-btn">View Details</a>' +
                    '</div>' +
                '</div>';
            grid.appendChild(card);
        });

        if (!data.matches || !data.matches.length) {
            grid.innerHTML = '<div class="property-empty-state"><p>No matching properties found yet. Try exploring all properties instead.</p></div>';
        }

        var score = data.matchScore || 0;
        document.getElementById('lifestyleScoreValue').textContent = score + '%';
        document.getElementById('lifestyleScoreRing').style.background =
            'conic-gradient(var(--color-accent-green, #2bbf62) ' + (score * 3.6) + 'deg, #e5edf7 0deg)';

        var prefLabels = {
            lifestyle: 'Lifestyle',
            location: 'Location Preference',
            property_type: 'Property Type',
            budget: 'Budget Range',
            household: 'Family & Household',
            must_haves: 'Must-Have Features',
            amenities: 'Amenities',
        };

        var prefList = document.getElementById('lifestylePreferencesList');
        prefList.innerHTML = '';
        Object.keys(prefLabels).forEach(function (key) {
            if (!data.preferences || !data.preferences[key]) {
                return;
            }
            var li = document.createElement('li');
            li.innerHTML = '<span class="lifestyle-pref-icon">&#9733;</span><div><strong>' + prefLabels[key] + '</strong><small>' + data.preferences[key] + '</small></div>';
            prefList.appendChild(li);
        });
    }

    retakeBtn.addEventListener('click', function () {
        state = {
            step: 0,
            answers: {
                lifestyle: null,
                location: null,
                property_type: null,
                budget: null,
                household: null,
                must_haves: [],
                amenities: [],
            },
        };
        nextBtn.disabled = false;
        resultsPanel.hidden = true;
        quizPanel.hidden = false;
        renderStep();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    renderStep();
})();
