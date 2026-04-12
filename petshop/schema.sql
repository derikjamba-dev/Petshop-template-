-- =====================================================
-- PataVerde Pet Shop — schema.sql
-- Execute: mysql -u root -p pataverde < schema.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS pataverde
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pataverde;

-- ─── USUÁRIOS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(100)  NOT NULL,
  sobrenome     VARCHAR(100)  NOT NULL DEFAULT '',
  email         VARCHAR(180)  NOT NULL UNIQUE,
  telefone      VARCHAR(30)   DEFAULT NULL,
  senha         VARCHAR(255)  NOT NULL,
  admin         TINYINT(1)    NOT NULL DEFAULT 0,
  ativo         TINYINT(1)    NOT NULL DEFAULT 1,
  ultimo_login  DATETIME      DEFAULT NULL,
  criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar usuário admin padrão (senha: Admin@123)
INSERT INTO usuarios (nome, sobrenome, email, senha, admin) VALUES
  ('Admin', 'PataVerde', 'admin@pataverde.co.mz',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE id = id;

-- ─── CATEGORIAS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categorias (
  id    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug  VARCHAR(50) NOT NULL UNIQUE,
  nome  VARCHAR(80) NOT NULL,
  emoji VARCHAR(10) DEFAULT '🐾'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (slug, nome, emoji) VALUES
  ('caes',       'Cães',       '🐕'),
  ('gatos',      'Gatos',      '🐈'),
  ('aves',       'Aves',       '🦜'),
  ('peixes',     'Peixes',     '🐟'),
  ('acessorios', 'Acessórios', '✨')
ON DUPLICATE KEY UPDATE id = id;

-- ─── PRODUTOS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS produtos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(200)   NOT NULL,
  descricao      TEXT           NOT NULL,
  preco          DECIMAL(10,2)  NOT NULL,
  preco_anterior DECIMAL(10,2)  DEFAULT NULL,
  categoria      VARCHAR(50)    NOT NULL,
  estoque        INT UNSIGNED   NOT NULL DEFAULT 0,
  emoji          VARCHAR(10)    DEFAULT '📦',
  badge          VARCHAR(50)    DEFAULT NULL,
  destaque       TINYINT(1)     NOT NULL DEFAULT 0,
  ativo          TINYINT(1)     NOT NULL DEFAULT 1,
  imagem_url     VARCHAR(500)   DEFAULT NULL,
  criado_em      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  DATETIME       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria),
  INDEX idx_ativo     (ativo),
  INDEX idx_destaque  (destaque),
  FULLTEXT  idx_busca (nome, descricao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO produtos (nome, descricao, preco, preco_anterior, categoria, estoque, emoji, badge, destaque) VALUES
  ('Ração Premium Adulto Cão', 'Ração super premium com frango e arroz para cães adultos. Rico em proteínas e vitaminas.', 850.00, 1000.00, 'caes', 50, '🦴', 'Mais Vendido', 1),
  ('Areia Sanitária Gatos 4kg', 'Areia sanitária aglomerante com controle de odor de longa duração. Ideal para todos os tipos de gatos.', 320.00, NULL, 'gatos', 80, '🐈', NULL, 0),
  ('Brinquedo Corda Interativo', 'Brinquedo de corda resistente para cães de médio e grande porte. Ideal para brincadeiras e dentição.', 180.00, 250.00, 'caes', 100, '🎾', 'Oferta', 1),
  ('Ração para Gatos Filhotes', 'Ração balanceada formulada para filhotes de gatos de até 12 meses. Com DHA para desenvolvimento cerebral.', 490.00, NULL, 'gatos', 60, '🐟', NULL, 1),
  ('Mistura para Canários 1kg', 'Mix de sementes e grãos selecionados para canários, periquitos e outros pássaros tropicais.', 145.00, NULL, 'aves', 200, '🦜', NULL, 0),
  ('Aquário Starter Kit 20L', 'Kit completo com aquário 20 litros, filtro, termômetro e decoração. Ideal para iniciantes.', 1200.00, 1500.00, 'peixes', 25, '🐠', 'Kit Completo', 1),
  ('Coleira Ajustável Anti-Pulgas', 'Coleira impregnada com repelente natural contra pulgas, carrapatos e mosquitos. Duração: 8 meses.', 220.00, NULL, 'caes', 75, '🪢', NULL, 0),
  ('Casa de Transporte Premium', 'Caixa de transporte rígida com ventilação superior e lateral. Aprovada por companhias aéreas. Tamanho M.', 750.00, 900.00, 'acessorios', 30, '🏠', 'Destaque', 1),
  ('Shampoo Neutro para Cães', 'Shampoo pH neutro com extrato de Aloe Vera e Camomila. Hipoalergênico, indicado para peles sensíveis.', 195.00, NULL, 'caes', 90, '🛁', NULL, 0),
  ('Arranhador para Gatos', 'Arranhador em sisal natural com plataforma superior para descanso. Altura: 60cm.', 580.00, 700.00, 'gatos', 40, '🏔️', 'Novo', 1),
  ('Vitamina Complexo B para Cães', 'Suplemento vitamínico completo para cães. Contribui para pele e pelagem saudáveis. 60 comprimidos.', 290.00, NULL, 'caes', 120, '💊', NULL, 0),
  ('Aquecedor de Aquário 100W', 'Aquecedor submersível com termostato automático e proteção contra superaquecimento.', 350.00, 420.00, 'peixes', 35, '🌡️', NULL, 0)
ON DUPLICATE KEY UPDATE id = id;

-- ─── PEDIDOS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT UNSIGNED  DEFAULT NULL,
  nome_entrega  VARCHAR(150)  NOT NULL,
  telefone      VARCHAR(30)   NOT NULL,
  endereco      VARCHAR(300)  NOT NULL,
  cidade        VARCHAR(100)  NOT NULL,
  referencia    VARCHAR(200)  DEFAULT NULL,
  pagamento     ENUM('mpesa','emola','dinheiro','cartao') NOT NULL,
  mpesa_num     VARCHAR(30)   DEFAULT NULL,
  cupom         VARCHAR(30)   DEFAULT NULL,
  subtotal      DECIMAL(12,2) NOT NULL,
  desconto      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  frete         DECIMAL(10,2) NOT NULL DEFAULT 150.00,
  total         DECIMAL(12,2) NOT NULL,
  status        ENUM('pendente','confirmado','em_preparo','a_caminho','entregue','cancelado') NOT NULL DEFAULT 'pendente',
  observacoes   TEXT          DEFAULT NULL,
  criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_usuario  (usuario_id),
  INDEX idx_status   (status),
  INDEX idx_criado   (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ITENS DO PEDIDO ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido_itens (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT UNSIGNED  NOT NULL,
  produto_id      INT UNSIGNED  DEFAULT NULL,
  nome_produto    VARCHAR(200)  NOT NULL,
  preco_unitario  DECIMAL(10,2) NOT NULL,
  quantidade      INT UNSIGNED  NOT NULL,
  total           DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL,
  INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CARRINHO ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS carrinho (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  produto_id  INT UNSIGNED NOT NULL,
  quantidade  INT UNSIGNED NOT NULL DEFAULT 1,
  adicionado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_product (usuario_id, produto_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── REVIEWS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produto_id  INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NOT NULL,
  nota        TINYINT UNSIGNED NOT NULL CHECK (nota BETWEEN 1 AND 5),
  comentario  TEXT DEFAULT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (produto_id, usuario_id),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── NEWSLETTER ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(180) NOT NULL UNIQUE,
  ativo       TINYINT(1)   NOT NULL DEFAULT 1,
  inscrito_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── USOS DE CUPOM ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS cupom_usos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cupom       VARCHAR(30)  NOT NULL,
  pedido_id   INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED DEFAULT NULL,
  usado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── VIEW: DASHBOARD ADMIN ───────────────────────────────
CREATE OR REPLACE VIEW v_dashboard AS
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE admin = 0)         AS total_clientes,
  (SELECT COUNT(*) FROM produtos WHERE ativo = 1)          AS total_produtos,
  (SELECT COUNT(*) FROM pedidos)                           AS total_pedidos,
  (SELECT COALESCE(SUM(total),0) FROM pedidos
   WHERE status NOT IN ('cancelado'))                      AS receita_total,
  (SELECT COUNT(*) FROM pedidos WHERE status='pendente')   AS pedidos_pendentes,
  (SELECT COUNT(*) FROM newsletter WHERE ativo = 1)        AS newsletter_inscritos;
