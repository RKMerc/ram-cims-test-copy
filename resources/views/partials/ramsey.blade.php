@php
    $ramsey = app(\App\Support\RamseyPersona::class);
    $ramseyStaff = $ramsey->staff();
    $ramseyPhysicians = \App\Support\ClinicRoster::names();
@endphp

<div class="ramsey">
    <button type="button" class="ramsey-toggle" id="ramseyToggle" aria-expanded="false" aria-controls="ramseyPanel">
        <span class="ramsey-toggle-mark">R</span>
        <span>RAMsey</span>
    </button>

    <section class="ramsey-panel" id="ramseyPanel" hidden aria-label="{{ $ramsey->title() }}" data-staff="{{ $ramseyStaff ? '1' : '0' }}" data-greeting="{{ $ramsey->greeting() }}">
        <div class="ramsey-head">
            <div>
                <strong id="ramseyTitle">{{ $ramsey->title() }}</strong>
                <p id="ramseyDescription">{{ $ramsey->description() }}</p>
            </div>
            <button type="button" class="ramsey-close" id="ramseyClose" aria-label="Close RAMsey">&times;</button>
        </div>
        @if($developerMode ?? false)
            <div class="ramsey-role" id="ramseyRole">
                <span>Dev preview</span>
                <button type="button" data-ramsey-view="student" class="{{ $ramseyStaff ? '' : 'is-active' }}" aria-pressed="{{ $ramseyStaff ? 'false' : 'true' }}">Student View</button>
                <button type="button" data-ramsey-view="staff" class="{{ $ramseyStaff ? 'is-active' : '' }}" aria-pressed="{{ $ramseyStaff ? 'true' : 'false' }}">Staff View</button>
            </div>
        @endif
        <div class="ramsey-body">
        <div class="ramsey-log" id="ramseyLog"></div>
        <div class="ramsey-chips" id="ramseyChips">
            @foreach($ramsey->chips() as $chip)
                <button type="button" @if(!empty($chip['ask'])) data-ask="{{ $chip['ask'] }}" @endif @if(!empty($chip['panel'])) data-panel="{{ $chip['panel'] }}" @endif>{{ $chip['label'] }}</button>
            @endforeach
        </div>
        <form class="ramsey-tools" id="ramseyRemind" hidden>
            <p>Email me when a slot opens</p>
            <select name="physician" required>
                <option value="">Physician</option>
                @foreach($ramseyPhysicians as $physician)
                    <option value="{{ $physician }}">{{ $physician }}</option>
                @endforeach
            </select>
            <input type="date" name="date" required>
            <input type="email" name="email" value="{{ $clinicAccount->EmailAddress ?? '' }}" placeholder="Email for the alert" required>
            <button type="submit">Save alert</button>
        </form>
        <form class="ramsey-tools" id="ramseyDuty" hidden>
            <p>Duty schedule quick-check</p>
            <select name="physician" required>
                <option value="">Attending physician</option>
                @foreach($ramseyPhysicians as $physician)
                    <option value="{{ $physician }}">{{ $physician }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ today()->toDateString() }}" required>
            <button type="submit">Check slots</button>
        </form>
        </div>
        <form class="ramsey-form" id="ramseyForm">
            <input type="text" id="ramseyInput" name="message" placeholder="Ask RAMsey..." maxlength="500" required>
            <button type="submit">Send</button>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('ramseyToggle');
    const panel = document.getElementById('ramseyPanel');
    const closeBtn = document.getElementById('ramseyClose');
    const log = document.getElementById('ramseyLog');
    const form = document.getElementById('ramseyForm');
    const input = document.getElementById('ramseyInput');
    const remind = document.getElementById('ramseyRemind');
    const duty = document.getElementById('ramseyDuty');
    const chips = document.getElementById('ramseyChips');
    const title = document.getElementById('ramseyTitle');
    const description = document.getElementById('ramseyDescription');
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function addMessage(text, role, links) {
        const wrap = document.createElement('div');
        const bubble = document.createElement('div');
        bubble.className = 'ramsey-msg ' + role;
        bubble.textContent = text;
        wrap.appendChild(bubble);

        if (links && links.length) {
            const row = document.createElement('div');
            row.className = 'ramsey-links';
            links.forEach(function (link) {
                const anchor = document.createElement('a');
                anchor.href = link.href;
                anchor.textContent = link.label;
                if (link.target) {
                    anchor.target = link.target;
                    anchor.rel = 'noopener';
                }
                row.appendChild(anchor);
            });
            bubble.appendChild(row);
        }

        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
    }

    function showGreeting() {
        if (!log.childElementCount) {
            addMessage(panel.dataset.greeting || '', 'bot');
        }
    }

    function renderChips(items) {
        chips.replaceChildren();
        (items || []).forEach(function (chip) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = chip.label;
            if (chip.ask) button.setAttribute('data-ask', chip.ask);
            if (chip.panel) button.setAttribute('data-panel', chip.panel);
            chips.appendChild(button);
        });
    }

    function syncTools() {
        remind.hidden = true;
        duty.hidden = true;
    }

    function applyPersona(data) {
        title.textContent = data.title;
        description.textContent = data.description;
        panel.setAttribute('aria-label', data.title);
        panel.dataset.greeting = data.greeting || '';
        panel.dataset.staff = data.staff ? '1' : '0';
        syncTools();
        renderChips(data.chips || []);
        document.querySelectorAll('[data-ramsey-view]').forEach(function (button) {
            const active = button.getAttribute('data-ramsey-view') === (data.staff ? 'staff' : 'student');
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        log.replaceChildren();
        if (!panel.hidden) showGreeting();
    }

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            showGreeting();
            input.focus();
        }
    }

    function errorText(data) {
        if (data.reply) return data.reply;
        if (data.errors) {
            const first = Object.values(data.errors)[0];
            if (first && first[0]) return first[0];
        }
        return '';
    }

    async function ask(message, extra, display) {
        addMessage(display || message, 'user');
        input.value = '';

        try {
            const response = await fetch('/ramsey/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(Object.assign({ message: message }, extra || {}))
            });
            const data = await response.json();
            addMessage(errorText(data) || 'I could not answer that just now.', 'bot', data.links || []);
        } catch (error) {
            addMessage('I could not reach the clinic assistant. Please try again.', 'bot');
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });
    closeBtn.addEventListener('click', function () { setOpen(false); });

    chips.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (!button) return;
        const panelName = button.getAttribute('data-panel');
        if (panelName === 'remind') {
            remind.hidden = false;
            remind.querySelector('select')?.focus();
            return;
        }
        if (panelName === 'duty') {
            duty.hidden = false;
            duty.querySelector('select')?.focus();
            return;
        }
        const prompt = button.getAttribute('data-ask');
        if (prompt) ask(prompt);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (message) ask(message);
    });

    remind.addEventListener('submit', async function (event) {
        event.preventDefault();
        const physician = remind.querySelector('[name="physician"]').value;
        const date = remind.querySelector('[name="date"]').value;
        const email = remind.querySelector('[name="email"]').value;
        addMessage('Alert me for ' + physician + ' on ' + date + '.', 'user');

        try {
            const response = await fetch('/ramsey/remind', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ email: email, physician: physician, date: date })
            });
            const data = await response.json();
            addMessage(errorText(data) || 'I could not save that reminder.', 'bot');
            if (response.ok) remind.querySelector('[name="date"]').value = '';
        } catch (error) {
            addMessage('I could not save that reminder.', 'bot');
        }
    });

    duty.addEventListener('submit', function (event) {
        event.preventDefault();
        const physician = duty.querySelector('[name="physician"]').value;
        const date = duty.querySelector('[name="date"]').value;
        if (!physician || !date) return;
        ask('Doctor Duty Schedule Quick-Check', { physician: physician, date: date }, 'Check duty for ' + physician + ' on ' + date + '.');
    });

    const roleBar = document.getElementById('ramseyRole');
    if (roleBar) {
        roleBar.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-ramsey-view]');
            if (!button) return;
            try {
                const response = await fetch('/ramsey/view', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ view: button.getAttribute('data-ramsey-view') })
                });
                const data = await response.json();
                if (!response.ok) {
                    addMessage(errorText(data) || 'I could not switch the preview.', 'bot');
                    return;
                }
                applyPersona(data);
            } catch (error) {
                addMessage('I could not switch the preview.', 'bot');
            }
        });
    }
});
</script>
