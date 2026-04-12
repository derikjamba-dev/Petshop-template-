/* =====================================================
   PataVerde Pet Shop — app.js
   ===================================================== */

'use strict';

// === STATE ===
const state = {
  cart: JSON.parse(localStorage.getItem('pataverde_cart') || '[]'),
  user: JSON.parse(localStorage.getItem('pataverde_user') || 'null'),
  coupon: null,
  discount: 0,
  currentFilter: 'todos',
  searchQuery: '',
};

// === PRODUCTS DATA ===
const PRODUCTS = [
  { id:1,  nome:'Ração Premium Adulto Cão', categoria:'caes',      emoji:'🦴', preco:850,  precoAnterior:1000, desc:'Ração super premium com frango e arroz para cães adultos. Rico em proteínas e vitaminas.', badge:'Mais Vendido' },
  { id:2,  nome:'Areia Sanitária Gatos 4kg', categoria:'gatos',    emoji:'🐈', preco:320,  precoAnterior:null, desc:'Areia sanitária aglomerante com controle de odor de longa duração. Ideal para todos os tipos de gatos.', badge:null },
  { id:3,  nome:'Brinquedo Corda Interativo', categoria:'caes',    emoji:'🎾', preco:180,  precoAnterior:250,  desc:'Brinquedo de corda resistente para cães de médio e grande porte. Ideal para brincadeiras e dentição.', badge:'Oferta' },
  { id:4,  nome:'Ração para Gatos Filhotes', categoria:'gatos',    emoji:'🐟', preco:490,  precoAnterior:null, desc:'Ração balanceada formulada para filhotes de gatos de até 12 meses. Com DHA para desenvolvimento cerebral.', badge:null },
  { id:5,  nome:'Mistura para Canários 1kg',categoria:'aves',      emoji:'🦜', preco:145,  precoAnterior:null, desc:'Mix de sementes e grãos selecionados para canários, periquitos e outros pássaros tropicais.', badge:null },
  { id:6,  nome:'Aquário Starter Kit 20L',  categoria:'peixes',    emoji:'🐠', preco:1200, precoAnterior:1500, desc:'Kit completo com aquário 20 litros, filtro, termômetro e decoração. Ideal para iniciantes.', badge:'Kit Completo' },
  { id:7,  nome:'Coleira Ajustável Anti-Pulgas', categoria:'caes', emoji:'🪢', preco:220,  precoAnterior:null, desc:'Coleira impregnada com repelente natural contra pulgas, carrapatos e mosquitos. Duração: 8 meses.', badge:null },
  { id:8,  nome:'Casa de Transporte Premium', categoria:'acessorios',emoji:'🏠',preco:750, precoAnterior:900,  desc:'Caixa de transporte rígida com ventilação superior e lateral. Aprovada por companhias aéreas. Tamanho M.', badge:'Destaque' },
  { id:9,  nome:'Shampoo Neutro para Cães', categoria:'caes',      emoji:'🛁', preco:195,  precoAnterior:null, desc:'Shampoo pH neutro com extrato de Aloe Vera e Camomila. Hipoalergênico, indicado para peles sensíveis.', badge:null },
  { id:10, nome:'Arranhador para Gatos',    categoria:'gatos',     emoji:'🏔️', preco:580,  precoAnterior:700,  desc:'Arranhador em sisal natural com plataforma superior para descanso. Altura: 60cm. Fretes incluso.', badge:'Novo' },
  { id:11, nome:'Vitamina Complexo B para Cães', categoria:'caes', emoji:'💊', preco:290,  precoAnterior:null, desc:'Suplemento vitamínico completo para cães. Contribui para pele e pelagem saudáveis. 60 comprimidos.', badge:null },
  { id:12, nome:'Aquecedor de Aquário 100W',categoria:'peixes',    emoji:'🌡️', preco:350,  precoAnterior:420,  desc:'Aquecedor submersível com termostato automático e proteção contra superaquecimento.', badge:null },
];

const COUPONS = {
  'PATA15': 15,
  'PET10':  10,
  'WELCOME': 20,
};

// === FORMAT ===
const fmt = (v) => `MT ${v.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// === TOAST ===
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast${type === 'error' ? ' error' : ''}`;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

