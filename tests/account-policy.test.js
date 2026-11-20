const test = require('node:test');
const assert = require('node:assert/strict');
const { usernameError, passwordError } = require('../backend/account-policy');
test('帳號長度、開頭、字元與保留名稱', () => {
    for (const name of ['Silv', 'Silver_123', 'A'.repeat(30)]) assert.equal(usernameError(name), '');
    for (const name of ['Abc', 'A'.repeat(31), '1silver', '_silver', '銀子123', 'Silver name', 'ADMIN', 'Root', 'administrator']) assert.ok(usernameError(name));
});
test('密碼長度、首字、數字、符號、空白與常見密碼', () => {
    for (const password of ['Zebra!93', 'A' + 'x'.repeat(68) + '9!']) assert.equal(passwordError(password, 'silver'), '');
    for (const password of ['Abc!123', 'zebra!93', 'Zebra!!!', 'Zebra123', 'Zebra 93!', 'Zebra\t93!', 'Password123!', 'P@ssw0rd123!', 'A' + '字'.repeat(24) + '9!']) assert.ok(passwordError(password));
    assert.ok(passwordError('Zebra!93', 'zEBRA!93'));
});
