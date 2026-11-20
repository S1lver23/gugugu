// gugu.js - 吉祥物目錄功能

document.addEventListener('DOMContentLoaded', () => {
    // 1. 定義教學漫畫資料
    const comicData = [
        {
            title: "第一章—新手入門篇",
            chapters: [
                { name: "第一回《何謂股票?》", link: "gugu_chapter/chapter1-1.html" },
                { name: "第二回《為什麼股票會漲跌？》", link: "gugu_chapter/chapter1-2.html" },
                { name: "第三回《什麼是 ETF？》", link: "gugu_chapter/chapter1-3.html" },
                { name: "第四回《股票的風險》", link: "gugu_chapter/chapter1-4.html" },
                { name: "第五回《購買股票的交易成本》", link: "gugu_chapter/chapter1-5.html" },
                { name: "第六回《證券戶怎麼開》", link: "gugu_chapter/chapter1-6.html" },
                { name: "第七回《購買股票時間》", link: "gugu_chapter/chapter1-7.html" }
            ]
        },
        {
            title: "第二章—選股技巧篇",
            chapters: [
                { name: "第一回《股票的分類》", link: "gugu_chapter/chapter2-1.html" },
                { name: "第二回《如何分析財報》", link: "gugu_chapter/chapter2-2.html" },
                { name: "第三回《怎麼看盤？》", link: "gugu_chapter/chapter2-3.html" },
                { name: "第四回《什麼是 K 線圖？》", link: "gugu_chapter/chapter2-4.html" },
                { name: "第五回《產業風向球》", link: "gugu_chapter/chapter2-5.html" },
                { name: "第六回《選股方法第一篇——基本面與技術面》", link: "gugu_chapter/chapter2-6.html" },
                { name: "第七回《選股方法第二篇——籌碼面與資訊面》", link: "gugu_chapter/chapter2-7.html" },
                { name: "第八回《股票的價格評估》", link: "gugu_chapter/chapter2-8.html" }
            ]
        },
        {
            title: "第三章—投資策略篇",
            chapters: [
                { name: "第一回《常見的投資股票工具》", link: "gugu_chapter/chapter3-1.html" },
                { name: "第二回《常見分析方式》", link: "gugu_chapter/chapter3-2.html" },
                { name: "第三回《投資組合》", link: "gugu_chapter/chapter3-3.html" },
                { name: "第四回《降低風險》", link: "gugu_chapter/chapter3-4.html" },
                { name: "第五回《永續與責任投資》", link: "gugu_chapter/chapter3-5.html" },
                { name: "第六回《快打短線——短線與極短線交易策略》", link: "gugu_chapter/chapter3-6.html" },
                { name: "第七回《中線趨勢追蹤——中期與趨勢跟蹤策略》", link: "gugu_chapter/chapter3-7.html" },
                { name: "第八回《宏觀視野——長線與宏觀投資策略》", link: "gugu_chapter/chapter3-8.html" }
            ]
        },
        {
            title: "第四章—投資人日常篇",
            chapters: [
                { name: "第一回《消息與數據》", link: "gugu_chapter/chapter4-1.html" },
                { name: "第二回《投資人日常》", link: "gugu_chapter/chapter4-2.html" },
                { name: "第三回《常見誤區》", link: "gugu_chapter/chapter4-3.html" },
                { name: "第四回《相關網站參考》", link: "gugu_chapter/chapter4-4.html" },
                { name: "第五回《理性面對漲跌》", link: "gugu_chapter/chapter4-5.html" },
                { name: "第六回《投資不是賭博》", link: "gugu_chapter/chapter4-6.html" },
                { name: "第七回《長期投資的重要》", link: "gugu_chapter/chapter4-7.html" },
                { name: "第八回《投資資金控管》", link: "gugu_chapter/chapter4-8.html" }
            ]
        },
        {
            title: "第五章—永續與長期投資篇",
            chapters: [
                { name: "第一回《ESG 是什麼》", link: "gugu_chapter/chapter5-1.html" },
                { name: "第二回《永續金融的重要性》", link: "gugu_chapter/chapter5-2.html" },
                { name: "第三回《永續投資的方式》", link: "gugu_chapter/chapter5-3.html" },
                { name: "第四回《ESG 相關 ETF》", link: "gugu_chapter/chapter5-4.html" },
                { name: "第五回《長期投資是什麼》", link: "gugu_chapter/chapter5-5.html" },
                { name: "第六回《長期投資的優勢》", link: "gugu_chapter/chapter5-6.html" },
                { name: "第七回《如何選擇合適的長期投資工具》", link: "gugu_chapter/chapter5-7.html" }
            ]
        }
    ];

    const mascotContainer = document.getElementById('mascot-helper');

    // 找出或建立對話框
    let bubble = document.querySelector('.chat-bubble');
    if (!bubble && mascotContainer) {
        bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        mascotContainer.appendChild(bubble);
    }

    // 2. 渲染目錄
    function renderMenu() {
        let html = `<div class="menu-title">📚 GUGU 漫畫教室</div>`;

        comicData.forEach((vol, index) => {
            html += `
                <div>
                    <button class="vol-btn" onclick="toggleChapters(${index})">
                        ${vol.title} <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul id="chapter-list-${index}" class="chapter-list">
                        ${vol.chapters.map(ch => {
                            if (!ch.link) {
                                return `
                                    <li>
                                        <a href="javascript:void(0)" style="opacity:.55; cursor:not-allowed;">📄 ${ch.name}（即將開放）</a>
                                    </li>
                                `;
                            }
                            return `
                                <li>
                                    <a href="${ch.link}" target="_blank">📄 ${ch.name}</a>
                                </li>
                            `;
                        }).join('')}
                    </ul>
                </div>
            `;
        });

        html += `
            <div style="text-align:center; margin-top:10px; font-size:0.8rem; color:#888; cursor:pointer;"
                 onclick="closeBubble()">❌ 關閉選單</div>
        `;

        bubble.innerHTML = html;
    }

    // 3. 吉祥物點擊事件 (修正版：綁定在容器上，增加點擊範圍)
    if (mascotContainer) {
        // 移除原本只綁定 img 的寫法
        // 改為直接綁定整個 mascotContainer
        mascotContainer.addEventListener('click', (e) => {
            // 防止點擊目錄內部的連結時，目錄又因為冒泡而被關閉
            // 但我們要允許點擊目錄內的按鈕，所以這裡做一個判斷
            
            // 如果點擊的是「關閉按鈕」或「目錄內容」，交給原本的邏輯處理，不這裡擋
            if (e.target.closest('.chat-bubble')) {
                return; 
            }

            e.stopPropagation(); // 阻止事件傳到 document 導致立刻關閉
            
            if (bubble.style.display === 'block') {
                bubble.style.display = 'none';
            } else {
                renderMenu();
                bubble.style.display = 'block';
            }
        });
    }

    // 點擊其他地方關閉
    document.addEventListener('click', (e) => {
        if (mascotContainer && !mascotContainer.contains(e.target)) {
            bubble.style.display = 'none';
        }
    });

    // === 全域函式 ===
    window.toggleChapters = function(index) {
        const list = document.getElementById(`chapter-list-${index}`);
        const allLists = document.querySelectorAll('.chapter-list');

        allLists.forEach(el => {
            if (el !== list) el.style.display = 'none';
        });

        list.style.display = (list.style.display === 'block') ? 'none' : 'block';
    };

    window.closeBubble = function() {
        if (bubble) bubble.style.display = 'none';
    };
});
