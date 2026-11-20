const { verifySmtp } = require('./mailer');
verifySmtp().then(() => console.log('SMTP 連線與驗證成功；未寄出任何郵件。')).catch(error => {
    console.error('SMTP 驗證失敗：', error.code || '連線錯誤', error.responseCode ? `SMTP 回應碼 ${error.responseCode}` : '');
    process.exitCode = 1;
});
