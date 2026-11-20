const express = require('express');
const fetch = require('node-fetch');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { pool, verifyDatabaseConnection } = require('./database');
const { createReminders } = require('./reminders');
const news = require('./news').createNews();

const app = express();
const port = Number(process.env.PORT || 4000);
const isProduction = process.env.NODE_ENV === 'production';

const SNAPSHOT_FILE = path.join(__dirname, '..', 'data', 'latest-market-cache.json');
const KLINE_FILE = path.join(__dirname, '..', 'data', 'kline-store.json');
const AUDIT_LOG_FILE = path.join(__dirname, '..', 'logs', 'security.log');

const SNAPSHOT_REFRESH_MS = 15000;
const STOCK_API_URL = 'https://mis.twse.com.tw/stock/api/getStockInfo.jsp';

const ETF_CATEGORIES = {
    market: {
        name: '市值型',
        mega: [
            { id: '0050', name: '元大台灣50', type: 'tse' },
            { id: '0051', name: '元大中型100', type: 'tse' },
            { id: '0052', name: '富邦科技', type: 'tse' },
            { id: '0057', name: '富邦摩台', type: 'tse' },
            { id: '006203', name: '元大MSCI台灣', type: 'tse' },
            { id: '006204', name: '永豐台灣加權', type: 'tse' },
            { id: '006208', name: '富邦台50', type: 'tse' }
        ]
    },

    dividend: {
        name: '高股息型',
        mega: [
            { id: '0056', name: '元大高股息', type: 'tse' },
            { id: '00878', name: '國泰永續高股息', type: 'tse' },
            { id: '00919', name: '群益台灣精選高息', type: 'tse' }
        ],
        large: [
            { id: '00713', name: '元大台灣高息低波', type: 'tse' },
            { id: '00730', name: '富邦台灣優質高息', type: 'tse' },
            { id: '00918', name: '大華優利高填息30', type: 'tse' },
            { id: '00929', name: '復華台灣科技優息', type: 'tse' },
            { id: '00940', name: '元大台灣價值高息', type: 'tse' }
        ]
    },

    industry: {
        name: '產業主題型',
        semiconductor: [
            { id: '00881', name: '國泰台灣科技龍頭', type: 'tse' },
            { id: '00891', name: '中信關鍵半導體', type: 'tse' },
            { id: '00892', name: '富邦台灣半導體', type: 'tse' },
            { id: '00894', name: '中信小資高價30', type: 'tse' }
        ],
        finance: [
            { id: '0055', name: '元大MSCI金融', type: 'tse' }
        ]
    },

    overseas: {
        name: '海外股票型',
        us: [
            { id: '00662', name: '富邦NASDAQ', type: 'tse' },
            { id: '00757', name: '統一FANG+', type: 'tse' }
        ]
    },

    bond: {
        name: '債券型',
        government: [
            { id: '00679B', name: '元大美債20年', type: 'otc' },
            { id: '00687B', name: '國泰20年美債', type: 'otc' }
        ],
        credit: [
            { id: '00720B', name: '元大投資級公司債', type: 'otc' },
            { id: '00725B', name: '國泰投資級公司債', type: 'otc' },
            { id: '00751B', name: '元大AAA至A公司債', type: 'otc' },
            { id: '00937B', name: '群益ESG投等債20+', type: 'otc' }
        ]
    },

    active: {
        name: '主動式ETF',
        mega: [
            { id: '00403A', name: '主動聯博台灣科技', type: 'tse' },
            { id: '00981A', name: '統一台灣高息動能', type: 'tse' }
        ],
        large: [
            { id: '00993A', name: '聯博台灣價值', type: 'tse' },
            { id: '00994A', name: '主動第一金台股優', type: 'tse' },
            { id: '00995A', name: '主動中信成長高息', type: 'tse' }
        ]
    },

    leveraged: {
        name: '槓桿反向型',
        leverage: [
            { id: '00631L', name: '元大台灣50正2', type: 'tse' },
            { id: '00663L', name: '國泰臺灣加權正2', type: 'tse' },
            { id: '00675L', name: '富邦臺灣加權正2', type: 'tse' },
            { id: '00680L', name: '元大美債20正2', type: 'tse' }
        ],
        inverse: [
            { id: '00632R', name: '元大台灣50反1', type: 'tse' },
            { id: '00676R', name: '富邦臺灣加權反1', type: 'tse' },
            { id: '00681R', name: '元大美債20反1', type: 'tse' }
        ]
    }
};

