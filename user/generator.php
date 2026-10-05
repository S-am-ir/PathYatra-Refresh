<?php
/**
 * YatraPath — 4-Step Intelligent Itinerary Generator Wizard
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$destinations = $db->query("SELECT * FROM destinations ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary Generator — YatraPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text); padding-bottom: 3rem; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: #fff; border-bottom: 1px solid var(--border); }
        .logo { font-size: 1.4rem; font-weight: 800; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.95rem; }
        
        .wizard-container { max-width: 860px; margin: 2.5rem auto; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        /* Steps indicator */
        .steps-bar { display: flex; justify-content: space-between; margin-bottom: 2.5rem; position: relative; }
        .steps-bar::before { content: ''; position: absolute; top: 18px; left: 10%; right: 10%; height: 2px; background: var(--border); z-index: 1; }
        .step-item { position: relative; z-index: 2; background: #fff; padding: 0 0.5rem; text-align: center; }
        .step-bubble { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: var(--muted); display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 0.4rem; }
        .step-item.active .step-bubble { background: var(--primary); color: #fff; }
        .step-text { font-size: 0.8rem; font-weight: 600; color: var(--muted); }
        .step-item.active .step-text { color: var(--primary); }

        .step-section { display: none; }
        .step-section.active { display: block; }
        
        .section-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem; }
        .section-subtitle { color: var(--muted); font-size: 0.95rem; margin-bottom: 2rem; }

        /* Step 1: Destinations */
        .dest-select-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .dest-option { border: 2px solid var(--border); border-radius: 12px; padding: 1.25rem; cursor: pointer; transition: all 0.2s; }
        .dest-option:hover { border-color: var(--primary); }
        .dest-option.selected { border-color: var(--primary); background: #eff6ff; }
        .dest-option input { display: none; }
        .dest-name { font-weight: 700; font-size: 1.05rem; margin-bottom: 0.25rem; }
        .dest-badge { display: inline-block; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: #e2e8f0; margin-bottom: 0.5rem; }

        /* Step 2: Dates & Duration */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .field-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem; }
        input[type="date"], input[type="number"], select { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); font-size: 1rem; }
        .range-slider { width: 100%; }

        /* Step 3: Budget */
        .budget-preview { background: #f1f5f9; border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
        .tier-badge { display: inline-block; font-weight: 700; padding: 0.3rem 0.75rem; border-radius: 6px; background: #dbeafe; color: var(--primary); font-size: 0.85rem; }

        /* Step 4: Interests */
        .interests-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .interest-card { border: 2px solid var(--border); border-radius: 12px; padding: 1.25rem; text-align: center; cursor: pointer; }
        .interest-card.selected { border-color: var(--primary); background: #eff6ff; }
        .interest-card input { display: none; }
        .interest-title { font-weight: 700; font-size: 0.95rem; }

        /* Wizard Nav Buttons */
        .wizard-buttons { display: flex; justify-content: space-between; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; font-size: 0.95rem; }
        .btn-prev { background: #e2e8f0; color: var(--text); }
        .btn-next { background: var(--primary); color: #fff; }
        .btn-gen { background: #059669; color: #fff; }

        /* Loading / Results container */
        #result-container { display: none; }
        .day-card { border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .slot-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed var(--border); font-size: 0.9rem; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../homepage.php" class="logo">YatraPath</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="generator.php" style="color: var(--primary); font-weight: 600;">Generator</a>
            <a href="itineraries.php">My Itineraries</a>
            <a href="../api/auth/logout.php" style="color: #ef4444;">Sign Out</a>
        </nav>
    </header>

    <main class="wizard-container">
        <!-- Progress Bar -->
        <div class="steps-bar" id="steps-bar">
            <div class="step-item active" data-step="1"><div class="step-bubble">1</div><div class="step-text">Where</div></div>
            <div class="step-item" data-step="2"><div class="step-bubble">2</div><div class="step-text">When</div></div>
            <div class="step-item" data-step="3"><div class="step-bubble">3</div><div class="step-text">Budget</div></div>
            <div class="step-item" data-step="4"><div class="step-bubble">4</div><div class="step-text">Interests</div></div>
        </div>

        <form id="generator-form">
            <!-- Step 1: Where -->
            <div class="step-section active" id="step-1">
                <h2 class="section-title">Where do you want to explore?</h2>
                <p class="section-subtitle">Select one or more destinations. Our route optimizer will calculate the best path.</p>
                <div class="dest-select-grid">
                    <?php foreach ($destinations as $d): ?>
                        <div class="dest-option" onclick="toggleSelect(this)">
                            <input type="checkbox" name="destinations[]" value="<?= $d['id'] ?>">
                            <span class="dest-badge"><?= htmlspecialchars($d['region']) ?></span>
                            <div class="dest-name"><?= htmlspecialchars($d['name']) ?></div>
                            <div style="font-size:0.8rem; color:var(--muted);">~ NPR <?= number_format((float)$d['avg_cost_per_day']) ?>/day</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step 2: When -->
            <div class="step-section" id="step-2">
                <h2 class="section-title">When and for how long?</h2>
                <p class="section-subtitle">We will detect the season and filter out unsuitable routes or trails.</p>
                <div class="form-row">
                    <div class="field-group">
                        <label for="start_date">Travel Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>
                    <div class="field-group">
                        <label for="days">Duration: <span id="days-val">5</span> Days</label>
                        <input type="range" class="range-slider" id="days" name="days" min="1" max="30" value="5" oninput="document.getElementById('days-val').innerText = this.value; updateBudgetCalc();">
                    </div>
                </div>
            </div>

            <!-- Step 3: Budget -->
            <div class="step-section" id="step-3">
                <h2 class="section-title">What is your total budget?</h2>
                <p class="section-subtitle">Enter your total budget in Nepali Rupees (NPR). We will assign lodging tiers and daily activity limits.</p>
                <div class="field-group">
                    <label for="budget">Total Trip Budget (NPR)</label>
                    <input type="number" id="budget" name="budget" step="1000" min="5000" value="30000" oninput="updateBudgetCalc()" required>
                </div>
                <div class="budget-preview">
                    <div>Daily Budget: <strong id="daily-budget">NPR 6,000</strong>/day</div>
                    <div style="margin-top: 0.5rem;">Estimated Tier: <span class="tier-badge" id="tier-badge">Mid-Range Traveler</span></div>
                </div>
            </div>

            <!-- Step 4: Interests -->
            <div class="step-section" id="step-4">
                <h2 class="section-title">What interests you most?</h2>
                <p class="section-subtitle">Select your preferred travel vibes to prioritize activity matching.</p>
                <div class="interests-grid">
                    <div class="interest-card selected" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="cultural" checked>
                        <div class="interest-title">Cultural & Heritage</div>
                    </div>
                    <div class="interest-card selected" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="adventure" checked>
                        <div class="interest-title">Adventure & Trekking</div>
                    </div>
                    <div class="interest-card" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="nature">
                        <div class="interest-title">Nature & Wildlife</div>
                    </div>
                    <div class="interest-card" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="food">
                        <div class="interest-title">Food & Cuisine</div>
                    </div>
                    <div class="interest-card" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="wellness">
                        <div class="interest-title">Wellness & Spiritual</div>
                    </div>
                    <div class="interest-card" onclick="toggleSelect(this)">
                        <input type="checkbox" name="interests[]" value="photography">
                        <div class="interest-title">Photography</div>
                    </div>
                </div>
            </div>

            <!-- Wizard Navigation Buttons -->
            <div class="wizard-buttons">
                <button type="button" class="btn btn-prev" id="btn-prev" onclick="changeStep(-1)" style="visibility: hidden;">&larr; Back</button>
                <button type="button" class="btn btn-next" id="btn-next" onclick="changeStep(1)">Next Step &rarr;</button>
                <button type="button" class="btn btn-gen" id="btn-gen" onclick="generateItinerary()" style="display: none;">Generate My Itinerary &rarr;</button>
            </div>
        </form>

        <!-- Dynamic Results Container -->
        <div id="result-container">
            <h2 id="plan-title" style="margin-bottom: 0.5rem;">Your Generated Itinerary</h2>
            <p id="plan-summary" style="color: var(--muted); margin-bottom: 1.5rem;"></p>
            <div id="days-wrapper"></div>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="button" class="btn btn-next" onclick="saveCurrentPlan()">Save Itinerary to Account</button>
                <button type="button" class="btn btn-prev" onclick="window.print()">Print Itinerary</button>
                <button type="button" class="btn btn-prev" onclick="location.reload()">Plan Another</button>
            </div>
        </div>
    </main>

    <script>
        let currentStep = 1;
        let generatedPlan = null;

        function toggleSelect(el) {
            const cb = el.querySelector('input[type="checkbox"]');
            cb.checked = !cb.checked;
            if (cb.checked) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        }

        function updateBudgetCalc() {
            const days = parseInt(document.getElementById('days').value) || 1;
            const budget = parseFloat(document.getElementById('budget').value) || 0;
            const perDay = Math.round(budget / days);
            document.getElementById('daily-budget').innerText = 'NPR ' + perDay.toLocaleString();

            const tierBadge = document.getElementById('tier-badge');
            if (perDay < 3000) {
                tierBadge.innerText = 'Budget Traveler';
                tierBadge.style.background = '#dcfce7';
                tierBadge.style.color = '#15803d';
            } else if (perDay <= 10000) {
                tierBadge.innerText = 'Mid-Range Traveler';
                tierBadge.style.background = '#dbeafe';
                tierBadge.style.color = '#1d4ed8';
            } else {
                tierBadge.innerText = 'Luxury Traveler';
                tierBadge.style.background = '#fef3c7';
                tierBadge.style.color = '#b45309';
            }
        }

        function changeStep(delta) {
            const nextStep = currentStep + delta;
            if (nextStep < 1 || nextStep > 4) return;

            document.getElementById(`step-${currentStep}`).classList.remove('active');
            document.querySelectorAll('.step-item')[currentStep - 1].classList.remove('active');

            currentStep = nextStep;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.querySelectorAll('.step-item')[currentStep - 1].classList.add('active');

            document.getElementById('btn-prev').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            if (currentStep === 4) {
                document.getElementById('btn-next').style.display = 'none';
                document.getElementById('btn-gen').style.display = 'block';
            } else {
                document.getElementById('btn-next').style.display = 'block';
                document.getElementById('btn-gen').style.display = 'none';
            }
        }

        async function generateItinerary() {
            const form = document.getElementById('generator-form');
            const destCheckboxes = form.querySelectorAll('input[name="destinations[]"]:checked');
            const selectedDestinations = Array.from(destCheckboxes).map(cb => parseInt(cb.value));

            if (selectedDestinations.length === 0) {
                alert('Please select at least one destination in Step 1.');
                return;
            }

            const interestCheckboxes = form.querySelectorAll('input[name="interests[]"]:checked');
            const selectedInterests = Array.from(interestCheckboxes).map(cb => cb.value);

            const payload = {
                destinations: selectedDestinations,
                start_date: document.getElementById('start_date').value,
                days: parseInt(document.getElementById('days').value),
                budget: parseFloat(document.getElementById('budget').value),
                interests: selectedInterests
            };

            const btnGen = document.getElementById('btn-gen');
            btnGen.disabled = true;
            btnGen.innerText = 'Optimizing Itinerary...';

            try {
                const res = await fetch('../api/itinerary/generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (!result.success) {
                    alert(result.message || 'Generation failed.');
                    btnGen.disabled = false;
                    btnGen.innerText = 'Generate My Itinerary \u2192';
                    return;
                }

                generatedPlan = result.data;
                renderGeneratedPlan(generatedPlan);
            } catch (err) {
                alert('Error generating itinerary: ' + err.message);
                btnGen.disabled = false;
                btnGen.innerText = 'Generate My Itinerary \u2192';
            }
        }

        function renderGeneratedPlan(plan) {
            document.getElementById('generator-form').style.display = 'none';
            document.getElementById('steps-bar').style.display = 'none';
            const container = document.getElementById('result-container');
            container.style.display = 'block';

            document.getElementById('plan-title').innerText = plan.title;
            document.getElementById('plan-summary').innerText = `Season: ${plan.season} · Total Days: ${plan.days.length} · Est Cost: NPR ${plan.budget_summary.total_estimated.toLocaleString()} / Budget NPR ${plan.budget_summary.total_budget.toLocaleString()}`;

            const wrapper = document.getElementById('days-wrapper');
            wrapper.innerHTML = '';

            plan.days.forEach(day => {
                const dayEl = document.createElement('div');
                dayEl.className = 'day-card';
                dayEl.innerHTML = `
                    <div style="font-weight: 700; font-size: 1.15rem; margin-bottom: 0.75rem;">
                        Day ${day.day_number}: ${day.destination} (${day.date})
                    </div>
                    <div class="slot-row">
                        <span><strong>Morning:</strong> ${day.slots.morning ? day.slots.morning.activity : 'Free Time'}</span>
                        <span>NPR ${day.slots.morning ? day.slots.morning.cost.toLocaleString() : 0}</span>
                    </div>
                    <div class="slot-row">
                        <span><strong>Afternoon:</strong> ${day.slots.afternoon ? day.slots.afternoon.activity : 'Free Time'}</span>
                        <span>NPR ${day.slots.afternoon ? day.slots.afternoon.cost.toLocaleString() : 0}</span>
                    </div>
                    <div class="slot-row">
                        <span><strong>Evening:</strong> ${day.slots.evening ? day.slots.evening.activity : 'Free Time'}</span>
                        <span>NPR ${day.slots.evening ? day.slots.evening.cost.toLocaleString() : 0}</span>
                    </div>
                    <div class="slot-row" style="border-bottom:none; margin-top:0.5rem; font-weight:600; color:var(--primary);">
                        <span>Stay: ${day.accommodation.name}</span>
                        <span>Day Total: NPR ${day.day_total.toLocaleString()}</span>
                    </div>
                `;
                wrapper.appendChild(dayEl);
            });
        }

        async function saveCurrentPlan() {
            if (!generatedPlan) return;
            try {
                const res = await fetch('../api/itinerary/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(generatedPlan)
                });
                const result = await res.json();
                if (result.success) {
                    alert('Itinerary saved successfully to your account!');
                    window.location.href = 'itineraries.php';
                } else {
                    alert('Failed to save itinerary: ' + result.message);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        // Auto-fill from URL query parameters and sessionStorage prefill
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const destParam = params.get('destination') || params.get('dest');
            const daysParam = params.get('days');
            const budgetParam = params.get('budget');
            const interestsParam = params.get('interests');
            const templateParam = params.get('template');

            let prefill = null;
            try {
                prefill = JSON.parse(sessionStorage.getItem('yp_prefill') || '{}');
            } catch (e) {}

            const targetDest = destParam || prefill?.destination;
            const targetDays = daysParam || prefill?.days;
            const targetBudget = budgetParam || prefill?.budget;

            // Preselect Destination
            if (targetDest) {
                document.querySelectorAll('.dest-option').forEach(opt => {
                    const cb = opt.querySelector('input[type="checkbox"]');
                    const name = opt.querySelector('.dest-name')?.textContent?.toLowerCase() || '';
                    if (cb.value === targetDest || (isNaN(targetDest) && name.includes(targetDest.toLowerCase()))) {
                        cb.checked = true;
                        opt.classList.add('selected');
                    }
                });
            }

            // Preselect multiple destinations from template
            if (templateParam) {
                const parts = decodeURIComponent(templateParam).split('→').map(s => s.trim().toLowerCase());
                document.querySelectorAll('.dest-option').forEach(opt => {
                    const cb = opt.querySelector('input[type="checkbox"]');
                    const name = opt.querySelector('.dest-name')?.textContent?.toLowerCase() || '';
                    if (parts.some(p => name.includes(p))) {
                        cb.checked = true;
                        opt.classList.add('selected');
                    }
                });
            }

            // Days Duration
            if (targetDays) {
                const daysInput = document.getElementById('days');
                if (daysInput) {
                    daysInput.value = targetDays;
                    const daysVal = document.getElementById('days-val');
                    if (daysVal) daysVal.innerText = targetDays;
                }
            }

            // Budget
            if (targetBudget) {
                const budgetInput = document.getElementById('budget');
                if (budgetInput) {
                    budgetInput.value = targetBudget;
                }
            }

            // Interests
            const activeInterests = interestsParam || sessionStorage.getItem('yp_interests');
            if (activeInterests) {
                const list = activeInterests.toLowerCase().split(',').map(s => s.trim());
                document.querySelectorAll('.interest-card').forEach(card => {
                    const cb = card.querySelector('input[type="checkbox"]');
                    const title = card.querySelector('.interest-title')?.textContent?.toLowerCase() || '';
                    const match = list.some(item => title.includes(item) || cb.value.includes(item));
                    if (match) {
                        cb.checked = true;
                        card.classList.add('selected');
                    }
                });
            }

            updateBudgetCalc();
        });
    </script>

</body>
</html>
