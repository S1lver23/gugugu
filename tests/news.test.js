const test=require('node:test');const assert=require('node:assert/strict');const fs=require('fs/promises');const os=require('os');const path=require('path');
const {createNews,parseNews,CATEGORIES}=require('../backend/news');
test('新聞解析排除過期、未来及非 HTTPS 連結',()=>{
 const now=Date.parse('2026-10-15T00:00:00Z');
 const xml='<rss><channel>'+[['近期','2026-10-14T00:00:00Z','https://example.com/a'],['過期','2026-10-01T00:00:00Z','https://example.com/b'],['未來','2026-10-16T00:00:00Z','https://example.com/c'],['無效','2026-10-14T00:00:00Z','javascript:bad']].map(([t,d,l])=>`<item><title>${t}</title><pubDate>${d}</pubDate><link>${l}</link></item>`).join('')+'</channel></rss>';
 assert.equal(parseNews(xml,CATEGORIES[0],now).length,1);
});
test('快取去重、來源失敗保留資料、重啟復原與並行更新鎖',async()=>{
 const dir=await fs.mkdtemp(path.join(os.tmpdir(),'gugu-news-'));let fail=false,calls=0;
 const stamp=Date.parse('2026-10-15T00:00:00Z');
 const service=createNews({cacheFile:path.join(dir,'news.json'),now:()=>stamp,request:async()=>{calls++;await new Promise(r=>setTimeout(r,5));if(fail)throw new Error('測試來源失敗');return {ok:true,text:async()=>'<rss><channel><item><title>共同新聞</title><link>https://example.com/article</link><pubDate>2026-10-14T00:00:00Z</pubDate></item></channel></rss>'};}});
 try{await Promise.all([service.refresh(),service.refresh()]);assert.equal(calls,3);assert.equal(service.snapshot().items.length,1);fail=true;await service.refresh();assert.equal(service.snapshot().items.length,1);
 const restored=createNews({cacheFile:path.join(dir,'news.json'),now:()=>stamp,request:()=>{throw Error('不應抓取');}});await restored.start();assert.equal(restored.snapshot().items.length,1);restored.stop();
 }finally{service.stop();await fs.rm(dir,{recursive:true,force:true});}
});