const SECTOR_STOCKS = {
    food: [
        { id: '2727', name: '王品集團', type: 'tse' },
        { id: '2723', name: '美食-KY', type: 'tse' },
        { id: '2753', name: '八方雲集', type: 'tse' },
        { id: '2729', name: '瓦城', type: 'otc' },
        { id: '7705', name: '三商餐飲', type: 'tse' },
        { id: '7708', name: '全家餐飲', type: 'otc' },
        { id: '1268', name: '漢來美食', type: 'otc' }
    ],
    travel: [
        { id: '2707', name: '晶華', type: 'tse' },
        { id: '2705', name: '六福', type: 'tse' },
        { id: '5706', name: '鳳凰', type: 'tse' },
        { id: '2731', name: '雄獅', type: 'tse' },
        { id: '5701', name: '劍湖山', type: 'otc' },
        { id: '2712', name: '遠雄來', type: 'tse' },
        { id: '2722', name: '夏都', type: 'tse' },
        { id: '2748', name: '雲品', type: 'tse' }
    ],
    aviation: [
        { id: '2618', name: '長榮航', type: 'tse' },
        { id: '2610', name: '華航', type: 'tse' },
        { id: '2646', name: '星宇航空', type: 'tse' },
        { id: '6757', name: '台灣虎航', type: 'tse' }
    ]
};

let latestSnapshot = {
    updatedAt: null,
    etf: [],
    sectors: {
        food: [],
        travel: [],
        aviation: []
    }
};

const extraTrackedStocks = new Map();
let klineStore = {};

// ==========================================
// 輔支與資料處理函式區
// ==========================================

function getAllETFStocks() {
    // 首頁卡片亦須追蹤，不能只追蹤 ETF 分類頁的清單。
    const result = [
        { id: '00905', name: 'FT臺灣Smart', type: 'tse' },
        { id: '00921', name: '兆豐龍頭等權重', type: 'tse' },
        { id: '009803', name: '玉山市值動能50', type: 'tse' },
        { id: '00980A', name: '主動野村臺灣優選', type: 'tse' }
    ];
    Object.values(ETF_CATEGORIES).forEach(category => {
        Object.entries(category).forEach(([key, value]) => {
            if (key === 'name') return;
            if (Array.isArray(value)) {
                result.push(...value);
            }
        });
    });
    return Array.from(new Map(result.map(item => [item.id, item])).values());
}

function safeReadJson(filePath, fallback) {
    try {
        if (!fs.existsSync(filePath)) return fallback;
        const raw = fs.readFileSync(filePath, 'utf8').replace(/^\uFEFF/, '');
        return JSON.parse(raw);
    } catch (error) {
        console.error(`[WARN] Failed to read JSON ${filePath}:`, error.message);
        return fallback;
    }
}

function safeWriteJson(filePath, data) {
    try {
        const directory = path.dirname(filePath);
        fs.mkdirSync(directory, { recursive: true });
        const temporaryFile = `${filePath}.${process.pid}.tmp`;
        fs.writeFileSync(temporaryFile, JSON.stringify(data, null, 2), { encoding: 'utf8', mode: 0o600 });
        fs.renameSync(temporaryFile, filePath);
        return true;
    } catch (error) {
        console.error(`[WARN] Failed to write JSON ${filePath}:`, error.message);
        return false;
    }
}

