# Drop Nexus Fiber 🚀

O **Drop Nexus Fiber** é um ecossistema completo de gestão e operações focado em provedores de telecomunicações e infraestrutura de fibra óptica (ISPs). Ele conta com um backend robusto em PHP (com arquitetura MVC customizada), além de contar com um inicializador de ecossistema embutido de alto nível.

## 🏗 Arquitetura do Projeto

O projeto adota uma arquitetura conteinerizada via Docker, garantindo isolamento, ambiente padronizado e fácil manutenção. A stack principal engloba:

- **Backend (PHP/Apache):** Arquitetura MVC customizada baseada em roteamento próprio e controllers (`App\Controllers`).
- **Banco de Dados (MySQL 8):** Gerenciamento relacional de todas as entidades, logs e transações do sistema.
- **Motor de Ativação (Instalador Integrado):** Assistente cinematográfico em `/install` responsável por verificar requisitos (Health Check), aplicar *migrations*, configurar rotas e checar a integridade pós-instalação.

## 🗂 Estrutura de Diretórios

```text
/
├── backend/            # Lógica central do sistema (MVC PHP)
│   ├── app/            # Controllers, Models e Core Router
│   ├── config/         # Configurações do sistema e conexão com BD
│   ├── database/       # Scripts SQL base e Migrations
│   ├── public/         # Entrypoint do backend (index.php) e assets globais
│   └── storage/        # Logs de erro, uploads e arquivos temporários

├── install/            # Motor de Ativação (Instalador, Health Check, Post Check)
├── docker-compose.yml  # Orquestração dos containers (app, db)
├── Dockerfile          # Definição customizada do container principal PHP/Apache
└── index.php           # Entrypoint da raiz do servidor web
```

## 🧩 Módulos do Sistema (Core)

O sistema é dividido em diversos módulos operacionais que cobrem de ponta a ponta a rotina de um provedor de infraestrutura:

1. **Dashboard & Engenharia (`/simulador`, `/calculadora`)**
   - Ferramentas de cálculo, simulação de redes ópticas, viabilidade de atendimento e tabelas de atenuação.
2. **Ordem de Serviço - O.S (`/os`)**
   - Criação, atribuição, checkout e finalização de serviços de campo (instalações, reparos, desativações).
3. **Gestão de Estoque (`/estoque`)**
   - Controle de materiais (cabos, caixas, ONUs, roteadores), rastreabilidade, transferências entre técnicos e relatórios de consumo.
4. **Segurança do Trabalho (`/apr`, `/perigo`)**
   - **APR (Análise Preliminar de Risco):** Formulários digitais obrigatórios para avaliação de risco pelas equipes de campo.
   - Mapeamento e relato de perigos na operação.
5. **Gestão de Equipe e RH (`/equipe`)**
   - Cadastro, permissões e gerenciamento de técnicos, líderes e auditores.
6. **Mapeamento e Projetos (`/mapa`)**
   - Visualização de projetos de rede no mapa georreferenciado (compatível com uploads MUBI/GeoJSON).
7. **Academia e Biblioteca (`/academia`, `/biblioteca`)**
   - Plataforma de capacitação, aplicação de quizzes técnicos de nivelamento e base de conhecimento com Procedimentos Operacionais Padrão (POPs).
8. **Relatórios (`/relatorios`)**
   - Inteligência do provedor com importação e exportação de bases de dados via CSV e emissão de consolidados.

## ⚙️ Requisitos e Instalação

O ambiente foi totalmente planejado para rodar nativamente via **Docker**, blindando contra problemas clássicos do XAMPP (como cache agressivo de PHP e problemas de socket de banco de dados).

### Pré-requisitos
- [Docker](https://www.docker.com/) e [Docker Compose](https://docs.docker.com/compose/) instalados.

### Passo a passo (Ambiente Local / Dev)

1. **Subir os containers:**
   Na raiz do projeto, execute o comando:
   ```bash
   docker-compose up -d
   ```
   *Isso inicializará os 2 serviços vitais: `drop_app` (PHP/Apache - porta 8000) e `drop_db` (MySQL).*

2. **Acessar o Instalador Integrado:**
   Com os containers de pé, abra o navegador e acesse:
   ```text
   http://localhost:8000/install/index.php
   ```
   - No Passo 2, clique no botão **"🐳 Usar Docker (db)"** para preencher automaticamente as credenciais seguras do contêiner (`db`, `root`, `drop_root_pass`).
   - Finalize o processo para que o instalador injete o schema SQL base no banco.

3. **Verificação do Sistema (Diagnóstico):**
   Acesse a ferramenta de verificação contínua para validar as permissões de pastas (`/storage`), criação dos arquivos de configuração e saúde estrutural do MySQL:
   ```text
   http://localhost:8000/install/health-check.php
   ```
   *Nota: Em produção, após tudo estar validado, a pasta `/install` deve ser removida por segurança.*

## 🔧 Stack Tecnológica

**Stack:**
- PHP 8+ (Vanilla MVC com Router próprio e Controllers estruturados)
- Apache HTTP Server (`mod_rewrite` habilitado via `.htaccess` e Dockerfile)
- MySQL 8.0 (Integração otimizada nativa via PDO / `mysqli`)
