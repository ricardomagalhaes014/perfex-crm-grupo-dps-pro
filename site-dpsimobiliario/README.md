# Ficheiros do site dpsimobiliario.pt

Cópia versionada do que está ao vivo em **dpsimobiliario.pt**. Este
repositório é do CRM; o site vive noutra conta de FTP e não tinha
controlo de versões nenhum — todo o trabalho existia num único ficheiro no
servidor, sem histórico e sem rede.

## Regras

**Isto é um espelho, não é a fonte de publicação.** Editar aqui não muda
nada no site: a publicação é por FTP para a conta do domínio
`dpsimobiliario.pt`, no caminho `simuladorportugal/index.html`.

O fluxo é: descarregar o ficheiro ao vivo → alterar → publicar por FTP →
copiar para aqui e commitar. Descarregar primeiro **não é opcional**: quem
publicar a partir de uma cópia antiga apaga o que outra pessoa fez pelo
meio.

A pasta está fechada por `.htaccess`. O deploy do CRM sincroniza o
repositório todo para o docroot, e sem isso ficaria aqui uma segunda cópia
do simulador acessível pela web — foi assim que existiram durante meses
duas cópias mortas em `crm.grupo-dps.com/simuladorportugal/` e
`/dpsimobiliario/simuladorportugal/`, paradas em Julho, a mostrar preços e
disponibilidades que já não eram verdade. Foram apagadas a 12/08/2026.

## Como saber se uma cópia é a viva

A viva pesa cerca de 510 KB e tem o bloco `AURA_RESIDENCE_INJECT` no fim.
As mortas pesavam 8,2 MB e não tinham `gerarPropostaAura`.

## Estados das unidades

Os estados (Disponível / Reservado / Vendido / DPS) **não estão neste
ficheiro**: vivem em `simulator_states.json`, no servidor, escrito por
`save_states.php`. São dados, não código, e mudam várias vezes ao dia —
não têm nada que fazer no repositório.
