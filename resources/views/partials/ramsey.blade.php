<div class="ramsey">
    <button type="button" class="ramsey-toggle" id="ramseyToggle" aria-expanded="false" aria-controls="ramseyPanel">
        <span class="ramsey-toggle-mark">R</span>
        <span>RAMsey</span>
    </button>

    <section class="ramsey-panel" id="ramseyPanel" hidden aria-label="RAMsey clinic assistant">
        <div class="ramsey-head">
            <div>
                <strong>RAMsey</strong>
                <p>Clinic assistant for visits, records, and reminders</p>
            </div>
            <button type="button" class="ramsey-close" id="ramseyClose" aria-label="Close RAMsey">&times;</button>
        </div>
        <div class="ramsey-log" id="ramseyLog"></div>
        <div class="ramsey-chips" id="ramseyChips">
            <button type="button" data-ask="Is there an open clinic slot?">Check availability</button>
            <button type="button" data-ask="Where are user accounts stored?">Account database</button>
            <button type="button" data-ask="What can you help with?">What RAMsey does</button>
        </div>
        <form class="ramsey-remind" id="ramseyRemind" hidden>
            <input type="email" name="email" placeholder="Email for the reminder" required>
            <button type="submit">Remind me</button>
        </form>
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
                row.appendChild(anchor);
            });
            bubble.appendChild(row);
        }

        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
    }

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && !log.childElementCount) {
            addMessage('I can help you move through the clinic, check physician availability, and save an email reminder when no slot is open.', 'bot');
        }
        if (open) input.focus();
    }

    async function ask(message) {
        addMessage(message, 'user');
        input.value = '';
        remind.hidden = true;

        try {
            const response = await fetch('/ramsey/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            addMessage(data.reply || 'I could not answer that just now.', 'bot', data.links || []);
            remind.hidden = !data.needs_reminder;
        } catch (error) {
            addMessage('I could not reach the clinic assistant. Please try again.', 'bot');
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });
    closeBtn.addEventListener('click', function () { setOpen(false); });

    document.getElementById('ramseyChips').addEventListener('click', function (event) {
        const button = event.target.closest('[data-ask]');
        if (button) ask(button.getAttribute('data-ask'));
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (message) ask(message);
    });

    remind.addEventListener('submit', async function (event) {
        event.preventDefault();
        const email = remind.querySelector('input[name="email"]').value;
        try {
            const response = await fetch('/ramsey/remind', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ email: email })
            });
            const data = await response.json();
            addMessage(data.reply || 'Reminder saved.', 'bot');
            remind.hidden = true;
            remind.reset();
        } catch (error) {
            addMessage('I could not save that reminder.', 'bot');
        }
    });
});
</script>
