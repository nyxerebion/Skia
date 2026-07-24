// click-shop.js

let shopItems = [];
let buyLock = false;
let currentTab = 'ATTACK';

function loadShop() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/click-shop.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "get_shop",
            csrf_token: csrfToken,
        }),
    })
    .then((res) => res.json())
    .then((data) => {
        if (data.success) {
            shopItems = data.shop;
            renderShop(shopItems, data.sections, data.coins, data.clicks);
        } else {
            console.error("Failed to load shop:", data.error);
        }
    })
    .catch((err) => console.error("Shop error:", err));
}

function renderShop(shop, sections, coins, clicks) {
    const container = document.getElementById("shopContainer");
    if (!container) return;

    document.getElementById("shopCoins").textContent = coins;
    document.getElementById("shopClicks").textContent = clicks;

    const ownedLevels = {};
    shop.forEach(item => {
        ownedLevels[item.id] = item.level;
    });

    const isRequirementMet = (need) => {
        if (!need) return true;
        const owned = ownedLevels[need.id] || 0;
        return owned >= need.amount;
    };

    const getItemName = (id) => {
        const item = shop.find(i => i.id === id);
        return item ? item.name : 'Unknown';
    };

    const sectionMap = {};
    const sectionNames = [];
    Object.keys(sections).forEach(section => {
        const items = shop.filter(item => sections[section].includes(item.type));
        if (items.length > 0) {
            sectionMap[section] = items;
            sectionNames.push(section);
        }
    });

    const tabsContainer = document.getElementById("shopTabs");
    tabsContainer.innerHTML = sectionNames.map((section) => `
        <button class="shop-tab" data-section="${section}">
            ${section}
        </button>
    `).join('');

    // Set active tab and render
    tabsContainer.querySelectorAll('.shop-tab').forEach(tab => {
        if (tab.dataset.section === currentTab) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    const itemsToRender = sectionMap[currentTab] || sectionMap[sectionNames[0]] || [];
    renderShopItems(itemsToRender, isRequirementMet, getItemName);

    // Tab click handler
    tabsContainer.querySelectorAll('.shop-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            tabsContainer.querySelectorAll('.shop-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentTab = this.dataset.section;
            renderShopItems(sectionMap[currentTab] || [], isRequirementMet, getItemName);
        });
    });

    // Event delegation for buy buttons
    const itemsContainer = document.getElementById("shopItems");
    itemsContainer.removeEventListener('click', handleBuyClick);
    itemsContainer.addEventListener('click', handleBuyClick);
}

function handleBuyClick(e) {
    const btn = e.target.closest('.shop-buy');
    if (!btn) return;
    if (btn.disabled) return;
    if (buyLock) return;
    const itemId = parseInt(btn.dataset.item);
    buyShopItem(itemId, btn);
}

function renderShopItems(items, isRequirementMet, getItemName) {
    const container = document.getElementById("shopItems");

    if (!items || items.length === 0) {
        container.innerHTML = `<div class="shop-empty">No items in this category</div>`;
        return;
    }

    container.innerHTML = items.map((item) => {
        const reqMet = isRequirementMet(item.need);
        return `
            <div class="shop-item ${item.maxed ? 'maxed' : ''}">
                <div class="shop-item-icon">${item.icon}</div>
                <div class="shop-item-info">
                    <h4>${item.name}</h4>
                    <p>${item.description}</p>
                    <span class="shop-item-level">Level ${item.level}/${item.max_level}</span>

                    ${item.need ? `
                        <span class="shop-item-need ${reqMet ? 'unlocked' : 'locked'}">
                            ${reqMet ? '✅' : '🔒'} Requires: ${getItemName(item.need.id)} x${item.need.amount}
                        </span>
                    ` : ``}
                </div>
                <div>
                    ${item.maxed
                        ? '<span class="maxed-label">MAXED</span>'
                        : `<div class="shop-buy-container">
                            <span class="requirements">
                                Need: 💰${item.cost.coins} | 👆${item.cost.clicks}
                            </span>
                            <button class="btn shop-buy" data-item="${item.id}">
                                ${reqMet ? 'Purchase' : '🔒 Locked'}
                            </button>
                        </div>`
                    }
                </div>
            </div>
        `;
    }).join('');
}

function buyShopItem(itemId, btn) {
    if (buyLock) return;
    buyLock = true;

    if (btn) {
        btn.disabled = true;
        btn.textContent = "⏳ Buying...";
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    fetch(API_URL + "/games/click-shop.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "buy",
            item_id: itemId,
            csrf_token: csrfToken,
        }),
    })
    .then((res) => {
        return res.json();
    })
    .then((data) => {
        if (data.success) {
            updateStatsDisplay(data.stats);
            toastMessage(data.message, "win");
            loadShop();
        } else {
            toastMessage(data.error || "Purchase failed", "error");
            if (btn) {
                btn.disabled = false;
                btn.textContent = "Purchase";
            }
        }
    })
    .catch((err) => {
        toastMessage("Purchase failed. Retrying...", "error");
        if (btn) {
            btn.disabled = false;
            btn.textContent = "Purchase";
        }
    })
    .finally(() => {
        buyLock = false;
        if (btn && !btn.disabled) {
            btn.textContent = "Purchase";
        }
    });
}
