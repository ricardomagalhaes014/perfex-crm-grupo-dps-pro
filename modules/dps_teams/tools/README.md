# Monitor de Incompatibilidades SQL (CI3 × MariaDB 11.8)

O **CI3 × MariaDB Monitor** é uma ferramenta de diagnóstico desenvolvida para detectar proactivamente problemas de compatibilidade entre o *Query Builder* do CodeIgniter 3 e o MariaDB 11.x, especificamente o erro de escape de identificadores que causa falhas do tipo "Unknown table".

## Funcionalidades

O monitor executa duas camadas de análise:

1. **Análise Estática (RegEx):** Faz *scan* aos ficheiros PHP em busca de padrões conhecidos que geram SQL inválido:
   - `db->select()` com `db_prefix()` e wildcards (`*`)
   - `db->where()` com prefixo de tabela completo
   - Funções depreciadas como `DATE()` em cláusulas WHERE
   - Queries executadas dentro de loops (N+1 queries)
2. **Análise Dinâmica (Base de Dados):**
   - Testa queries reais com JOINs para validar o comportamento de escape do servidor
   - Verifica se há mistura de collations (ex: `utf8mb3_general_ci` vs `utf8mb4_unicode_ci`)
   - Verifica o `@@sql_mode` à procura de modos restritivos (`ONLY_FULL_GROUP_BY`)
   - Identifica colunas sem índices usadas frequentemente em filtros (ex: `tblleads.country`)

## Como Usar

### 🌐 Via Web (Recomendado)

Aceda ao wrapper web através do URL protegido por token:

```
https://crm.grupo-dps.com/dps_monitor.php?token=dps_monitor_2026
```

**Parâmetros de URL:**
- `?token=...` — Obrigatório. Protege o script contra acesso público.
- `&dir=all` — Analisa todos os módulos em `/modules/` (pode demorar ~45 segundos).
- `&dir=nome_do_modulo` — Analisa apenas um módulo específico (ex: `dps_teams`). O comportamento por defeito analisa apenas o módulo `dps_teams` para ser rápido (~0.02s).

### 💻 Via Linha de Comando (CLI)

O script pode ser integrado em pipelines de CI/CD ou executado via SSH:

```bash
php modules/dps_teams/tools/ci3_mariadb_monitor.php
```

**Argumentos CLI:**
- `--dir=/caminho/para/pasta` — Define o directório a analisar.
- `--html` — Força a saída em formato HTML em vez de texto simples.
- `--quiet` — Oculta os avisos do tipo "INFO", mostrando apenas CRITICAL e WARNING.

O script retorna o **código de saída 1** se encontrar problemas críticos, permitindo falhar uma build no GitHub Actions ou GitLab CI.

## Estrutura dos Ficheiros

- `dps_monitor.php` — Fica na raiz do Perfex. É o wrapper web que inclui o script principal e define a constante `PERFEX_ROOT`.
- `modules/dps_teams/tools/ci3_mariadb_monitor.php` — O núcleo do monitor. Lê as credenciais directamente do `application/config/app-config.php` para não duplicar segredos.

## Segurança

O ficheiro `dps_monitor.php` expõe detalhes da base de dados e excertos de código. É fortemente recomendado:
1. Alterar o valor de `MONITOR_TOKEN` no topo do ficheiro `ci3_mariadb_monitor.php`.
2. Remover ou renomear o `dps_monitor.php` quando a auditoria estiver concluída.
