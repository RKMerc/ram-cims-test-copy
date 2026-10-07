<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700" rel="stylesheet">
<style>
    :root {
        --apc-blue: #003B7A;
        --apc-gold: #E1B11A;
        --apc-white: #ffffff;
        --apc-mist: #f4f7fb;
        --apc-ink: #10243f;
        --bs-primary: #003B7A;
        --bs-primary-rgb: 0, 59, 122;
        --bs-link-color: #003B7A;
        --bs-link-hover-color: #002a58;
        --bs-dark: #003B7A;
        --bs-dark-rgb: 0, 59, 122;
        --bs-body-font-family: "Source Sans 3", "Segoe UI", sans-serif;
        --bs-body-color: #10243f;
        --bs-border-radius: 0.75rem;
        --bs-focus-ring-color: rgba(225, 177, 26, 0.45);
    }

    body {
        font-family: "Source Sans 3", "Segoe UI", sans-serif;
        color: var(--apc-ink);
    }

    .btn-primary {
        --bs-btn-bg: #003B7A;
        --bs-btn-border-color: #003B7A;
        --bs-btn-hover-bg: #002a58;
        --bs-btn-hover-border-color: #002a58;
        --bs-btn-active-bg: #002244;
        --bs-btn-active-border-color: #002244;
        --bs-btn-disabled-bg: #003B7A;
        --bs-btn-disabled-border-color: #003B7A;
    }

    .btn-outline-primary {
        --bs-btn-color: #003B7A;
        --bs-btn-border-color: #E1B11A;
        --bs-btn-hover-color: #003B7A;
        --bs-btn-hover-bg: #E1B11A;
        --bs-btn-hover-border-color: #E1B11A;
        --bs-btn-active-color: #003B7A;
        --bs-btn-active-bg: #c99a12;
        --bs-btn-active-border-color: #c99a12;
    }

    .btn-dark {
        --bs-btn-bg: #003B7A;
        --bs-btn-border-color: #003B7A;
        --bs-btn-hover-bg: #002a58;
        --bs-btn-hover-border-color: #E1B11A;
    }

    .text-primary { color: #003B7A !important; }
    .bg-primary { background-color: #003B7A !important; }
    .border-primary { border-color: #E1B11A !important; }

    .bg-dark,
    .card-header.bg-dark {
        background-color: #003B7A !important;
    }

    .table-dark {
        --bs-table-bg: #003B7A;
        --bs-table-color: #fff;
        --bs-table-border-color: rgba(225, 177, 26, 0.35);
        --bs-table-striped-bg: #0a4688;
        --bs-table-hover-bg: #145294;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #E1B11A;
        box-shadow: 0 0 0 0.2rem rgba(225, 177, 26, 0.28);
    }

    .app-shell {
        min-height: 100vh;
        background: var(--apc-mist);
        position: relative;
    }

    .app-shell::before {
        content: "";
        position: fixed;
        inset: 0;
        background: url("{{ asset('images/apc-logo.png') }}") center 42% / min(520px, 70vw) no-repeat;
        opacity: 0.1;
        pointer-events: none;
        z-index: 0;
    }

    .app-nav,
    .app-main,
    .app-footer,
    .ramsey {
        position: relative;
        z-index: 1;
    }

    .app-nav {
        background: #003B7A;
        border-bottom: 4px solid #E1B11A;
        box-shadow: 0 8px 24px rgba(0, 59, 122, 0.18);
    }

    .brand-lockup {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #fff;
        text-decoration: none;
    }

    .brand-lockup:hover { color: #fff; }

    .brand-mark {
        width: 52px;
        height: 52px;
        border-radius: 8px;
        background: #E1B11A;
        padding: 0;
        object-fit: cover;
    }

    .brand-lockup strong {
        display: block;
        letter-spacing: 0.04em;
        font-size: 1.05rem;
        line-height: 1.1;
    }

    .brand-lockup small {
        display: block;
        color: #E1B11A;
        letter-spacing: 0.02em;
    }

    .app-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }

    .app-links a {
        color: rgba(255, 255, 255, 0.84);
        text-decoration: none;
        font-weight: 600;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
    }

    .app-links a:hover,
    .app-links a.active {
        color: #003B7A;
        background: #E1B11A;
    }

    .app-main {
        width: min(1140px, calc(100% - 2rem));
        margin: 0 auto;
        padding: 1.75rem 0 5.5rem;
    }

    .app-shell .card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(1, 45, 96, 0.06);
    }

    .app-shell .card-header {
        border-bottom: 0;
    }

    .app-shell .table {
        --bs-table-bg: transparent;
    }

    .app-shell thead.table-light {
        --bs-table-bg: #f8f1d4;
        --bs-table-color: #003B7A;
    }

    .app-shell h1,
    .app-shell h2,
    .app-shell .h3 {
        letter-spacing: -0.02em;
    }

    .app-footer {
        color: #5c6b82;
        text-align: center;
        font-size: 0.875rem;
        padding-bottom: 1.5rem;
    }

    .ramsey-toggle {
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: 1040;
        display: flex;
        align-items: center;
        gap: 0.55rem;
        border: 2px solid #E1B11A;
        border-radius: 999px;
        background: #003B7A;
        color: #fff;
        font-weight: 700;
        padding: 0.7rem 1rem 0.7rem 0.7rem;
        box-shadow: 0 12px 28px rgba(1, 45, 96, 0.28);
    }

    .ramsey-toggle:hover { background: #002a58; }

    .ramsey-toggle-mark {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #E1B11A;
        color: #003B7A;
        display: grid;
        place-items: center;
        font-weight: 700;
    }

    .ramsey-panel {
        position: fixed;
        right: 1.25rem;
        bottom: 5rem;
        z-index: 1040;
        width: min(380px, calc(100vw - 1.5rem));
        height: min(540px, calc(100vh - 7rem));
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 50px rgba(1, 45, 96, 0.22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .ramsey-panel[hidden] { display: none !important; }

    .ramsey-head {
        background: #003B7A;
        border-bottom: 3px solid #E1B11A;
        color: #fff;
        padding: 0.9rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .ramsey-head p {
        margin: 0;
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.82rem;
    }

    .ramsey-close {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 1.4rem;
        line-height: 1;
    }

    .ramsey-log {
        flex: 1;
        overflow: auto;
        padding: 1rem;
        background:
            linear-gradient(180deg, rgba(231, 237, 246, 0.65), #fff 90px);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .ramsey-msg {
        max-width: 92%;
        padding: 0.7rem 0.85rem;
        border-radius: 14px;
        white-space: pre-wrap;
        line-height: 1.45;
    }

    .ramsey-msg.bot {
        background: #eef2fa;
        color: #10243f;
        border-bottom-left-radius: 4px;
    }

    .ramsey-msg.user {
        margin-left: auto;
        background: #003B7A;
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .ramsey-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.55rem;
    }

    .ramsey-links a,
    .ramsey-chips button,
    .ramsey-remind button {
        border: 1px solid #E1B11A;
        background: #fff;
        color: #003B7A;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
    }

    .ramsey-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        padding: 0 1rem 0.75rem;
    }

    .ramsey-form,
    .ramsey-remind {
        display: flex;
        gap: 0.45rem;
        padding: 0.75rem;
        border-top: 1px solid #e6ebf3;
        background: #fff;
    }

    .ramsey-form input,
    .ramsey-remind input {
        flex: 1;
        border: 1px solid #d5deea;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
    }

    .ramsey-form button,
    .ramsey-remind button[type="submit"] {
        border: 0;
        border-radius: 999px;
        background: #003B7A;
        color: #fff;
        font-weight: 700;
        padding: 0.45rem 0.9rem;
    }

    .guest-shell {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 1.5rem;
        background: #003B7A;
        color: #fff;
        position: relative;
    }

    .guest-shell::before {
        content: "";
        position: fixed;
        inset: 0;
        background: url("{{ asset('images/apc-logo.png') }}") center / min(640px, 90vw) no-repeat;
        opacity: 0.16;
        pointer-events: none;
    }

    .guest-card {
        position: relative;
        z-index: 1;
        width: min(460px, 100%);
        background: #fff;
        color: #10243f;
        border-radius: 22px;
        padding: 2rem 1.75rem;
        text-align: center;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
    }

    .guest-card img {
        width: 148px;
        height: auto;
        margin-bottom: 0.5rem;
    }

    .guest-card .eyebrow {
        color: #003B7A;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-size: 0.78rem;
    }

    .guest-actions {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        margin-top: 1.25rem;
    }

    .guest-note {
        margin-top: 1rem;
        color: #5c6b82;
        font-size: 0.9rem;
    }

    .guest-card-roles {
        width: min(460px, 100%);
    }

    .role-folders {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
        text-align: left;
    }

    .role-folder {
        margin-top: 1.5rem;
    }

    .role-folder-tab {
        display: inline-block;
        margin: 0;
        background: #003B7A;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.42rem 1rem 0.38rem;
        border-radius: 12px 12px 0 0;
    }

    .role-folder-body {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        min-height: 0;
        background: #f7f9fc;
        border: 1px solid #d5deea;
        border-radius: 16px;
        padding: 1rem 0.95rem 0.95rem;
    }

    .guest-card-roles .guest-actions {
        width: min(420px, 100%);
        margin-left: auto;
        margin-right: auto;
    }

    .role-folder-body h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.3;
        color: #10243f;
    }

    .role-folder-body p {
        margin: 0;
        color: #3d4f68;
        flex: 1;
    }

    .role-folder-body .btn {
        margin-top: auto;
        white-space: normal;
        line-height: 1.25;
    }

</style>
