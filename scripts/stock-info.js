const quotePage = document.querySelector('.quote-page');
const refreshQuoteButton = document.getElementById('refreshQuote');
const quoteStatus = document.getElementById('quoteStatus');
const stockQuoteFields = { previousClose: 'previousClose', open: 'openPrice', high: 'highPrice', low: 'lowPrice' };

function displayStockQuote(data) {
    const number = value => Number.isFinite(Number(value)) ? Number(value) : 0;
    document.getElementById('quotePrice').textContent = number(data.price).toFixed(2);
    const change = number(data.change);
    const changeElement = document.getElementById('quoteChange');
    changeElement.textContent = `漲跌 ${change > 0 ? '+' : ''}${change.toFixed(2)}（${number(data.changePct).toFixed(2)}%）`;
    changeElement.className = `quote-change ${change > 0 ? 'quote-up' : change < 0 ? 'quote-down' : ''}`;
    for (const [field, id] of Object.entries(stockQuoteFields)) document.getElementById(id).textContent = number(data[field]).toFixed(2);
    document.getElementById('volume').textContent = number(data.volume).toLocaleString('zh-TW');
    document.getElementById('quoteTime').textContent = data.quoteTime || '0';
    document.getElementById('quoteDate').textContent = data.quoteTime ? String(data.quoteTime).slice(0, 10) : '0';
    quoteStatus.textContent = data.message || '來源未提供資料，數值以 0 顯示。';
}

async function loadStockQuote() {
    refreshQuoteButton.disabled = true;
    quoteStatus.textContent = '正在讀取來源報價…';
    displayStockQuote({ message: '正在讀取來源報價…' });
    try {
        const params = new URLSearchParams({ category: quotePage.dataset.category, symbol: quotePage.dataset.symbol });
        const response = await fetch(`stock-quote.php?${params}`, { cache: 'no-store', signal: AbortSignal.timeout(16000) });
        if (!response.ok) throw new Error(`報價服務回應 ${response.status}`);
        const data = await response.json();
        displayStockQuote(data);
        if (!data.ok) console.error('股票報價失敗：', data.message);
    } catch (error) {
        console.error('股票報價讀取失敗：', error);
        displayStockQuote({ message: '報價服務暫時無法使用，數值以 0 顯示；請稍後重新整理報價。' });
    } finally { refreshQuoteButton.disabled = false; }
}
refreshQuoteButton.addEventListener('click', loadStockQuote);
if (!refreshQuoteButton.disabled) loadStockQuote();
