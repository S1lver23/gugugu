/*
 * 僅供一次性遷移既有 users.json；網站運作時不會讀取 JSON 帳號資料。
 * 執行：npm run migrate-json-users
 */
const fs = require('fs');
const path = require('path');
const { pool, verifyDatabaseConnection } = require('./database');

const sourceFile = path.join(__dirname, '..', 'data', 'users.json');

async function migrate() {
    const users = JSON.parse(fs.readFileSync(sourceFile, 'utf8').replace(/^\uFEFF/, ''));
    if (!Array.isArray(users)) throw new Error('users.json 格式錯誤：預期為陣列');

    await verifyDatabaseConnection();
    let imported = 0;

    for (const user of users) {
        if (!user || typeof user.username !== 'string' || !/^\$2[aby]\$/.test(user.password || '')) {
            throw new Error('發現非 bcrypt 格式帳號，請先執行 migrate-passwords');
        }
        const [result] = await pool.execute(
            'INSERT IGNORE INTO users (username, password_hash) VALUES (?, ?)',
            [user.username, user.password]
        );
        imported += result.affectedRows;

        const [rows] = await pool.execute('SELECT id FROM users WHERE username = ? LIMIT 1', [user.username]);
        const userId = rows[0]?.id;
        for (const stockId of Array.isArray(user.favorites) ? user.favorites : []) {
            await pool.execute(
                'INSERT IGNORE INTO favorites (user_id, stock_id) VALUES (?, ?)',
                [userId, String(stockId).toUpperCase()]
            );
        }
    }

    console.log(`已遷移 ${imported} 個帳號到 MySQL。`);
    await pool.end();
}

migrate().catch(error => {
    console.error('JSON 帳號遷移失敗：', error.message);
    process.exitCode = 1;
});
