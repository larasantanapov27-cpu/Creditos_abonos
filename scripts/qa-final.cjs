const { chromium } = require('C:/Users/laras/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('fs'), assert = require('node:assert/strict'), { randomUUID } = require('crypto');
(async () => {
    const b = await chromium.launch({
        headless: true,
        executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'
    });
    const c = await b.newContext({ viewport: { width: 1440, height: 1000 } }), p = await c.newPage();
    const base = 'http://127.0.0.1:8001', out = 'storage/app/private/qa', external = [], errors = [], timings = [];
    p.on('pageerror', e => errors.push(e.message));
    await c.route('**/*', r => new URL(r.request().url()).origin === base ? r.continue() : (external.push(r.request().url()), r.abort()));
    for (const [w, h] of [[1440, 1000], [390, 844]]) {
        await p.setViewportSize({ width: w, height: h });
        for (const path of ['login', 'registro']) {
            await p.goto(base + '/' + path);
            await p.screenshot({ path: `${out}/${path}-final-${w}.png`, fullPage: true });
        }
    }
    await p.goto(base + '/login');
    await p.locator('#username').fill('demo');
    await p.locator('#password').fill('DemoLocal2026!');
    await p.locator('button[type=submit]').click();
    await p.waitForURL('**/home');
    for (const [w, h] of [[1440, 1000], [390, 844]]) {
        await p.setViewportSize({ width: w, height: h });
        for (const path of [
            'home',
            'clientes',
            'clientes/create',
            'creditos',
            'creditos/create',
            'abonos',
            'abonos/create'
        ]) {
            await p.goto(base + '/' + path);
            await p.screenshot({ path: `${out}/${path.replace('/', '-')}-final-${w}.png`, fullPage: true });
            assert(await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
        }
    }
    // Tiempos HTTP sin screenshots ni espera artificial de networkidle.
    const csrf = await p.locator('meta[name=csrf-token]').getAttribute('content');
    for (const path of ['home', 'clientes', 'creditos', 'abonos', 'consultas']) {
        const samples = [];
        for (let i = 0; i < 3; i++) {
            const start = performance.now();
            const r = await c.request.get(base + '/' + path);
            assert(r.ok());
            samples.push(Math.round(performance.now() - start));
        }
        timings.push({ operacion: 'GET /' + path, muestras_ms: samples });
    }
    const create = async (path, data) => {
        const start = performance.now();
        const r = await c.request.post(base + path, { form: { _token: csrf, ...data }, maxRedirects: 0 });
        timings.push({
            operacion: 'POST ' + path,
            ms: Math.round(performance.now() - start),
            status: r.status()
        });
        assert.equal(r.status(), 302);
        return r.headers().location;
    };
    const creditUrl = await create('/creditos', {
        id_cliente: '1',
        motivo_credito: 'Medición aislada de operación',
        monto_credito: '30.00',
        fecha_aprobacion: '2026-10-18',
        fecha_pago: '2026-11-01',
        fecha_vencimiento: '2026-11-15',
        cantidad_pagos: '3',
        periodicidad: 'semanal',
        id_tasa_interes: '1',
        id_tipo_pago: '1'
    });
    assert(/\/creditos\/\d+$/.test(creditUrl));
    const id = creditUrl.split('/').pop();
    const receipt = await create('/abonos', { id_credito: id, monto_abono: '10.00', token: randomUUID() });
    assert(/\/abonos\/\d+$/.test(receipt));
    await p.goto(receipt);
    await p.emulateMedia({ media: 'print' });
    await p.screenshot({ path: out + '/comprobante-impresion.png', fullPage: true });
    await p.emulateMedia({ media: 'screen' });
    await create('/abonos/' + receipt.split('/').pop() + '/cancelar', { motivo: 'Cancelar movimiento de medición aislada' });
    assert.deepEqual(external, []);
    assert.deepEqual(errors, []);
    fs.writeFileSync(out + '/mediciones-http.json', JSON.stringify({
        fecha: new Date().toISOString(),
        entorno: 'Windows, PHP 8.2.12, Laravel 12, MariaDB 10.4.32, Edge headless, servidor artisan local, base de prueba',
        nota: 'Muestras HTTP secuenciales. No prueba de carga. Datos de prueba separados.',
        externas: external,
        errores: errors,
        tiempos: timings
    }, null, 2));
    console.log(JSON.stringify(timings));
    // Comprueba acceso bajo Apache y protección de archivos si está encendido.
    try {
        for (const path of [
            '/creditos_abonos/public/login',
            '/creditos_abonos/.env',
            '/creditos_abonos/storage/logs/laravel.log'
        ]) {
            const r = await c.request.get('http://localhost' + path);
            console.log('Apache', path, r.status());
        }
    } catch (e) {
        console.log('Apache no disponible:', e.message.slice(0, 100));
    }
    await b.close();
})().catch(e => {
    console.error(e);
    process.exit(1)
});
