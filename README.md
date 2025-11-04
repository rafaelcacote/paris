# 📄 Paris - Sistema de Gestão de Notas Fiscais

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?style=for-the-badge)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.2-3178C6?style=for-the-badge&logo=typescript&logoColor=white)

**Sistema moderno e completo para gestão de notas fiscais com extração automática de dados de PDFs**

[Características](#-características) • [Tecnologias](#-tecnologias) • [Instalação](#-instalação) • [Uso](#-uso)

</div>

---

## ✨ Características

### 🎯 Gestão de Notas Fiscais
- **Upload de PDFs** - Envio simples e seguro de arquivos PDF
- **Extração Automática** - Extração inteligente de dados diretamente dos PDFs
- **Visualização Completa** - Visualização detalhada de todas as informações da nota fiscal
- **Controle de Status** - Acompanhamento do status de pagamento das notas fiscais
- **Download de PDFs** - Download facilitado dos arquivos originais

### 👥 Gestão de Usuários
- **CRUD Completo** - Criação, edição, visualização e exclusão de usuários
- **Controle de Status** - Gerenciamento do status de ativação dos usuários
- **CPF e Validações** - Sistema completo de validação de CPF
- **Alteração de Senhas** - Funcionalidade dedicada para atualização de senhas

### 🔐 Segurança e Autenticação
- **Autenticação Completa** - Login, registro e recuperação de senha
- **Verificação de Email** - Sistema de verificação de email para novos usuários
- **Autenticação de Dois Fatores (2FA)** - Segurança adicional com código QR e códigos de recuperação
- **Reset de Senha** - Recuperação segura de senhas via email

### 🎨 Interface Moderna
- **Design Responsivo** - Interface adaptável para desktop, tablet e mobile
- **Modo Escuro** - Suporte completo para tema claro e escuro
- **Componentes Reutilizáveis** - Biblioteca completa de componentes UI modernos
- **Experiência Fluida** - Navegação rápida e responsiva com Inertia.js

---

## 🛠 Tecnologias

### Backend
- **[Laravel 12](https://laravel.com)** - Framework PHP moderno e poderoso
- **[Laravel Fortify](https://github.com/laravel/fortify)** - Autenticação headless
- **[smalot/pdfparser](https://github.com/smalot/pdfparser)** - Extração de dados de PDFs
- **[Pest PHP](https://pestphp.com)** - Framework de testes elegante

### Frontend
- **[Vue 3](https://vuejs.org)** - Framework JavaScript progressivo
- **[Inertia.js v2](https://inertiajs.com)** - Construa aplicações SPA sem API
- **[Tailwind CSS v4](https://tailwindcss.com)** - Framework CSS utility-first
- **[Reka UI](https://reka-ui.com)** - Componentes UI acessíveis e modernos
- **[TypeScript](https://www.typescriptlang.org)** - Tipagem estática para JavaScript
- **[Vite](https://vitejs.dev)** - Build tool rápida e moderna

### Ferramentas de Desenvolvimento
- **[Laravel Pint](https://laravel.com/docs/pint)** - Code formatter
- **[ESLint](https://eslint.org)** - Linter JavaScript/TypeScript
- **[Prettier](https://prettier.io)** - Code formatter

---

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- **PHP** >= 8.3
- **Composer** >= 2.0
- **Node.js** >= 18.x
- **npm** >= 9.x
- **PostgreSQL** >= 13 (ou outro banco de dados suportado)
- **Git**

---

## 🚀 Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/rafaelcacote/paris.git
cd paris
```

### 2. Instale as dependências PHP

```bash
composer install
```

### 3. Instale as dependências Node.js

```bash
npm install
```

### 4. Configure o ambiente

Copie o arquivo de exemplo e configure as variáveis de ambiente:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações:

```env
APP_NAME=Paris
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=paris
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 5. Gere a chave da aplicação

```bash
php artisan key:generate
```

### 6. Execute as migrações

```bash
php artisan migrate
```

### 7. Compile os assets

```bash
npm run build
```

Ou para desenvolvimento com hot reload:

```bash
npm run dev
```

### 8. Inicie o servidor

```bash
php artisan serve
```

Acesse `http://localhost:8000` no seu navegador.

---

## 🎮 Uso

### Comandos Úteis

#### Desenvolvimento

```bash
# Iniciar servidor PHP, fila e Vite simultaneamente
composer run dev

# Iniciar com SSR (Server-Side Rendering)
composer run dev:ssr

# Compilar assets para produção
npm run build
```

#### Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=NotaFiscalTest

# Executar testes com coverage
php artisan test --coverage
```

#### Formatação de Código

```bash
# Formatar código PHP
vendor/bin/pint

# Formatar código JavaScript/TypeScript
npm run format

# Verificar formatação sem aplicar
npm run format:check
```

#### Linting

```bash
# Verificar e corrigir problemas de lint
npm run lint
```

---

## 📁 Estrutura do Projeto

```
paris/
├── app/
│   ├── Actions/          # Actions do Fortify (autenticação)
│   ├── Console/          # Comandos Artisan
│   ├── Http/
│   │   ├── Controllers/  # Controladores
│   │   ├── Middleware/   # Middlewares
│   │   └── Requests/     # Form Requests (validação)
│   ├── Models/           # Modelos Eloquent
│   ├── Providers/        # Service Providers
│   └── Services/         # Serviços (ex: NotaFiscalPdfExtractor)
├── database/
│   ├── factories/        # Factories para testes
│   ├── migrations/       # Migrações do banco de dados
│   └── seeders/          # Seeders
├── resources/
│   ├── js/
│   │   ├── components/   # Componentes Vue reutilizáveis
│   │   ├── layouts/      # Layouts da aplicação
│   │   ├── pages/        # Páginas Inertia
│   │   └── routes/       # Rotas tipadas (Wayfinder)
│   └── css/              # Estilos CSS
├── routes/
│   ├── web.php           # Rotas web
│   └── settings.php      # Rotas de configurações
└── tests/                # Testes Pest
```

---

## 🧪 Testes

Este projeto utiliza [Pest PHP](https://pestphp.com) para testes. Os testes estão organizados em:

- `tests/Feature/` - Testes de funcionalidades completas
- `tests/Unit/` - Testes unitários

### Executando Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=AuthenticationTest

# Executar com coverage
php artisan test --coverage
```

---

## 🔧 Configuração Adicional

### Autenticação de Dois Fatores (2FA)

O sistema possui suporte completo para 2FA:
- Geração de código QR para configuração
- Códigos de recuperação
- Confirmação de senha antes de habilitar

### Extração de PDFs

O serviço `NotaFiscalPdfExtractor` extrai automaticamente:
- Número da nota fiscal
- Código de verificação
- Data de emissão
- Valores e impostos
- Informações do tomador de serviço
- E muito mais...

---

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para:

1. Fazer um fork do projeto
2. Criar uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abrir um Pull Request

---

## 📧 Contato

**Rafael Cacote**

- GitHub: [@rafaelcacote](https://github.com/rafaelcacote)
- Repositório: [https://github.com/rafaelcacote/paris](https://github.com/rafaelcacote/paris)

---

<div align="center">

Feito com ❤️ usando Laravel e Vue.js

**[⬆ Voltar ao topo](#-paris---sistema-de-gestão-de-notas-fiscais)**

</div>

