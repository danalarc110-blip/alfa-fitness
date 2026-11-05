// Runs the existing acceptance suites against a new disposable database only.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const assert = require('node:assert/strict');
const {spawn, spawnSync} = require('node:child_process');
const root = path.resolve(__dirname, '..');
const php = process.env.ALPHA_QA_PHP || 'C:/xampp/php/php.exe';
const playwright = process.env.ALPHA_PLAYWRIGHT_MODULE || 'C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1030-493-0_26.1030.493.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules/playwright';
const out = path.join(root, 'outputs/revision-2026-11-04/segunda-revision');
fs.mkdirSync(out, {recursive: true});
const scripts = {
    settings: 'tools/settings_browser_check.cjs',
    forms: 'tools/design_forms_check.cjs',
    matrix: 'tools/design_browser_check.cjs',
    staff: 'tools/design_browser_check.cjs',
};
const selected = process.argv.slice(2);
const suites = selected.length ? selected : ['settings', 'forms', 'matrix'];
assert.ok(suites.every(name => Object.hasOwn(scripts, name)));
let server;
const passed = [];
const available = () => new Promise((resolve, reject) => {
    const probe = net.createServer();
    probe.once('error', reject);
    probe.listen(8021, '127.0.0.1', () => probe.close(resolve));
});
const execute = (command, args, env = process.env) => new Promise((resolve, reject) => {
    const child = spawn(command, args, {cwd: root, env, windowsHide: true, stdio: 'inherit'});
    child.once('error', reject);
    child.once('exit', code => code === 0 ? resolve() : reject(new Error(args.join(' ') + ' exited with ' + code)));
});
(async () => {
    await available();
    const qaConfigCache = path.join(os.tmpdir(), 'alpha-browser-config-' + process.pid + '-' + Date.now() + '.php');
    assert.equal(fs.existsSync(qaConfigCache), false);
    const qaEnv = {...process.env, APP_CONFIG_CACHE: qaConfigCache};
    const prepared = spawnSync(php, ['tools/prepare_browser_qa.php'], {cwd: root, env: qaEnv, encoding: 'utf8', windowsHide: true, timeout: 120000});
    assert.equal(prepared.status, 0, prepared.stderr || 'Fixture preparation failed');
    const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/browser-qa.json'), 'utf8'));
    assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
    assert.ok(path.basename(cfg.database).startsWith('alp'));
    cfg.url = 'http://127.0.0.1:8021';
    fs.writeFileSync(path.join(root, 'storage/app/private/design-browser-qa.json'), JSON.stringify(cfg));
    fs.mkdirSync(path.join(root, 'storage/app/private/design-check'), {recursive: true});
    const env = {...qaEnv, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: cfg.url, DB_CONNECTION: 'sqlite', DB_DATABASE: cfg.database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_second_review_qa', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync', ALPHA_PLAYWRIGHT_MODULE: playwright};
    const log = fs.openSync(path.join(out, 'server.log'), 'a');
    server = spawn(php, ['-d', 'extension=zip', '-S', '127.0.0.1:8021', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    for (const name of suites) {
        await execute(process.execPath, [scripts[name], ...(name === 'staff' ? ['--staff-only'] : [])], env);
        passed.push(name);
    }
    fs.writeFileSync(path.join(out, 'browser-suites.json'), JSON.stringify({passed, disposableData: true, publicProductionWrites: false}, null, 2));
    console.log('PASS second review browser suites: ' + passed.join(', '));
})().catch(error => {console.error(error); process.exitCode = 1;}).finally(() => {
    if (server) server.kill();
});
