// Serves only disposable browser fixtures created by prepare_browser_qa.php.
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn} = require('node:child_process');
const root = path.resolve(__dirname, '..');
const cfg = JSON.parse(fs.readFileSync(path.join(root,'storage/app/private/browser-qa.json'),'utf8'));
const dbDir=fs.statSync(path.dirname(cfg.database)),tempDir=fs.statSync(os.tmpdir());
if(dbDir.dev!==tempDir.dev || dbDir.ino!==tempDir.ino || !path.basename(cfg.database).startsWith('alp')) throw new Error('Expected isolated QA database in TEMP.');
cfg.url='http://127.0.0.1:8021';
fs.writeFileSync(path.join(root,'storage/app/private/design-browser-qa.json'),JSON.stringify(cfg));
const env={...process.env,DB_CONNECTION:'sqlite',DB_DATABASE:cfg.database,DB_URL:'',SESSION_DRIVER:'file',SESSION_COOKIE:'alpha_design_qa',CACHE_STORE:'array',MAIL_MAILER:'array',APP_URL:cfg.url,APP_DEBUG:'false'};
const log=fs.openSync(path.join(root,'storage/logs/design-qa-server.log'),'a');
const server=spawn('C:/xampp/php/php.exe',['-S','127.0.0.1:8021','-t','public','tools/qa_router.php'],{cwd:root,env,windowsHide:true,stdio:['ignore',log,log],detached:true});
server.unref();fs.closeSync(log);
console.log('QA server started on '+cfg.url+' (disposable database).');
