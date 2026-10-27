// Small progressive enhancements. Every page works without JavaScript.

// Mobile menu toggle.
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.nav-toggle');
    if (!toggle) return;
    const sidebar = toggle.closest('.sidebar');
    const open = sidebar.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
});

// Ask before destructive actions: <form data-confirm="Delete this?">.
let dialog;
function confirmDialog(message) {
    if (!dialog) {
        dialog = document.createElement('dialog');
        dialog.className = 'confirm-dialog';
        dialog.innerHTML = `
            <form method="dialog">
                <p></p>
                <div class="form-actions">
                    <button class="btn btn-ghost" value="cancel">Keep it</button>
                    <button class="btn btn-danger" value="confirm">Yes, continue</button>
                </div>
            </form>`;
        document.body.append(dialog);
    }
    dialog.querySelector('p').textContent = message;
    dialog.returnValue = '';
    dialog.showModal();
    dialog.querySelector('[value="cancel"]').focus();
    return new Promise((resolve) => {
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.dataset.confirm || form.dataset.confirmed) return;
    event.preventDefault();
    if (typeof HTMLDialogElement === 'undefined') {
        if (window.confirm(form.dataset.confirm)) form.submit();
        return;
    }
    if (await confirmDialog(form.dataset.confirm)) {
        form.dataset.confirmed = '1';
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
});

// Filter dropdowns apply as soon as they change.
document.addEventListener('change', (event) => {
    if (event.target.matches('select[data-autosubmit]')) event.target.form.requestSubmit();
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) window.print();
});