function writeAuditLog(event, details = {}) {
    try {
        fs.mkdirSync(path.dirname(AUDIT_LOG_FILE), { recursive: true });
        const entry = JSON.stringify({
            time: new Date().toISOString(),
            event,
            ...details
        });
        fs.appendFileSync(AUDIT_LOG_FILE, `${entry}\n`, { encoding: 'utf8', mode: 0o600 });
    } catch (error) {
        // 稽核失敗不可影響登入或收藏主流程，但要留下伺服器端訊息。
        console.error('[WARN] Failed to write security audit log:', error.message);
    }
}

function getClientIp(req) {
    return req.ip || req.socket?.remoteAddress || 'unknown';
}

function createRateLimiter({ windowMs, max, name }) {
    return require('./security').rateLimiter({windowMs,max});
}

function toNumber(value, fallback = 0) {
    const num = Number(value);
    return Number.isFinite(num) ? num : fallback;
}

function toFixedString(value) {
    return toNumber(value, 0).toFixed(2);
}

function normalizeQuote(item) {
    return {
        ...item,
        price: toFixedString(item.price),
        change: toFixedString(item.change),
        changePct: toFixedString(item.changePct),
        volume: String(toNumber(item.volume, 0))
    };
}

function firstLevelPrice(levelText) {
    if (!levelText || levelText === '-') return null;
    const first = String(levelText).split('_')[0];
    const value = Number(first);
    return Number.isFinite(value) ? value : null;
}

function parseRealtimeQuote(rawItem = {}) {
    const prevClose = toNumber(rawItem.y, 0);
    let price = toNumber(rawItem.z, NaN);
    if (!Number.isFinite(price) || price <= 0) {
        const bid1 = firstLevelPrice(rawItem.b);
        const ask1 = firstLevelPrice(rawItem.a);
        if (bid1 !== null) price = bid1;
        else if (ask1 !== null) price = ask1;
        else price = prevClose;
    }
    const change = price - prevClose;
    const changePct = prevClose > 0 ? (change / prevClose) * 100 : 0;

    return normalizeQuote({
        name: rawItem.n || '',
        price,
        change,
        changePct,
        volume: toNumber(rawItem.v, 0)
    });
}

function buildExCh(stockId, type) {
    return `${type}_${stockId}.tw`;
}

function findTypeByStockId(stockId) {
    const fromEtf = getAllETFStocks().find(item => item.id === stockId);
    if (fromEtf) return fromEtf.type;

    const fromSector = Object.values(SECTOR_STOCKS).flat().find(item => item.id === stockId);
    if (fromSector) return fromSector.type;

    return 'tse';
}

function parseQuoteSymbol(symbolText = '') {
    const text = String(symbolText).trim().toUpperCase();
    if (!text) return { id: '', type: 'tse' };

    const explicit = text.match(/^(TSE|OTC)_([0-9A-Z]+)\.TW$/i);
    if (explicit) {
        return { id: explicit[2], type: explicit[1].toLowerCase() };
    }

    const pureId = text.replace(/\.TW$/i, '');
    return { id: pureId, type: findTypeByStockId(pureId) };
}

