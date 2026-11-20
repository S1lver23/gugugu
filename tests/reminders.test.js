const test=require('node:test');
const assert=require('node:assert/strict');
const {normalizeRule,readLiveQuote,triggeredTypes,hashToken}=require('../backend/reminders');
// 報價欄位取自使用者提供的六福 MIS 回應；不產生模擬股價。
const liufu={c:'2705',ex:'tse',z:'15.1000',y:'15.1000',b:'15.1000_15.0500_',d:'20261014',t:'13:30:00',n:'六福'};
const observedNow=Date.parse('2026-10-14T13:31:00+08:00');
test('近期真實來源報價保留成交價及來源時間',()=>{
 const quote=readLiveQuote(liufu,observedNow);
 assert.equal(quote.price,15.1);assert.equal(quote.time,'2026-10-14 13:30:00');
});
test('過期、跨日、缺少成交價或報價缺失皆不觸發價格提醒',()=>{
 assert.equal(readLiveQuote(liufu,observedNow+6*60000),null);
 assert.equal(readLiveQuote(liufu,observedNow+86400000),null);
 assert.equal(readLiveQuote({...liufu,z:'-'},observedNow),null);
 assert.deepEqual(triggeredTypes({stopPrice:16,takePrice:17},null),[]);
 assert.deepEqual(triggeredTypes({stopPrice:16,takePrice:17},{price:0}),[]);
});
test('提醒使用者設定的門檻與期限，成交價只取來源資料',()=>{
 const rule=normalizeRule({uid:'test',symbol:'2705.TW',market:'tse',stopPrice:16,takePrice:20,emailEnabled:false});
 assert.deepEqual(triggeredTypes(rule,readLiveQuote(liufu,observedNow),observedNow),['stop']);
 assert.deepEqual(triggeredTypes({...rule,deadline:'2026-10-14T13:00:00+08:00'},null,observedNow),['time']);
 assert.throws(()=>normalizeRule({...rule,market:'unknown'}));
 assert.throws(()=>normalizeRule({...rule,stopPrice:20,takePrice:16}));
 assert.equal(hashToken('verification-token').length,64);
});
