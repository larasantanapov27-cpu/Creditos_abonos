const { chromium } = require('C:/Users/laras/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('fs');
const assert = require('node:assert/strict');
const base = process.env.QA_URL || 'http://127.0.0.1:8001';
const out = 'storage/app/private/qa';
fs.mkdirSync(out, { recursive: true });
(async () => {
    const browser = await chromium.launch({
        headless: true,
        executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'
    });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const errors = [], external = [], timings = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text())
    });
    await context.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin !== new URL(base).origin) {
            external.push(url.href);
            return route.abort();
        }
        return route.continue();
    });
    const visit = async (path, name) => {
        const start = performance.now();
        const response = await page.goto(base + path);
        assert(response.ok(), path + ' HTTP ' + response.status());
        await page.waitForLoadState('networkidle');
        timings.push({ pantalla: name, ms: Math.round(performance.now() - start) });
        await page.screenshot({ path: `${out}/${name}-desktop.png`, fullPage: true });
    };
    await visit('/login', 'login');
    await page.locator('[data-password]').click();
    assert.equal(await page.locator('#password').getAttribute('type'), 'text');
    await page.locator('[data-password]').click();
    await visit('/registro', 'registro');
    const suffix = Date.now().toString();
    await page.locator('#name').fill('Verificación navegador');
    await page.locator('#username').fill('qa' + suffix);
    await page.locator('#email').fill('qa' + suffix + '@example.test');
    await page.locator('#password').fill('PruebaLocal2026!');
    await page.locator('#password_confirmation').fill('PruebaLocal2026!');
    await page.locator('button[type=submit]').click();
    await page.waitForURL('**/login');
    assert(await page.getByRole('status').isVisible());
    await page.locator('.alert .btn-close').click();
    await page.locator('.alert').waitFor({ state: 'detached' });
    await page.locator('#username').fill('demo');
    await page.locator('#password').fill('DemoLocal2026!');
    await page.locator('button[type=submit]').click();
    await page.waitForURL('**/home');
    await visit('/home', 'home');
    await page.getByRole('button', { name: 'Abrir menú de cuenta' }).click();
    await page.locator('.dropdown-menu.show').waitFor();
    await page.keyboard.press('Escape');
    for (const [path, name] of [
        ['/clientes', 'clientes'],
        ['/clientes/create', 'cliente-formulario'],
        ['/creditos', 'creditos'],
        ['/creditos/create', 'credito-formulario'],
        ['/abonos', 'abonos'],
        ['/abonos/create', 'abono-formulario'],
        ['/consultas', 'consultas'],
        ['/catalogos', 'catalogos']
    ]) await visit(path, name);
    // Modal de edición del catálogo sin enviar cambios.
    await page.getByRole('button', { name: 'Editar', exact: true }).first().click();
    await page.locator('.modal.show').waitFor();
    await page.locator('.modal.show .btn-close').click();
    await page.locator('.modal.show').waitFor({ state: 'hidden' });
    // Alta de un cliente en la base de verificación.
    await page.goto(base + '/clientes/create');
    for (const [id, v] of Object.entries({
        nombre: 'Elena',
        apellido_paterno: 'Prueba',
        apellido_materno: 'Local',
        telefono: ('55' + suffix.slice(-8)),
        correo_electronico: 'elena@example.test',
        curp: ('PULE900101MMCRCL' + suffix.slice(-2)),
        direccion: 'Calle de verificación 1',
        codigo_postal: '51200'
    })) await page.locator('#' + id).fill(v);
    await page.locator('#id_estado').selectOption({ label: 'Estado de México' });
    await page.locator('#id_municipio').selectOption({ label: 'Valle de Bravo' });
    await page.getByRole('button', { name: 'Guardar cliente' }).click();
    await page.waitForURL(/\/clientes\/\d+$/);
    const clientUrl = page.url();
    await page.getByRole('link', { name: 'Nuevo crédito', exact: true }).click();
    await page.locator('#monto_credito').fill('100.00');
    await page.locator('#motivo_credito').fill('Verificación sin internet');
    await page.locator('#fecha_aprobacion').fill('2026-10-18');
    await page.locator('#fecha_pago').fill('2026-10-31');
    await page.locator('#cantidad_pagos').fill('3');
    await page.locator('#periodicidad').selectOption('mensual');
    await page.locator('#previewCalendar').click();
    await page.locator('#saveCredit').waitFor({ state: 'visible' });
    await page.waitForFunction(() => !document.getElementById('saveCredit').disabled);
    assert.equal(await page.locator('#fecha_vencimiento').inputValue(), '2026-12-31');
    assert.equal(await page.locator('#calendarRows tr').count(), 3);
    assert((await page.locator('#calendarRows').innerText()).includes('33.34'));
    await page.screenshot({ path: `${out}/calendario-desktop.png`, fullPage: true });
    await page.locator('#saveCredit').click();
    await page.locator('#confirmModal.show').waitFor();
    await page.locator('#confirmSubmit').click();
    await page.waitForURL(/\/creditos\/\d+$/);
    const creditUrl = page.url();
    await page.screenshot({ path: `${out}/credito-detalle-desktop.png`, fullPage: true });
    await page.getByRole('link', { name: 'Registrar abono' }).click();
    await page.locator('#monto_abono').fill('40.00');
    assert.equal(await page.locator('#paymentResult').innerText(), '$60.00');
    await page.locator('#savePayment').click();
    await page.locator('#confirmModal.show').waitFor();
    await page.locator('#confirmSubmit').click();
    await page.waitForURL(/\/abonos\/\d+$/);
    await page.getByText('$40.00 MXN', { exact: true }).waitFor();
    await page.screenshot({ path: `${out}/comprobante-desktop.png`, fullPage: true });
    await page.locator('#motivo').fill('Cancelación de prueba de navegador');
    await page.getByRole('button', { name: 'Cancelar abono', exact: true }).click();
    await page.locator('#confirmModal.show').waitFor();
    await page.locator('#confirmSubmit').click();
    await page.getByText('ABONO CANCELADO', { exact: true }).waitFor();
    await page.goto(creditUrl);
    assert((await page.locator('main').innerText()).includes('$100.00'));
    // Cierre de sesión e inaccesibilidad de rutas internas.
    await page.getByRole('button', { name: 'Abrir menú de cuenta' }).click();
    await page.locator('.dropdown-menu.show button').click();
    await page.waitForURL('**/login');
    await page.goto(base + '/home');
    assert(page.url().endsWith('/login'));
    // Vista móvil real, sin solicitudes externas permitidas.
    await page.setViewportSize({ width: 390, height: 844 });
    await visit('/login', 'login-mobile');
    await visit('/registro', 'registro-mobile');
    await page.goto(base + '/login');
    await page.locator('#username').fill('demo');
    await page.locator('#password').fill('DemoLocal2026!');
    await page.locator('button[type=submit]').click();
    await page.waitForURL('**/home');
    await page.getByRole('button', { name: 'Menú', exact: false }).first().click();
    await page.locator('#navegacion.show').waitFor();
    await page.getByRole('link', { name: 'Clientes', exact: true }).click();
    await page.waitForURL('**/clientes');
    for (const [path, name] of [
        ['/home', 'home-mobile'],
        ['/clientes', 'clientes-mobile'],
        ['/clientes/create', 'cliente-formulario-mobile'],
        ['/creditos', 'creditos-mobile'],
        ['/creditos/create', 'credito-formulario-mobile'],
        ['/abonos', 'abonos-mobile'],
        ['/abonos/create', 'abono-formulario-mobile']
    ]) {
        await visit(path, name);
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), name + ' desborda horizontalmente');
    }
    assert.deepEqual(external, [], 'Solicitudes externas');
    assert.deepEqual(errors, [], 'Errores JS');
    const report = {
        fecha: new Date().toISOString(),
        navegador: 'Edge headless instalado',
        resoluciones: ['1440x1000', '390x844'],
        red: 'Solo se permiten solicitudes al origen local; todas las externas se abortan',
        solicitudesExternas: external,
        errores: errors,
        tiempos: timings,
        flujo: 'Registro, login, logout, cliente, calendario, crédito, abono, cancelación, menú móvil, desplegable, alertas, modales'
    };
    fs.writeFileSync(out + '/informe-navegador.json', JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
    await browser.close();
})().catch(e => {
    console.error(e);
    process.exit(1)
});
