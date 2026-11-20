const test=require('node:test'),assert=require('node:assert/strict');const {rateLimiter}=require('../backend/security');
test('限流不採用偽造轉送標頭，並在時窗過後恢復',()=>{
 let now=100000;const limit=rateLimiter({windowMs:60000,max:2,clock:()=>now});let allowed=0,status;
 const res={set(){},status(s){status=s;return this;},json(){}};
 for(let i=0;i<3;i++)limit({ip:'127.0.0.1',headers:{'x-forwarded-for':`1.1.1.${i}`}},res,()=>allowed++);
 assert.equal(allowed,2);assert.equal(status,429);now+=60001;limit({ip:'127.0.0.1'},res,()=>allowed++);assert.equal(allowed,3);
});
test('限流記憶體數量有上限',()=>{const limit=rateLimiter({windowMs:60000,max:2,maxKeys:1,clock:()=>1000});let allowed=0,status;const res={set(){},status(s){status=s;return this;},json(){}};limit({ip:'a'},res,()=>allowed++);limit({ip:'b'},res,()=>allowed++);assert.equal(allowed,1);assert.equal(status,429);});
