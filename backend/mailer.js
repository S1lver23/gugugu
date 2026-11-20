const nodemailer = require('nodemailer');
let transport;
function configured() {
    return Boolean(process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASSWORD && process.env.MAIL_FROM_ADDRESS);
}
function getTransport() {
    if (!configured()) throw new Error('尚未完成寄信設定');
    if (!transport) transport = nodemailer.createTransport({
        host: process.env.SMTP_HOST, port: Number(process.env.SMTP_PORT || 587),
        secure: process.env.SMTP_SECURE === 'true', requireTLS: process.env.SMTP_REQUIRE_TLS !== 'false',
        auth: { user: process.env.SMTP_USER, pass: process.env.SMTP_PASSWORD.replace(/\s/g, '') },
        connectionTimeout: 10000, greetingTimeout: 10000, socketTimeout: 15000,
        disableFileAccess: true, disableUrlAccess: true, logger: false, debug: false
    });
    return transport;
}
async function sendMail({ to, subject, text, messageId }) {
    if (process.env.EMAIL_ENABLED !== 'true') throw new Error('寄信服務尚未啟用');
    const result = await getTransport().sendMail({
        from: { name: process.env.MAIL_FROM_NAME || '古估股', address: process.env.MAIL_FROM_ADDRESS },
        to, subject, text, ...(messageId ? { messageId } : {})
    });
    if (!result.accepted?.length) throw new Error('郵件服務未接受收件人');
    return result;
}
async function verifySmtp() { return getTransport().verify(); }
module.exports = { configured, sendMail, verifySmtp };
