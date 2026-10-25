/* Recursos locales; ninguna petición a servicios externos. */
'use strict';
(() => {
    const money = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
    const cents = value => {
        const s = String(value);
        if (!/^\d+(\.\d{1,2})?$/.test(s)) return NaN;
        const [a, b = ''] = s.split('.');
        return Number(a) * 100 + Number(b.padEnd(2, '0'));
    };

    document.querySelectorAll('[data-password]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.password);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Ocultar' : 'Mostrar';
        button.setAttribute('aria-pressed', String(show));
    }));

    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));

    const state = document.querySelector('[data-state-select]'), city = document.querySelector('[data-city-select]');
    if (state && city) {
        const options = [...city.options].slice(1);
        const filter = () => {
            const selected = city.value;
            city.replaceChildren(new Option('Selecciona un municipio', ''));
            options.filter(o => o.dataset.state === state.value).forEach(o => city.append(o));
            if ([...city.options].some(o => o.value === selected)) city.value = selected;
        };
        state.addEventListener('change', filter);
        filter();
    }

    document.querySelectorAll('select[data-readonly]').forEach(select => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = select.name;
        hidden.value = select.value;
        select.after(hidden);
        select.disabled = true;
    });

    let pending = null;

    const modalElement = document.getElementById('confirmModal');

    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
        if (form.dataset.confirmed === '1') {
            form.querySelectorAll('[type="submit"]').forEach(b => b.disabled = true);
            return;
        }
        event.preventDefault();
        pending = form;
        document.getElementById('confirmText').textContent = form.dataset.confirm;
        if (form.matches('[data-payment-form]')) document.getElementById('confirmText').textContent += ` Importe: ${money(cents(form.elements.monto_abono.value) / 100)}. Saldo resultante: ${document.getElementById('paymentResult').textContent}.`;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }));

    document.getElementById('confirmSubmit')?.addEventListener('click', () => {
        if (pending) {
            const form = pending;
            pending = null;
            form.dataset.confirmed = '1';
            bootstrap.Modal.getInstance(modalElement).hide();
            form.requestSubmit();
        }
    });
    modalElement?.addEventListener('hidden.bs.modal', () => {
        pending = null;
    });

    const creditForm = document.querySelector('[data-credit-form]');
    if (creditForm && creditForm.dataset.locked !== '1') {
        const save = document.getElementById('saveCredit'), error = document.getElementById('calendarError'), button = document.getElementById('previewCalendar');
        let generation = 0;
        creditForm.addEventListener('input', () => {
            save.disabled = true;
            generation++;
        });
        creditForm.addEventListener('change', () => {
            save.disabled = true;
            generation++;
        });
        button.addEventListener('click', async () => {
            error.hidden = true;
            save.disabled = true;
            const d = new FormData(creditForm);
            d.delete('_method');
            const first = d.get('fecha_pago'), n = Number(d.get('cantidad_pagos')), period = d.get('periodicidad');
            if (!first || !Number.isInteger(n) || n < 1 || n > 600) {
                error.textContent = 'Indica el primer pago y una cantidad de 1 a 600 pagos.';
                error.hidden = false;
                return;
            }
            const [y, m, day] = first.split('-').map(Number);
            let last;
            if (period === 'mensual') {
                const base = new Date(Date.UTC(y, m - 1 + n - 1, 1));
                const max = new Date(Date.UTC(base.getUTCFullYear(), base.getUTCMonth() + 1, 0)).getUTCDate();
                last = new Date(Date.UTC(base.getUTCFullYear(), base.getUTCMonth(), Math.min(day, max)));
            }
            else last = new Date(Date.UTC(y, m - 1, day + (n - 1) * (period === 'semanal' ? 7 : 15)));
            creditForm.elements.fecha_vencimiento.value = last.toISOString().slice(0, 10);
            d.set('fecha_vencimiento', creditForm.elements.fecha_vencimiento.value);
            if (!creditForm.reportValidity()) return;
            button.disabled = true;
            const currentGeneration = generation;
            try {
                const response = await fetch(creditForm.dataset.previewUrl, {
                    method: 'POST',
                    body: d,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : 'No fue posible calcular el calendario. Recarga la página.');
                if (currentGeneration !== generation) throw new Error('Cambiaste las condiciones. Calcula el calendario de nuevo.');
                const body = document.getElementById('calendarRows');
                body.replaceChildren();
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    [row.numero, row.fecha.split('-').reverse().join('/'), money(row.importe)].forEach(value => {
                        const td = document.createElement('td');
                        td.textContent = value;
                        tr.append(td);
                    });
                    body.append(tr);
                });
                document.getElementById('calendarHelp').textContent = `${data.length} cuotas. Total pactado: ${money(d.get('monto_credito'))}. Revisa y confirma para guardar.`;
                save.disabled = false;
            } catch (e) {
                error.textContent = e.message;
                error.hidden = false;
            } finally {
                button.disabled = false;
            }
        });
    }

    const payment = document.querySelector('[data-payment-form]');
    if (payment) {
        const select = payment.elements.id_credito, input = payment.elements.monto_abono, button = document.getElementById('savePayment');
        const update = () => {
            const o = select.selectedOptions[0], balance = cents(o?.dataset.balance ?? ''), amount = cents(input.value), valid = Number.isFinite(balance) && Number.isFinite(amount) && amount > 0 && amount <= balance;
            document.getElementById('paymentCredit').textContent = select.value ? o.textContent : 'Selecciona un crédito para consultar su saldo.';
            for (const [id, value] of [
                ['paymentTotal', o?.dataset.total],
                ['paymentPaid', o?.dataset.paid],
                ['paymentBalance', o?.dataset.balance]
            ]) document.getElementById(id).textContent = value ? money(value) : '—';
            document.getElementById('paymentAmount').textContent = Number.isFinite(amount) ? money(amount / 100) : '—';
            document.getElementById('paymentResult').textContent = valid ? money((balance - amount) / 100) : '—';
            document.getElementById('paymentHint').textContent = valid
                ? (balance === amount
                    ? 'Este abono liquidará el crédito.'
                    : 'El saldo se actualizará al confirmar el abono.')
                : 'Elige un crédito e ingresa un importe mayor que cero y hasta el saldo disponible.';
            if (Number.isFinite(balance)) input.max = (balance / 100).toFixed(2);
            button.disabled = !valid;
        };
        select.addEventListener('change', update);
        input.addEventListener('input', update);
        update();
    }
})();