function convertDate(twDate) {
    const parts = String(twDate).split('/');
    const year = Number(parts[0]) + 1911;
    const month = parts[1].padStart(2, '0');
    const day = parts[2].padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// 台北時區今日日期字串格式化 (YYYY-MM-DD)
function getTaipeiDateKey(date = new Date()) {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Taipei',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(date);
}

function trackStock(stockId, type, name = '') {
    if (!stockId) return;
    const key = String(stockId).trim().toUpperCase();
    if (!extraTrackedStocks.has(key)) {
        if(extraTrackedStocks.size>=200)return;
        extraTrackedStocks.set(key, {
            id: key,
            type: type || findTypeByStockId(key),
            name: name || key
        });
    } else {
        const old = extraTrackedStocks.get(key);
        extraTrackedStocks.set(key, {
            ...old,
            type: type || old.type || findTypeByStockId(key),
            name: name || old.name || key
        });
    }
}

function getAllTrackedStocks() {
    const merged = [
        ...getAllETFStocks().map(item => ({ id: item.id, name: item.name, type: item.type })),
        ...Object.values(SECTOR_STOCKS).flat(),
        ...Array.from(extraTrackedStocks.values())
    ];
    const unique = new Map();
    merged.forEach(item => {
        const key = `${item.type}:${item.id}`;
        if (!unique.has(key)) unique.set(key, item);
    });
    return Array.from(unique.values());
}

async function fetchStockApiBatch(stocks) {
    if (!stocks.length) return new Map();
    const exCh = stocks.map(item => buildExCh(item.id, item.type)).join('|');
    const params = new URLSearchParams({
        ex_ch: exCh,
        json: '1',
        delay: '0',
        _: Date.now().toString()
    });

    const response = await fetch(`${STOCK_API_URL}?${params.toString()}`, {
        headers: {
            'User-Agent': 'Mozilla/5.0',
            Referer: 'https://mis.twse.com.tw/stock/fibest.jsp'
        },
        timeout:10000, size:2*1024*1024
    });
    if (!response.ok) throw new Error(`StockAPI HTTP ${response.status}`);

    const payload = await response.json();
    const list = Array.isArray(payload?.msgArray) ? payload.msgArray : [];
    const quoteMap = new Map();
    list.forEach(item => {
        if (!item?.c) return;
        quoteMap.set(item.c, parseRealtimeQuote(item));
    });
    return quoteMap;
}

function getKlineSeries(symbol) {
    const key = String(symbol).trim().toUpperCase();
    if (!Array.isArray(klineStore[key])) {
        klineStore[key] = [];
    }
    return klineStore[key];
}

function recordDailyKline(symbol, price, time = new Date()) {
    const key = String(symbol).trim().toUpperCase();
    const p = Number(price);
    if (!Number.isFinite(p) || p <= 0) return;

    const dayKey = getTaipeiDateKey(time);
    const series = getKlineSeries(key);
    const last = series[series.length - 1];

    if (!last || last.time !== dayKey) {
        // 新交易日第一次取得的 MIS 即時成交價，先作為當日 O/H/L/C。
        // 後續每次真實報價只更新最高、最低與收盤，不生成任何隨機價格。
        series.push({
            time: dayKey,
            open: +p.toFixed(2),
            high: +p.toFixed(2),
            low: +p.toFixed(2),
            close: +p.toFixed(2)
        });
    } else {
        last.high = Math.max(last.high, p);
        last.low = Math.min(last.low, p);
        last.close = p;
    }

    if (series.length > 300) {
        series.splice(0, series.length - 300);
    }
    klineStore[key] = series;
    klineDirty = true;
}

function seedKlineFromQuote(symbol, quotePrice) {
    recordDailyKline(symbol, quotePrice);
}

// ==========================================
// 中間件 (Middleware) 設定
// ==========================================
// 只在配置已知反向代理時信任轉送標頭，預設不採用 X-Forwarded-For。
app.set('trust proxy', process.env.TRUST_PROXY === 'loopback' ? 'loopback' : false);
app.disable('x-powered-by');
app.use(require('./security').headers);
app.use('/api', createRateLimiter({name:'api',windowMs:60000,max:180}));
app.use('/api', (req,res,next)=>{
 const origin=req.get('origin');
 if(!['GET','HEAD','OPTIONS'].includes(req.method) && origin && !allowedOrigins.has(origin))return res.status(403).json({success:false,message:'不允許的網站來源'});
 next();
});
app.use(express.json({ limit: '16kb' }));
app.use(express.urlencoded({ extended: true, limit: '16kb' }));

const configuredOrigins = String(process.env.ALLOWED_ORIGINS || '')
    .split(',')
    .map(origin => origin.trim())
    .filter(Boolean);
const allowedOrigins = new Set(configuredOrigins.length ? configuredOrigins : [
    'http://localhost',
    'http://127.0.0.1'
]);

if (isProduction && !process.env.SESSION_SECRET) {
    throw new Error('正式環境必須設定 SESSION_SECRET');
}

const sessionConfig = {
    secret: process.env.SESSION_SECRET || 'development-only-change-this-secret',
    resave: false,
    saveUninitialized: false,
    cookie: {
        httpOnly: true,
        secure: isProduction,
        sameSite: 'lax',
        maxAge: 7 * 24 * 60 * 60 * 1000
    }
};

app.use(session(sessionConfig));

const cors = require('cors');
app.use(cors({
    origin: function (origin, callback) {
        // 沒有 Origin 的請求（例如健康檢查）不會收到 CORS 標頭；瀏覽器僅允許白名單網域讀取回應。
        callback(null, !origin || allowedOrigins.has(origin) ? origin : false);
    },
    credentials: true
}));

function requireLogin(req, res, next) {
    if (!req.session.user?.id) {
        return res.status(401).json({ success: false, message: '尚未登入' });
    }
    return next();
}

function requireCsrf(req, res, next) {
    const origin = req.get('origin');
    const token = req.get('x-csrf-token');
    if ((origin && !allowedOrigins.has(origin)) || !token || token !== req.session.csrfToken) {
        writeAuditLog('csrf_rejected', { route: req.path, ip: getClientIp(req) });
        return res.status(403).json({ success: false, message: '無效的安全驗證，請重新登入後再試' });
    }
    return next();
}

const loginRateLimiter = createRateLimiter({ name: 'login', windowMs: 15 * 60 * 1000, max: 10 });
const registerRateLimiter = createRateLimiter({ name: 'register', windowMs: 60 * 60 * 1000, max: 5 });
const writeRateLimiter = createRateLimiter({ name: 'write', windowMs: 60 * 1000, max: 30 });
const emailRateLimiter = createRateLimiter({ name: 'email', windowMs: 60 * 60 * 1000, max: 5 });
app.use(['/api/quote','/api/kline'],createRateLimiter({name:'market',windowMs:60000,max:30}));
const reminders = createReminders({ pool, requireLogin, requireCsrf, writeRateLimiter, emailRateLimiter });
app.use('/api', reminders.router);

// ==========================================
// 資料載入
// ==========================================
latestSnapshot = safeReadJson(SNAPSHOT_FILE, latestSnapshot);
klineStore = safeReadJson(KLINE_FILE, {});
if (!klineStore || typeof klineStore !== 'object' || Array.isArray(klineStore)) {
    klineStore = {};
}

// ==========================================
// 背景資料更新與路由區
// ==========================================
let isUpdatingSnapshot = false;
let klineDirty = false;

async function updateSnapshot() {
    if (isUpdatingSnapshot) return;
    isUpdatingSnapshot = true;

    try {
        console.log('[資訊] 正在從 TWSE StockAPI 更新市場快照...');
        const quoteMap = await fetchStockApiBatch(getAllTrackedStocks());

        const etfQuotes = getAllETFStocks().map(item => {
            if (!quoteMap.has(item.id)) console.error(`[錯誤] ETF ${item.id}：TWSE StockAPI 未回傳報價，使用預設值 0`);
            const quote = quoteMap.get(item.id) || normalizeQuote({
                price: 0, change: 0, changePct: 0, volume: 0
            });

            seedKlineFromQuote(item.id, quote.price);

            return {
                symbol: `${item.id}.TW`,
                id: item.id,
                name: item.name,
                price: quote.price,
                change: quote.change,
                changePct: quote.changePct,
                volume: quote.volume,
                time: new Date().toLocaleString('zh-TW', { hour12: false })
            };
        });

        const sectorQuotes = { food: [], travel: [], aviation: [] };

        Object.entries(SECTOR_STOCKS).forEach(([sector, stocks]) => {
            sectorQuotes[sector] = stocks.map(stock => {
                const quote = quoteMap.get(stock.id) || normalizeQuote({
                    price: 0, change: 0, changePct: 0, volume: 0
                });

                seedKlineFromQuote(stock.id, quote.price);

                return {
                    id: stock.id,
                    name: stock.name,
                    price: quote.price,
                    change: quote.change,
                    changePct: quote.changePct,
                    volume: quote.volume
                };
            });
        });

        latestSnapshot = {
            updatedAt: new Date().toLocaleString('zh-TW', { hour12: false }),
            etf: etfQuotes,
            sectors: sectorQuotes
        };

        safeWriteJson(SNAPSHOT_FILE, latestSnapshot);
        console.log('[資訊] 快照更新完成');
    } catch (error) {
        console.error('[錯誤] 快照更新失敗:', error.message);
    } finally {
        if (klineDirty) {
            safeWriteJson(KLINE_FILE, klineStore);
            klineDirty = false;
        }
        isUpdatingSnapshot = false;
    }
}

setInterval(updateSnapshot, SNAPSHOT_REFRESH_MS);

app.get('/api/news', (req, res) => {
    res.set('Cache-Control', 'public, max-age=60');
    res.json(news.snapshot());
});

app.get('/api/snapshot', (req, res) => {
    res.json(latestSnapshot);
});

app.get('/api/quote/:symbol', async (req, res) => {
    try {
        const parsed = parseQuoteSymbol(req.params.symbol);
        if (!parsed.id) return res.status(400).json({ error: '股票代號格式錯誤' });

        trackStock(parsed.id, parsed.type);

        const quoteMap = await fetchStockApiBatch([{ id: parsed.id, type: parsed.type }]);
        const quote = quoteMap.get(parsed.id) || normalizeQuote({
            price: 0, change: 0, changePct: 0, volume: 0
        });

        if (Number(quote.price) > 0) {
            seedKlineFromQuote(parsed.id, quote.price);
            safeWriteJson(KLINE_FILE, klineStore);
            klineDirty = false;
        }

        res.json(quote);
    } catch (error) {
        console.warn('[報價] 請求失敗：',error.message);
        res.status(503).json({ error: '報價服務暫時無法使用',price:0,change:0,changePct:0,volume:0 });
    }
});

app.get('/api/etf/snapshot', (req, res) => {
    res.json({
        updatedAt: latestSnapshot.updatedAt,
        categories: ETF_CATEGORIES,
        quotes: latestSnapshot.etf
    });
});

app.get('/api/etf/categories', (req, res) => {
    res.json(ETF_CATEGORIES);
});

app.get('/api/etf/list', (req, res) => {
    res.json(getAllETFStocks());
});

app.get('/api/kline', async (req, res) => {
    const { symbol } = req.query;
    if (!symbol) return res.status(400).json({ error: '請提供股票代號' });

    const parsed = parseQuoteSymbol(symbol);
    const key = parsed.id.toUpperCase();

    trackStock(parsed.id, parsed.type);
    let series = getKlineSeries(key);

    try {
        const quoteMap = await fetchStockApiBatch([{ id: parsed.id, type: parsed.type }]);
        const quote = quoteMap.get(parsed.id);

        if (quote && Number(quote.price) > 0) {
            const today = getTaipeiDateKey();
            const last = series[series.length - 1];

            if (!last || last.time !== today) {
                recordDailyKline(parsed.id, quote.price);
            } else {
                last.high = Math.max(last.high, Number(quote.price));
                last.low = Math.min(last.low, Number(quote.price));
                last.close = Number(quote.price);
            }

            safeWriteJson(KLINE_FILE, klineStore);
            series = getKlineSeries(key);
        }

        return res.json(series);
    } catch (error) {
        return res.status(500).json({ error: 'K線讀取失敗' });
    }
});

// 登入驗證與收藏功能
const BCRYPT_ROUNDS = 12;

function hashPassword(password) {
    return bcrypt.hash(password, BCRYPT_ROUNDS);
}

async function verifyPassword(inputPassword, storedPassword) {
    return typeof storedPassword === 'string'
        && /^\$2[aby]\$/.test(storedPassword)
        && await bcrypt.compare(inputPassword, storedPassword);
}

app.post('/api/login', loginRateLimiter, async (req, res) => {
    const username = String(req.body?.username || '').trim();
    const password = String(req.body?.password || '');
    if (username.length > 30 || Buffer.byteLength(password, 'utf8') > 72) return res.status(400).json({success:false,message:'帳號或密碼格式錯誤'});
    if (!username || !password) return res.status(400).json({ success: false, message: '請輸入帳號密碼' });

    let user;
    try {
        const [rows] = await pool.execute(
            'SELECT id, username, password_hash FROM users WHERE username COLLATE utf8mb4_unicode_ci = ? LIMIT 1',
            [username]
        );
        user = rows[0];
    } catch (error) {
        console.error('[ERROR] Database login query failed:', error.message);
        return res.status(503).json({ success: false, message: '帳號服務暫時無法使用' });
    }

    if (!user || !(await verifyPassword(password, user.password_hash))) {
        writeAuditLog('login_failed', { username, ip: getClientIp(req) });
        return res.status(401).json({ success: false, message: '帳號或密碼錯誤' });
    }

    return req.session.regenerate(error => {
        if (error) {
            console.error('[WARN] Failed to regenerate session:', error.message);
            return res.status(500).json({ success: false, message: '登入服務暫時無法使用' });
        }
        req.session.user = { username: user.username, id: String(user.id) };
        req.session.csrfToken = crypto.randomBytes(32).toString('hex');
        return req.session.save(saveError => {
            if (saveError) return res.status(500).json({ success: false, message: '登入服務暫時無法使用' });
            writeAuditLog('login_success', { userId: user.id, ip: getClientIp(req) });
            return res.json({ success: true, message: '登入成功' });
        });
    });
});

app.post('/api/register', registerRateLimiter, async (req, res) => {
    const username = String(req.body?.username || '').trim();
    const password = String(req.body?.password || '');
    const email=String(req.body?.email || '').trim().toLowerCase();
    if(!require('./password-recovery').validEmail(email))return res.status(400).json({success:false,message:'請填寫有效信箱'});

    const policy = require('./account-policy');
    const policyError = policy.usernameError(username) || policy.passwordError(password, username);
    if (policyError) return res.status(400).json({ success: false, message: policyError });

    const conn=await pool.getConnection();
    let mailSent=false;
    try {
        await conn.beginTransaction();
        const [insert]=await conn.execute('INSERT INTO users (username,password_hash) VALUES (?,?)',[username,await hashPassword(password)]);
        await conn.execute('INSERT INTO notification_profiles (user_id,email) VALUES (?,?)',[insert.insertId,email]);
        const mailer=require('./mailer');
        if(mailer.configured() && process.env.EMAIL_ENABLED==='true') {
            const token=crypto.randomBytes(32).toString('hex');
            const link=require('./password-recovery').recoveryLink('verify-email.php',token);
            await conn.execute('INSERT INTO email_verifications (token_hash,user_id,email,expires_at) VALUES (?,?,?,?)',[crypto.createHash('sha256').update(token).digest('hex'),insert.insertId,email,Date.now()+30*60000]);
            try {
                await mailer.sendMail({to:email,subject:'古估股｜驗證註冊信箱',text:`請在 30 分鐘內開啟以下連結並按「確認驗證」：\n${link}\n驗證後可接收提醒與申請重設密碼。若不是你提出的申請，請忽略此信。`});
                mailSent=true;
                await conn.execute('UPDATE notification_profiles SET last_sent_at=? WHERE user_id=?',[Date.now(),insert.insertId]);
            } catch(error) {console.warn('[註冊] 驗證信未寄出：',error.code || 'SMTP錯誤');}
        }
        await conn.commit();
    } catch(error) {
        await conn.rollback();
        return res.status(error.code==='ER_DUP_ENTRY'?409:503).json({success:false,message:error.code==='ER_DUP_ENTRY'?'這個帳號已經存在':'註冊服務暫時無法使用'});
    } finally {conn.release();}
    writeAuditLog('register_success', { username, ip: getClientIp(req) });
    return res.status(201).json({ success: true, message: mailSent ? '註冊成功，驗證信已寄出；請驗證信箱後使用提醒與密碼重設功能。登入仍使用帳號與密碼。' : '註冊成功，但驗證信尚未寄出。請登入後到通知信箱設定重新寄送驗證信。' });
});

const recovery = require('./password-recovery').createRecovery({pool,limiter:registerRateLimiter});
app.use('/api',recovery.router);

app.get('/api/csrf', requireLogin, (req, res) => {
    if (!req.session.csrfToken) req.session.csrfToken = crypto.randomBytes(32).toString('hex');
    res.json({ success: true, csrfToken: req.session.csrfToken });
});

app.post('/api/logout', requireLogin, requireCsrf, (req, res) => {
    const userId = req.session.user.id;
    req.session.destroy(() => {
        res.clearCookie('connect.sid');
        writeAuditLog('logout', { userId, ip: getClientIp(req) });
        res.json({ success: true, message: '已登出' });
    });
});

app.get('/api/me', (req, res) => {
    if (req.session.user) {
        res.json({ loggedIn: true, user: req.session.user });
    } else {
        res.json({ loggedIn: false });
    }
});

app.get('/api/status', (req, res) => {
    if (req.session.user) {
        return res.json({ isLoggedIn: true, user: { name: req.session.user.username } });
    }
    res.json({ isLoggedIn: false });
});

app.get('/api/favorites', requireLogin, async (req, res) => {
    try {
        const [rows] = await pool.execute(
            'SELECT stock_id FROM favorites WHERE user_id = ? ORDER BY created_at ASC',
            [req.session.user.id]
        );
        return res.json({ success: true, favorites: rows.map(row => row.stock_id) });
    } catch (error) {
        console.error('[ERROR] Database favorites query failed:', error.message);
        return res.status(503).json({ success: false, message: '收藏服務暫時無法使用' });
    }
});

app.post('/api/favorites', requireLogin, requireCsrf, writeRateLimiter, async (req, res) => {
    const { stockId, action } = req.body || {};
    if (!/^[0-9A-Za-z]{4,8}$/.test(String(stockId || '')) || !['add', 'remove'].includes(action)) {
        return res.status(400).json({ success: false, message: '參數錯誤' });
    }

    let favorites;
    try {
        if (action === 'add') {
            await pool.execute(
                'INSERT IGNORE INTO favorites (user_id, stock_id) VALUES (?, ?)',
                [req.session.user.id, String(stockId).toUpperCase()]
            );
        } else {
            await pool.execute(
                'DELETE FROM favorites WHERE user_id = ? AND stock_id = ?',
                [req.session.user.id, String(stockId).toUpperCase()]
            );
        }
        const [rows] = await pool.execute(
            'SELECT stock_id FROM favorites WHERE user_id = ? ORDER BY created_at ASC',
            [req.session.user.id]
        );
        favorites = rows.map(row => row.stock_id);
    } catch (error) {
        console.error('[ERROR] Database favorites update failed:', error.message);
        return res.status(503).json({ success: false, message: '收藏服務暫時無法使用' });
    }
    writeAuditLog('favorite_updated', {
        userId: req.session.user.id,
        action,
        stockId: String(stockId),
        ip: getClientIp(req)
    });
    res.json({ success: true, favorites });
});

async function startServer() {
    try {
        await verifyDatabaseConnection();
        await reminders.initialize();
        await recovery.initialize();
        app.listen(port, process.env.HOST || '127.0.0.1', () => {
            console.log(`[資訊] 伺服器已啟動：http://localhost:${port}`);
            updateSnapshot();
            reminders.start();
            void news.start();
        });
    } catch (error) {
        console.error('[ERROR] MySQL 連線失敗，為避免帳密回退到 JSON，伺服器不會啟動：', error.message);
        await pool.end();
        process.exit(1);
    }
}

app.use((error,req,res,next)=>{
    if(res.headersSent)return next(error);
    console.warn('[API] 請求失敗：',error.type || error.code || '內部錯誤');
    const status=error.type==='entity.too.large'?413:error instanceof SyntaxError?400:500;
    res.status(status).json({success:false,message:status===413?'請求內容過大':status===400?'請求格式錯誤':'服務暫時無法使用'});
});

startServer();
