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

// Person picker: turns <select data-person-picker="/api/people.php"> into a
// type-to-search box (ARIA combobox). The select stays in the form, hidden,
// and holds the chosen value.
function initPersonPicker(select) {
    const endpoint = select.dataset.personPicker;
    const wrap = document.createElement('div');
    wrap.className = 'picker';
    const input = document.createElement('input');
    const list = document.createElement('ul');
    const listId = select.id + '-list';

    input.type = 'text';
    input.id = select.id + '-search';
    input.autocomplete = 'off';
    input.placeholder = 'Type a name or person code';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', listId);
    for (const attr of ['aria-invalid', 'aria-describedby', 'required']) {
        if (select.hasAttribute(attr)) input.setAttribute(attr, select.getAttribute(attr));
    }
    input.disabled = select.disabled || !!select.closest('fieldset:disabled');
    list.id = listId;
    list.className = 'picker-list';
    list.setAttribute('role', 'listbox');
    list.hidden = true;

    const current = select.selectedOptions[0];
    if (current && current.value) input.value = current.textContent;

    // The visible label now points at the search box.
    const label = document.querySelector(`label[for="${select.id}"]`);
    if (label) label.htmlFor = input.id;

    select.hidden = true;
    select.tabIndex = -1;
    select.after(wrap);
    wrap.append(input, list);

    let results = [];
    let active = -1;
    let timer;
    let controller;

    const close = () => {
        list.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };

    const choose = (item) => {
        let option = [...select.options].find((o) => o.value === String(item.id));
        if (!option) {
            option = new Option(item.label, item.id);
            select.add(option);
        }
        select.value = String(item.id);
        input.value = item.label;
        close();
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const highlight = (index) => {
        const items = list.querySelectorAll('[role="option"]');
        items.forEach((li, i) => li.setAttribute('aria-selected', String(i === index)));
        active = index;
        if (items[index]) {
            input.setAttribute('aria-activedescendant', items[index].id);
            items[index].scrollIntoView({ block: 'nearest' });
        }
    };

    const render = (message) => {
        list.replaceChildren();
        if (message) {
            const li = document.createElement('li');
            li.className = 'picker-empty';
            li.textContent = message;
            list.append(li);
        }
        results.forEach((item, i) => {
            const li = document.createElement('li');
            li.id = `${listId}-${i}`;
            li.setAttribute('role', 'option');
            li.setAttribute('aria-selected', 'false');
            const name = document.createElement('span');
            name.className = 'picker-name';
            name.textContent = item.name;
            const meta = document.createElement('span');
            meta.className = 'picker-meta';
            meta.textContent = item.meta;
            li.append(name, meta);
            // mousedown so the choice lands before the input loses focus
            li.addEventListener('mousedown', (e) => { e.preventDefault(); choose(item); });
            list.append(li);
        });
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        if (results.length) highlight(0);
    };

    const search = async (q) => {
        controller?.abort();
        controller = new AbortController();
        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) throw new Error(res.status);
            results = (await res.json()).results;
            render(results.length ? '' : 'No one matches. Check the spelling or try the person code.');
        } catch (err) {
            if (err.name !== 'AbortError') { results = []; render('Search failed. Reload the page and try again.'); }
        }
    };

    input.addEventListener('input', () => {
        // Typing clears the previous choice until a new one is picked.
        if (select.value) select.value = '';
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results = []; close(); return; }
        timer = setTimeout(() => search(q), 150);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown' && !list.hidden) { e.preventDefault(); highlight(Math.min(active + 1, results.length - 1)); }
        else if (e.key === 'ArrowUp' && !list.hidden) { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
        else if (e.key === 'Enter' && !list.hidden && results[active]) { e.preventDefault(); choose(results[active]); }
        else if (e.key === 'Escape') { close(); }
    });

    input.addEventListener('blur', () => {
        close();
        // Leaving the box without picking anyone empties it, so it never shows a name that isn't selected.
        if (!select.value) input.value = '';
    });
}

document.querySelectorAll('select[data-person-picker]').forEach(initPersonPicker);

// QR codes for two-factor setup: <div data-qr="otpauth://..."> (needs the qrcode-generator script on the page).
document.querySelectorAll('[data-qr]').forEach((el) => {
    if (typeof window.qrcode !== 'function') return;
    const qr = window.qrcode(0, 'M');
    qr.addData(el.dataset.qr);
    qr.make();
    const img = document.createElement('img');
    img.src = qr.createDataURL(5, 2);
    img.alt = '';
    img.width = img.height = qr.getModuleCount() * 5 + 20;
    el.append(img);
});
