# 🐾 PataVerde Pet Shop

Website completo de pet shop com frontend moderno e backend PHP + MySQL.

## Estrutura do Projeto

```
petshop/
├── index.html              ← Página principal (SPA)
├── schema.sql              ← Schema do banco de dados
├── assets/
│   ├── css/style.css       ← Estilos completos
│   └── js/app.js           ← Lógica frontend
├── config/
│   └── db.php              ← Configuração PDO MySQL
└── api/
    ├── auth.php            ← Login, cadastro, logout
    ├── products.php        ← CRUD de produtos
    ├── cart.php            ← Carrinho server-side
    ├── orders.php          ← Pedidos / checkout
    └── newsletter.php      ← Inscrição newsletter
```

## Funcionalidades

### Frontend
- ✅ Catálogo de produtos com filtros por categoria
- ✅ Busca em tempo real
- ✅ Carrinho de compras (localStorage)
- ✅ Modal de login e cadastro
- ✅ Checkout completo (entrega + pagamento)
- ✅ Cupons de desconto (PATA15, PET10, WELCOME)
- ✅ Favoritos
- ✅ Modal de detalhe do produto
- ✅ Newsletter
- ✅ Toast notifications
- ✅ Design responsivo (mobile-first)
- ✅ Animações e micro-interações

### Backend PHP
- ✅ Autenticação com sessões e bcrypt
- ✅ Registro de utilizadores com validação
- ✅ CRUD de produtos (admin)
- ✅ Carrinho server-side sincronizado
- ✅ Criação de pedidos com validação de estoque
- ✅ Controle de status dos pedidos
- ✅ Sistema de cupons de desconto
- ✅ Newsletter
- ✅ Respostas JSON padronizadas
- ✅ Proteção contra SQL injection (PDO prepared statements)
- ✅ Sanitização de inputs

## Configuração

### 1. Requisitos
- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3
- Servidor web (Apache/Nginx) ou `php -S localhost:8000`

### 2. Banco de Dados
```bash
# Criar banco e importar schema
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pataverde CHARACTER SET utf8mb4"
mysql -u root -p pataverde < schema.sql
```

### 3. Configuração da Conexão
Edite `config/db.php` e altere:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pataverde');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

### 4. Iniciar Servidor de Desenvolvimento
```bash
cd petshop
php -S localhost:8000
```
Acesse: http://localhost:8000

### 5. Credenciais Admin Padrão
- **E-mail:** admin@pataverde.co.mz
- **Senha:** Admin@123

> ⚠️ Altere a senha do admin após o primeiro login!

## Modo Demo (sem backend)
O site funciona em modo demo mesmo sem PHP/MySQL:
- Produtos são carregados do JavaScript
- Carrinho usa localStorage
- Login/Cadastro simulados localmente
- Pedidos confirmados sem chamada ao servidor

## API Endpoints

### Auth (`/api/auth.php`)
| Ação | Método | Body |
|------|--------|------|
| Login | POST | `{action:"login", email, password}` |
| Cadastro | POST | `{action:"register", nome, email, password, ...}` |
| Logout | POST | `{action:"logout"}` |
| Perfil | GET | `?action=me` |

### Produtos (`/api/products.php`)
| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `?categoria=caes` | GET | Filtrar por categoria |
| `?q=ração` | GET | Buscar produtos |
| `?id=1` | GET | Detalhe do produto |
| `/` | POST | Criar produto (admin) |
| `?id=1` | PUT | Editar produto (admin) |
| `?id=1` | DELETE | Remover produto (admin) |

### Pedidos (`/api/orders.php`)
| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/` | POST | Criar pedido (checkout) |
| `/` | GET | Listar meus pedidos |
| `?id=1` | GET | Detalhe do pedido |
| `?id=1` | PUT | Atualizar status (admin) |

### Carrinho (`/api/cart.php`)
| Ação | Descrição |
|------|-----------|
| `get` | Obter carrinho |
| `add` | Adicionar item |
| `remove` | Remover item |
| `update` | Atualizar quantidade |
| `clear` | Limpar carrinho |
| `sync` | Sincronizar com localStorage |

## Cupons de Desconto
| Código | Desconto |
|--------|----------|
| `PATA15` | 15% |
| `PET10` | 10% |
| `WELCOME` | 20% |

## Segurança Implementada
- Senhas com bcrypt (cost 12)
- PDO Prepared Statements (sem SQL injection)
- Sanitização de inputs com `htmlspecialchars`
- Validação de e-mail com `filter_var`
- Sessões PHP seguras
- Verificação de estoque antes do pedido
- Controle de acesso admin por role

## Próximos Passos (Melhorias)
- [ ] Painel administrativo completo
- [ ] Upload de imagens dos produtos
- [ ] Integração M-Pesa API real
- [ ] Envio de e-mail de confirmação (PHPMailer)
- [ ] Rastreamento de pedidos
- [ ] Sistema de avaliações/reviews
- [ ] Wishlist server-side
- [ ] Paginação de produtos
- [ ] Cache Redis
- [ ] JWT para API stateless
