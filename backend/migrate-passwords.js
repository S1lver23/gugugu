/*
 * 一次性遷移 users.json 的舊密碼格式。
 * 執行：npm run migrate-passwords
 */
const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const dbFile = path.join(__dirname, '..', 'data', 'users.json');
const bcryptPattern = /^\$2[aby]\$/;
const users = JSON.parse(fs.readFileSync(dbFile, 'utf8').replace(/^\uFEFF/, ''));

if (!Array.isArray(users)) {
    throw new Error('users.json 格式錯誤：預期為陣列');
}

let migrated = 0;
let assignedIds = 0;
const usedIds = new Set();
for (const user of users) {
    if (!user || typeof user.password !== 'string' || user.password.length === 0) {
        throw new Error('users.json 包含無效的密碼欄位，已停止遷移');
    }
    if (!bcryptPattern.test(user.password)) {
        // 此專案現有 users.json 為明碼格式；保留原字串作為登入密碼再雜湊，避免變更會員密碼。
        user.password = bcrypt.hashSync(user.password, 12);
        migrated += 1;
    }
    if (typeof user.id !== 'string' || user.id.length === 0 || usedIds.has(user.id)) {
        user.id = crypto.randomUUID();
        assignedIds += 1;
    }
    usedIds.add(user.id);
}

const temporaryFile = `${dbFile}.${process.pid}.tmp`;
fs.writeFileSync(temporaryFile, JSON.stringify(users, null, 2), { encoding: 'utf8', mode: 0o600 });
fs.renameSync(temporaryFile, dbFile);
console.log(`已遷移 ${migrated} 個帳號密碼，補齊 ${assignedIds} 個會員 ID。`);
