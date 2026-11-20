(()=>{
// 註冊與密碼重設共用規則；既有密碼仍可登入。
const reserved = new Set(['admin', 'administrator', 'root', 'system', 'support', 'guest']);
const common = new Set(['password', 'password1', 'password123', 'password1234', 'p@ssword', 'p@ssw0rd', 'p@ssw0rd1', 'p@ssw0rd123', 'admin123', 'administrator', 'qwerty123', 'qwerty1234', 'welcome1', 'welcome123', 'letmein1', 'changeme1', 'abc12345', '12345678']);
function usernameError(username) {
    if (username.length < 4 || username.length > 30) return '帳號長度請設為 4～30 個字元';
    if (!/^[A-Za-z0-9_]+$/.test(username)) return '帳號僅可使用英文字母、數字與底線，不可有底線以外的符號';
    if (!/^[A-Za-z]/.test(username)) return '帳號開頭請使用英文字母';
    if (reserved.has(username.toLowerCase())) return '此帳號為系統保留名稱，請使用其他帳號';
    return '';
}
function passwordError(password, username = '') {
    if ([...password].length < 8) return '密碼至少需要 8 個字元';
    if (new TextEncoder().encode(password).length > 72) return '密碼過長，請勿超過 72 個 UTF-8 位元組';
    if (/\s/u.test(password)) return '密碼不可有空白';
    if (!/^[A-Z]/.test(password)) return '密碼開頭請設為大寫英文字母';
    if (!/[0-9]/.test(password)) return '密碼請至少包含一個數字';
    if (!/[^\p{L}\p{N}\s]/u.test(password)) return '密碼請至少包含一個符號';
    const lower = password.toLowerCase();
    const plain = lower.replace(/[^a-z0-9@$]/g, '').replace(/@/g, 'a').replace(/\$/g, 's').replace(/0/g, 'o');
    if ((username && lower === username.toLowerCase()) || common.has(lower) || common.has(plain) || /^(password|admin|qwerty|welcome|letmein|changeme|abc)[0-9]*$/.test(plain)) return '請勿使用常見密碼或與帳號相同的密碼';
    return '';
}
if (typeof module !== 'undefined' && module.exports) module.exports = { usernameError, passwordError };
else window.accountPolicy = { usernameError, passwordError };

})();
