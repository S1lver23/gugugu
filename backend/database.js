const mysql = require('mysql2/promise');

const isProduction = process.env.NODE_ENV === 'production';
const dbUser = process.env.DB_USER || 'root';
const dbPassword = process.env.DB_PASSWORD || '';

if (isProduction && (!dbPassword || dbUser === 'root')) {
    throw new Error('正式環境必須使用具密碼的非 root MySQL 專用帳號');
}

const pool = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    user: dbUser,
    password: dbPassword,
    database: process.env.DB_NAME || 'gugugu_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    charset: 'utf8mb4',
    connectTimeout: 5000
});

async function verifyDatabaseConnection() {
    await pool.query('SELECT 1');
}

module.exports = { pool, verifyDatabaseConnection };