// === CART UTILS ===
function saveCart() {
  localStorage.setItem('pataverde_cart', JSON.stringify(state.cart));
}
function cartTotal() {
  return state.cart.reduce((s, i) => s + i.preco * i.qty, 0);
}
function cartCount() {
  return state.cart.reduce((s, i) => s + i.qty, 0);
}
function updateCartUI() {
  const total = cartTotal();
  const count = cartCount();
  const badge = document.getElementById('cartCount');
  badge.textContent = count;
  badge.classList.toggle('visible', count > 0);

  const cartItems = document.getElementById('cartItems');
  const cartFooter = document.getElementById('cartFooter');
  const cartTotalEl = document.getElementById('cartTotal');

  if (state.cart.length === 0) {
    cartItems.innerHTML = `
      <div class="cart-empty">
        <span>🛒</span>
        <p>Seu carrinho está vazio</p>
        <button class="btn-primary" onclick="closeCart()">Ver produtos</button>
      </div>`;
    cartFooter.style.display = 'none';
    return;
  }

  cartFooter.style.display = 'block';
  const discountAmount = (total * state.discount) / 100;
  const finalTotal = total - discountAmount;
  cartTotalEl.textContent = fmt(finalTotal);

  cartItems.innerHTML = state.cart.map(item => `
    <div class="cart-item" data-id="${item.id}">
      <div class="cart-item-img">${item.emoji}</div>
      <div class="cart-item-info">
        <div class="cart-item-name">${item.nome}</div>
        <div class="cart-item-price">${fmt(item.preco * item.qty)}</div>
        <div class="cart-item-controls">
          <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
        </div>
      </div>
      <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Remover">🗑️</button>
    </div>
  `).join('');
}

function addToCart(id) {
  const product = PRODUCTS.find(p => p.id === id);
  if (!product) return;
  const existing = state.cart.find(i => i.id === id);
  if (existing) {
    existing.qty++;
  } else {
    state.cart.push({ id: product.id, nome: product.nome, preco: product.preco, emoji: product.emoji, qty: 1 });
  }
  saveCart();
  updateCartUI();
  showToast(`🛒 ${product.nome} adicionado ao carrinho!`);

  // Animate button
  const btn = document.querySelector(`[data-add-id="${id}"]`);
  if (btn) {
    btn.textContent = '✓ Adicionado';
    btn.style.background = 'var(--terra)';
    setTimeout(() => {
      btn.textContent = 'Adicionar';
      btn.style.background = '';
    }, 1200);
  }
}

function removeFromCart(id) {
  state.cart = state.cart.filter(i => i.id !== id);
  saveCart();
  updateCartUI();
  showToast('Produto removido do carrinho');
}

function changeQty(id, delta) {
  const item = state.cart.find(i => i.id === id);
  if (!item) return;
  item.qty = Math.max(1, item.qty + delta);
  if (item.qty === 0) {
    removeFromCart(id);
  } else {
    saveCart();
    updateCartUI();
  }
}

