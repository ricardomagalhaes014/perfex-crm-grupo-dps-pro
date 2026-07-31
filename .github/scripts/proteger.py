#!/usr/bin/env python3
"""
Trava o deploy quando o servidor tem trabalho que o repositório não conhece.

O deploy por FTP sobrepõe no servidor todos os ficheiros que existem no
repositório. Isso é o que se quer quando o push traz uma correcção — e é um
desastre quando alguém arranjou alguma coisa directamente no servidor e nunca
a commitou: o deploy repõe a versão velha por cima e o trabalho desaparece
sem uma linha de aviso.

Aconteceu duas vezes:
  29/07/2026 — 23 ficheiros perdidos, o quadro de comissões em branco.
  31/07/2026 — a regra de não-cache do backoffice, apagada pelo deploy que a
               própria sessão que a criou disparou. Custou uma manhã a
               perceber que o painel estava certo e o browser é que mostrava
               uma página velha.

Como decide:
  - Lê os padrões de .github/deploy-protegidos.txt e expande-os com git.
  - Descarrega cada ficheiro do servidor e compara com o do repositório.
  - Um ficheiro DIFERENTE que este push altera  -> é a correcção. Deixa passar.
  - Um ficheiro DIFERENTE que este push NÃO altera -> o servidor tem algo que
    o repositório não tem. PÁRA o deploy e diz quais são.
  - Um ficheiro que não existe no servidor -> é novo. Deixa passar.

A versão que está no servidor é sempre guardada em servidor-antes-do-deploy/,
que o workflow publica como artefacto. Mesmo no caminho em que se deixa
passar, nada se perde.

Para sobrepor de propósito, escrever [sobrepor-servidor] na mensagem do commit.
"""

import ftplib
import hashlib
import io
import os
import subprocess
import sys

RAIZ = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
LISTA = os.path.join(RAIZ, '.github', 'deploy-protegidos.txt')
GUARDADOS = os.path.join(RAIZ, 'servidor-antes-do-deploy')


def git(*args):
    return subprocess.run(['git', '-C', RAIZ] + list(args),
                          capture_output=True, text=True).stdout


def padroes():
    if not os.path.isfile(LISTA):
        sys.exit('Falta o ficheiro %s' % LISTA)

    saida = []
    for linha in open(LISTA, encoding='utf-8'):
        linha = linha.split('#')[0].strip()
        if linha:
            saida.append(linha)

    return saida


def ficheiros_protegidos():
    caminhos = set()
    for p in padroes():
        for f in git('ls-files', p).splitlines():
            if f.strip():
                caminhos.add(f.strip())

    return sorted(caminhos)


def alterados_neste_push():
    """O que este push muda — esses PODEM diferir do servidor: é a correcção."""
    antes = os.environ.get('GITHUB_EVENT_BEFORE', '')
    agora = os.environ.get('GITHUB_SHA', 'HEAD')

    # Push novo (branch acabada de criar) vem com tudo a zeros: sem base de
    # comparação, compara-se com o commit anterior.
    if not antes or set(antes) == {'0'}:
        antes = agora + '~1'

    saida = git('diff', '--name-only', antes, agora)
    if not saida.strip():
        saida = git('show', '--format=', '--name-only', agora)

    return {l.strip() for l in saida.splitlines() if l.strip()}


def main():
    forcar = '[sobrepor-servidor]' in os.environ.get('MENSAGEM_COMMIT', '')

    protegidos = ficheiros_protegidos()
    do_push = alterados_neste_push()

    print('Ficheiros protegidos a verificar: %d' % len(protegidos))
    print('Alterados por este push: %d\n' % len(do_push))

    servidor = os.environ['FTP_SERVER']
    utilizador = os.environ['FTP_USERNAME']
    senha = os.environ['FTP_PASSWORD']

    divergentes, novos, iguais = [], [], 0
    os.makedirs(GUARDADOS, exist_ok=True)

    with ftplib.FTP(servidor, timeout=120) as f:
        f.login(utilizador, senha)
        f.voidcmd('TYPE I')

        for rel in protegidos:
            local = os.path.join(RAIZ, rel)
            if not os.path.isfile(local):
                continue

            buf = io.BytesIO()
            try:
                f.retrbinary('RETR ' + rel, buf.write)
            except Exception:
                novos.append(rel)
                continue

            no_servidor = buf.getvalue()
            if hashlib.md5(no_servidor).hexdigest() == hashlib.md5(open(local, 'rb').read()).hexdigest():
                iguais += 1
                continue

            # Guardar SEMPRE o que lá está, mesmo quando se vai deixar passar.
            destino = os.path.join(GUARDADOS, rel)
            os.makedirs(os.path.dirname(destino), exist_ok=True)
            with open(destino, 'wb') as fh:
                fh.write(no_servidor)

            if rel not in do_push:
                divergentes.append(rel)

    print('iguais: %d | novos (ainda não estão no servidor): %d' % (iguais, len(novos)))

    if not divergentes:
        print('\nNada por explicar. O deploy pode seguir.')
        return 0

    print('\n' + '=' * 70)
    print('O SERVIDOR TEM %d FICHEIRO(S) QUE ESTE PUSH NÃO ALTERA' % len(divergentes))
    print('=' * 70)
    for rel in divergentes:
        print('  ' + rel)
    print("""
Estes ficheiros estão diferentes no servidor e este push não lhes toca — ou
seja, alguém arranjou alguma coisa lá directamente e nunca a commitou. Se o
deploy avançar, sobrepõe-lhes a versão do repositório e esse trabalho perde-se.

O que está no servidor foi guardado no artefacto "servidor-antes-do-deploy"
desta execução. Para resolver, uma de duas:

  1. Trazer o trabalho para o repositório (o normal):
     descarregar o artefacto, copiar os ficheiros para o repositório,
     conferir, commitar e voltar a fazer push.

  2. Sobrepor de propósito, porque a versão do repositório é que está certa:
     repetir o push com [sobrepor-servidor] na mensagem do commit.
""")

    if forcar:
        print('[sobrepor-servidor] presente na mensagem: o deploy segue à mesma.')
        return 0

    return 1


if __name__ == '__main__':
    sys.exit(main())
