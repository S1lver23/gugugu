const fs = require('fs/promises');
const path = require('path');
const fetch = require('node-fetch');
const cheerio = require('cheerio');
const INTERVAL = 30 * 60 * 1000;
const WEEK = 7 * 24 * 60 * 60 * 1000;
const CATEGORIES = [
  { id: 'food', name: '美食', query: '(美食 OR 餐飲 OR 餐廳) when:7d' },
  { id: 'travel', name: '旅遊', query: '(旅遊 OR 觀光 OR 旅行社) when:7d' },
  { id: 'aviation', name: '航空', query: '(航空 OR 航班 OR 航空公司) when:7d' }
];
function parseNews(xml, category, now = Date.now()) {
  const $ = cheerio.load(xml, { xmlMode: true });
  if (!$('rss channel').length) throw new Error('新聞來源格式異常');
  return $('item').toArray().flatMap(element => {
    const node = $(element), title = node.find('title').text().trim();
    const link = node.find('link').text().trim(), date = Date.parse(node.find('pubDate').text());
    let url; try { url = new URL(link); } catch { return []; }
    if (!title || url.protocol !== 'https:' || !Number.isFinite(date) || date > now || now-date > WEEK) return [];
    return [{ title, link: url.href, publishedAt: new Date(date).toISOString(), category: category.id, categoryName: category.name }];
  }).sort((a,b) => Date.parse(b.publishedAt)-Date.parse(a.publishedAt));
}
function selectNews(groups, now = Date.now()) {
  const seen = new Set();
  const lists = CATEGORIES.map(category => (groups[category.id]?.items || []).filter(item => {
    const date=Date.parse(item.publishedAt);
    return Number.isFinite(date) && date <= now && now-date <= WEEK;
  }));
  const result=[]; const counts=[0,0,0];
  while (lists.some((list,i)=>list.length && counts[i]<4)) {
    lists.forEach((list,i) => {
      while (list.length && counts[i]<4) {
        const item=list.shift(), key=item.title.toLowerCase().replace(/\s+/g,'');
        if (seen.has(key) || seen.has(item.link)) continue;
        seen.add(key); seen.add(item.link); result.push(item); counts[i]++; break;
      }
    });
  }
  return result;
}
function createNews({ cacheFile=path.join(__dirname,'../data/news-cache.json'), request=fetch, now=Date.now } = {}) {
  let groups={}, running=false, timer;
  async function refresh() {
    if(running) return;
    running=true;
    try {
      let changed=false;
      // 依序抓三類，整輪最多三次請求，不接受網頁觸發更新。
      for(const category of CATEGORIES) {
        const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),10000);
        try {
          const params=new URLSearchParams({q:category.query,hl:'zh-TW',gl:'TW',ceid:'TW:zh-Hant'});
          const response=await request(`https://news.google.com/rss/search?${params}`,{signal:controller.signal,size:2*1024*1024});
          if(!response.ok) throw new Error(`HTTP ${response.status}`);
          const items=parseNews(await response.text(),category,now());
          groups[category.id]={items,updatedAt:new Date(now()).toISOString()};changed=true;
        } catch(error) {console.warn(`[新聞] ${category.name}更新失敗：${error.name==='AbortError'?'請求逾時':error.message}`);}
        finally {clearTimeout(timeout);}
      }
      if(changed) {
        const temp=`${cacheFile}.tmp`;await fs.writeFile(temp,JSON.stringify({groups}), 'utf8');await fs.rename(temp,cacheFile);
      }
    } catch(error) {console.warn('[新聞] 快取寫入失敗：',error.message);}
    finally {running=false;}
  }
  function snapshot() {
    const items=selectNews(groups,now());
    const categories=CATEGORIES.map(c=>({id:c.id,name:c.name,updatedAt:groups[c.id]?.updatedAt || null,stale:!groups[c.id]?.updatedAt || now()-Date.parse(groups[c.id].updatedAt)>INTERVAL+60000}));
    const dates=categories.map(c=>c.updatedAt).filter(Boolean).sort();
    return {success:true,items,categories,updatedAt:dates.at(-1)||null,stale:categories.some(c=>c.stale),message:items.length?'':'新聞暫時無法讀取'};
  }
  async function start() {
    if(timer) return;
    try {
      const saved=JSON.parse(await fs.readFile(cacheFile,'utf8'));
      for(const c of CATEGORIES) {
        const group=saved.groups?.[c.id];
        if(group && Number.isFinite(Date.parse(group.updatedAt)) && Array.isArray(group.items)) {
          groups[c.id]={updatedAt:group.updatedAt,items:group.items.filter(i=>typeof i.title==='string' && typeof i.link==='string' && i.link.startsWith('https://') && i.category===c.id)};
        }
      }
    } catch(error) {if(error.code!=='ENOENT') console.warn('[新聞] 快取無法讀取，將重新抓取');}
    timer=setInterval(()=>void refresh(),INTERVAL);timer.unref();
    if(snapshot().stale) void refresh();
  }
  return {start,refresh,snapshot,stop(){clearInterval(timer);timer=null;}};
}
module.exports={createNews,parseNews,selectNews,CATEGORIES};