// === CART SIDEBAR ===
function openCart() {
  document.getElementById('cartSidebar').classList.add('open');
  document.getElementById('cartOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cartSidebar').classList.remove('open');
  document.getElementById('cartOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

// === PRODUCTS RENDER ===
function renderProducts(filter = 'todos', query = '') {
  const grid = document.getElementById('productsGrid');
  let filtered = PRODUCTS;
  if (filter !== 'todos') filtered = filtered.filter(p => p.categoria === filter);
  if (query) filtered = filtered.filter(p => p.nome.toLowerCase().includes(query.toLowerCase()) || p.desc.toLowerCase().includes(query.toLowerCase()));

  if (filtered.length === 0) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 0;color:var(--text-muted)">
      <div style="font-size:3rem;margin-bottom:12px">🔍</div>
      <p>Nenhum produto encontrado para "<strong>${query}</strong>"</p>
    </div>`;
    return;
  }

  grid.innerHTML = filtered.map((p, i) => `
    <div class="product-card" style="animation-delay:${i * 0.05}s" onclick="openProductDetail(${p.id})">
      <div class="product-img">
        ${p.badge ? `<span class="product-badge">${p.badge}</span>` : ''}
        <button class="product-fav" onclick="event.stopPropagation();toggleFav(${p.id},this)" title="Favoritar">🤍</button>
        <span>${p.emoji}</span>
      </div>
      <div class="product-info">
        <div class="product-category">${categoryLabel(p.categoria)}</div>
        <div class="product-name">${p.nome}</div>
        <div class="product-desc">${p.desc.slice(0, 72)}...</div>
        <div class="product-footer">
          <div class="product-price">
            ${p.precoAnterior ? `<small>${fmt(p.precoAnterior)}</small>` : ''}
            ${fmt(p.preco)}
          </div>
          <button class="add-cart-btn" data-add-id="${p.id}"
            onclick="event.stopPropagation();addToCart(${p.id})">
            Adicionar
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function categoryLabel(cat) {
  const labels = { caes: '🐕 Cães', gatos: '🐈 Gatos', aves: '🦜 Aves', peixes: '🐟 Peixes', acessorios: '✨ Acessórios' };
  return labels[cat] || cat;
}

function toggleFav(id, btn) {
  const isFav = btn.textContent === '❤️';
  btn.textContent = isFav ? '🤍' : '❤️';
  showToast(isFav ? 'Removido dos favoritos' : '❤️ Adicionado aos favoritos!');
}

// === PRODUCT DETAIL MODAL ===
function openProductDetail(id) {
  const p = PRODUCTS.find(pr => pr.id === id);
  if (!p) return;
  document.getElementById('productDetail').innerHTML = `
    <div class="product-detail-img">${p.emoji}</div>
    <div class="product-detail-info">
      <div class="product-category">${categoryLabel(p.categoria)}</div>
      <h2>${p.nome}</h2>
      <div class="product-price">
        ${p.precoAnterior ? `<small>${fmt(p.precoAnterior)}</small>` : ''}
        ${fmt(p.preco)}
      </div>
      <p>${p.desc}</p>
      <div class="qty-select">
        <label>Quantidade:</label>
        <button class="qty-btn" onclick="document.getElementById('detailQty').stepDown()">−</button>
        <input type="number" id="detailQty" value="1" min="1" max="50"
          style="width:50px;text-align:center;border:1px solid var(--border);border-radius:8px;padding:4px;font-size:1rem" />
        <button class="qty-btn" onclick="document.getElementById('detailQty').stepUp()">+</button>
      </div>
      <button class="btn-primary btn-block" onclick="addMultipleToCart(${p.id})">
        🛒 Adicionar ao Carrinho
      </button>
      ${p.precoAnterior ? `<p style="margin-top:12px;color:var(--terra);font-size:0.88rem;font-weight:600">
        💰 Poupa ${fmt(p.precoAnterior - p.preco)} (${Math.round((1 - p.preco/p.precoAnterior)*100)}% desconto)
      </p>` : ''}
    </div>
  `;
  openModal('productOverlay');
}

function addMultipleToCart(id) {
  const qty = parseInt(document.getElementById('detailQty').value) || 1;
  for (let i = 0; i < qty; i++) addToCart(id);
  closeModal('productOverlay');
  openCart();
}

// === MODALS ===
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

// === AUTH ===
async function doLogin(email, password) {
  try {
    const res = await fetch('api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'login', email, password }),
    });
    const data = await res.json();
    if (data.success) {
      state.user = data.user;
      localStorage.setItem('pataverde_user', JSON.stringify(data.user));
      closeModal('authOverlay');
      updateAuthUI();
      showToast(`👋 Bem-vindo, ${data.user.nome}!`);
    } else {
      showToast(data.message || 'Credenciais inválidas.', 'error');
    }
  } catch {
    // Demo mode without backend
    demoLogin(email);
  }
}

async function doRegister(formData) {
  if (formData.password !== formData.password_confirm) {
    showToast('As senhas não coincidem.', 'error'); return;
  }
  try {
    const res = await fetch('api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'register', ...formData }),
    });
    const data = await res.json();
    if (data.success) {
      state.user = data.user;
      localStorage.setItem('pataverde_user', JSON.stringify(data.user));
      closeModal('authOverlay');
      updateAuthUI();
      showToast('🎉 Conta criada com sucesso! Bem-vindo(a)!');
    } else {
      showToast(data.message || 'Erro ao criar conta.', 'error');
    }
  } catch {
    demoRegister(formData);
  }
}

function demoLogin(email) {
  const user = { nome: email.split('@')[0], email };
  state.user = user;
  localStorage.setItem('pataverde_user', JSON.stringify(user));
  closeModal('authOverlay');
  updateAuthUI();
  showToast(`👋 Bem-vindo, ${user.nome}! (modo demo)`);
}

function demoRegister(formData) {
  const user = { nome: formData.nome, email: formData.email };
  state.user = user;
  localStorage.setItem('pataverde_user', JSON.stringify(user));
  closeModal('authOverlay');
  updateAuthUI();
  showToast('🎉 Conta criada! (modo demo)');
}

function logout() {
  state.user = null;
  localStorage.removeItem('pataverde_user');
  updateAuthUI();
  showToast('Sessão encerrada.');
  fetch('api/auth.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action:'logout' }) }).catch(() => {});
}

function updateAuthUI() {
  const loginBtn = document.getElementById('loginBtn');
  const registerBtn = document.getElementById('registerBtn');
  if (state.user) {
    loginBtn.textContent = `👤 ${state.user.nome}`;
    loginBtn.onclick = logout;
    registerBtn.textContent = 'Sair';
    registerBtn.onclick = logout;
  } else {
    loginBtn.textContent = 'Entrar';
    loginBtn.onclick = () => openModal('authOverlay');
    registerBtn.textContent = 'Cadastrar';
    registerBtn.onclick = () => {
      openModal('authOverlay');
      switchTab('register');
    };
  }
}

// === CHECKOUT ===
async function doCheckout(formData) {
  if (state.cart.length === 0) { showToast('Seu carrinho está vazio.', 'error'); return; }
  const total = cartTotal();
  const discountAmount = (total * state.discount) / 100;
  const finalTotal = total - discountAmount + 150;

  const orderData = {
    ...formData,
    items: state.cart,
    subtotal: total,
    desconto: discountAmount,
    total: finalTotal,
    coupon: state.coupon,
  };

  try {
    const res = await fetch('api/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderData),
    });
    const data = await res.json();
    if (data.success) {
      finishOrder(data.order_id);
    } else {
      showToast(data.message || 'Erro ao processar pedido.', 'error');
    }
  } catch {
    // Demo mode
    finishOrder(Math.floor(Math.random() * 90000) + 10000);
  }
}

function finishOrder(orderId) {
  closeModal('checkoutOverlay');
  document.getElementById('orderNumber').textContent = orderId;
  openModal('successOverlay');
  state.cart = [];
  state.discount = 0;
  state.coupon = null;
  saveCart();
  updateCartUI();
  closeCart();
}

function applyCoupon(code) {
  const disc = COUPONS[code.toUpperCase()];
  if (disc) {
    state.discount = disc;
    state.coupon = code.toUpperCase();
    updateCartUI();
    showToast(`🎉 Cupom aplicado! ${disc}% de desconto!`);
    return true;
  } else {
    showToast('Cupom inválido ou expirado.', 'error');
    return false;
  }
}

function fillCheckoutSummary() {
  const items = document.getElementById('checkoutItems');
  const total = cartTotal();
  const discountAmount = (total * state.discount) / 100;
  const finalTotal = total - discountAmount + 150;

  items.innerHTML = state.cart.map(i => `
    <div class="checkout-item-row">
      <span>${i.emoji}</span>
      <span>${i.nome} ×${i.qty}</span>
      <span>${fmt(i.preco * i.qty)}</span>
    </div>
  `).join('');

  document.getElementById('summarySubtotal').textContent = fmt(total);
  document.getElementById('summaryTotal').textContent = fmt(finalTotal);

  const discountLine = document.getElementById('discountLine');
  if (discountAmount > 0) {
    discountLine.style.display = 'flex';
    document.getElementById('summaryDiscount').textContent = `-${fmt(discountAmount)}`;
  } else {
    discountLine.style.display = 'none';
  }
}

// === TABS (AUTH) ===
function switchTab(tab) {
  document.querySelectorAll('.modal-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${tab}`));
}

// === NAVBAR SCROLL ===
function handleScroll() {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
}

// === INIT ===
document.addEventListener('DOMContentLoaded', () => {
  // Render products
  renderProducts();
  updateCartUI();
  updateAuthUI();

  // Navbar scroll
  window.addEventListener('scroll', handleScroll, { passive: true });

  // Cart
  document.getElementById('cartBtn').addEventListener('click', openCart);
  document.getElementById('cartClose').addEventListener('click', closeCart);
  document.getElementById('cartOverlay').addEventListener('click', closeCart);

  // Auth modal
  document.getElementById('loginBtn').addEventListener('click', () => openModal('authOverlay'));
  document.getElementById('registerBtn').addEventListener('click', () => {
    openModal('authOverlay');
    switchTab('register');
  });
  document.getElementById('authClose').addEventListener('click', () => closeModal('authOverlay'));
  document.getElementById('authOverlay').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal('authOverlay');
  });

  // Auth tabs
  document.querySelectorAll('.modal-tab').forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
  });

  // Tab switch links
  document.addEventListener('click', e => {
    if (e.target.dataset.switch) switchTab(e.target.dataset.switch);
  });

  // Login form
  document.getElementById('loginForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    await doLogin(fd.get('email'), fd.get('password'));
  });

  // Register form
  document.getElementById('registerForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    await doRegister(Object.fromEntries(fd));
  });

  // Checkout button
  document.getElementById('checkoutBtn').addEventListener('click', () => {
    if (!state.user) {
      closeCart();
      openModal('authOverlay');
      showToast('Por favor, entre ou cadastre-se para continuar.', 'error');
      return;
    }
    fillCheckoutSummary();
    closeCart();
    openModal('checkoutOverlay');
  });

  // Checkout close
  document.getElementById('checkoutClose').addEventListener('click', () => closeModal('checkoutOverlay'));
  document.getElementById('checkoutOverlay').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal('checkoutOverlay');
  });

  // Checkout form
  document.getElementById('checkoutForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const btn = e.target.querySelector('button[type="submit"]');
    btn.textContent = 'Processando...';
    btn.disabled = true;
    await doCheckout(Object.fromEntries(fd));
    btn.textContent = 'Confirmar Pedido 🎉';
    btn.disabled = false;
  });

  // Payment method toggle
  document.querySelectorAll('input[name="pagamento"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.getElementById('mpesaField').style.display =
        radio.value === 'mpesa' || radio.value === 'emola' ? 'block' : 'none';
    });
  });

  // Success close
  document.getElementById('successClose').addEventListener('click', () => closeModal('successOverlay'));

  // Product modal close
  document.getElementById('productClose').addEventListener('click', () => closeModal('productOverlay'));
  document.getElementById('productOverlay').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal('productOverlay');
  });

  // Filter tabs
  document.getElementById('filterTabs').addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.currentFilter = btn.dataset.filter;
    renderProducts(state.currentFilter, state.searchQuery);
  });

  // Search
  document.getElementById('searchBtn').addEventListener('click', () => {
    const bar = document.getElementById('searchBar');
    bar.classList.toggle('open');
    if (bar.classList.contains('open')) document.getElementById('searchInput').focus();
  });
  document.getElementById('searchClose').addEventListener('click', () => {
    document.getElementById('searchBar').classList.remove('open');
    state.searchQuery = '';
    renderProducts(state.currentFilter, '');
  });
  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.searchQuery = e.target.value;
      renderProducts(state.currentFilter, state.searchQuery);
      if (state.searchQuery) {
        document.getElementById('produtos').scrollIntoView({ behavior: 'smooth' });
      }
    }, 300);
  });

  // Coupon
  document.getElementById('applyCoupon').addEventListener('click', () => {
    const code = document.getElementById('couponInput').value.trim();
    if (code) applyCoupon(code);
  });

  // Promo banner
  document.getElementById('promoShopBtn').addEventListener('click', () => {
    document.getElementById('couponInput').value = 'PATA15';
    document.getElementById('produtos').scrollIntoView({ behavior: 'smooth' });
    showToast('🎁 Código PATA15 pronto para usar no carrinho!');
  });

  // Newsletter
  document.getElementById('newsletterForm').addEventListener('submit', async e => {
    e.preventDefault();
    const email = e.target.querySelector('input').value;
    try {
      await fetch('api/newsletter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      });
    } catch {}
    showToast('📧 Inscrito com sucesso! Obrigado!');
    e.target.reset();
  });

  // Hamburger
  document.getElementById('hamburger').addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
    document.querySelector('.nav-actions').classList.toggle('open');
  });

  // Keyboard ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeModal('authOverlay');
      closeModal('checkoutOverlay');
      closeModal('productOverlay');
      closeModal('successOverlay');
      closeCart();
    }
  });
});
