<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * As unidades do Aura Residence: fracção -> tipologia, piso e áreas.
 *
 * O contrato e a declaração de cedência precisam de dizer a tipologia e os
 * metros da fracção, e a venda não os guarda -- só guarda a letra da fracção
 * e o valor. Andavam por isso a ser escritos à mão, ou saíam em branco.
 *
 * Esta é a mesma lista que o simulador usa em dpsimobiliario.pt (AURA_UNITS),
 * copiada para aqui a 01/08/2026. Fica no CRM de propósito: gerar um contrato não
 * pode depender de o simulador estar de pé nesse momento.
 *
 * Se as áreas ou tipologias mudarem no projecto, mudam lá e têm de mudar aqui.
 */
return [
    'A'    => ['tipologia' => 'T1',  'piso' => 1, 'abc' => 52.7, 'varanda' => 8.4, 'total' => 63.8, 'orientacao' => 'Trás - Centro'],
    'B'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 73.1, 'varanda' => 14.7, 'total' => 87.8, 'orientacao' => 'Trás - centro - Direito'],
    'C'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 79.0, 'varanda' => 27.2, 'total' => 106.2, 'orientacao' => 'Trás - Direito'],
    'D'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 79.6, 'varanda' => 33.9, 'total' => 113.5, 'orientacao' => 'Frente - Direito'],
    'E'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 79.6, 'varanda' => 20.0, 'total' => 99.6, 'orientacao' => 'Frente - Centro - Direito'],
    'F'    => ['tipologia' => 'T1',  'piso' => 1, 'abc' => 55.3, 'varanda' => 16.8, 'total' => 82.1, 'orientacao' => 'Frente - Centro'],
    'G'    => ['tipologia' => 'T1+1', 'piso' => 1, 'abc' => 77.2, 'varanda' => 12.9, 'total' => 90.1, 'orientacao' => 'Frente - Centro - Esquerdo'],
    'H'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 71.6, 'varanda' => 34.6, 'total' => 106.2, 'orientacao' => 'Frente - Esquerdo'],
    'I'    => ['tipologia' => 'T1',  'piso' => 1, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'J'    => ['tipologia' => 'T1',  'piso' => 1, 'abc' => 52.0, 'varanda' => 10.4, 'total' => 62.4, 'orientacao' => 'Trás - Esquerdo'],
    'L'    => ['tipologia' => 'T1',  'piso' => 1, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'M'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 72.0, 'varanda' => 27.8, 'total' => 99.8, 'orientacao' => 'Trás - Esquerdo'],
    'N'    => ['tipologia' => 'T2',  'piso' => 1, 'abc' => 72.0, 'varanda' => 26.5, 'total' => 97.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'O'    => ['tipologia' => 'T0',  'piso' => 1, 'abc' => 39.9, 'varanda' => 8.8, 'total' => 47.2, 'orientacao' => 'Trás - Centro -Esquerdo'],
    'P'    => ['tipologia' => 'T1',  'piso' => 2, 'abc' => 52.7, 'varanda' => 8.4, 'total' => 63.8, 'orientacao' => 'Trás - Centro'],
    'Q'    => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 73.1, 'varanda' => 14.7, 'total' => 87.8, 'orientacao' => 'Trás - centro - Direito'],
    'R'    => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 79.0, 'varanda' => 27.2, 'total' => 106.2, 'orientacao' => 'Trás - Direito'],
    'S'    => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 79.6, 'varanda' => 33.9, 'total' => 113.5, 'orientacao' => 'Frente - Direito'],
    'T'    => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 79.6, 'varanda' => 20.0, 'total' => 99.6, 'orientacao' => 'Frente - Centro - Direito'],
    'U'    => ['tipologia' => 'T1',  'piso' => 2, 'abc' => 55.3, 'varanda' => 16.8, 'total' => 82.1, 'orientacao' => 'Frente - Centro'],
    'V'    => ['tipologia' => 'T1+1', 'piso' => 2, 'abc' => 77.2, 'varanda' => 12.9, 'total' => 90.1, 'orientacao' => 'Frente - Centro - Esquerdo'],
    'X'    => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 71.6, 'varanda' => 34.6, 'total' => 106.2, 'orientacao' => 'Frente - Esquerdo'],
    'Z'    => ['tipologia' => 'T1',  'piso' => 2, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'AA'   => ['tipologia' => 'T1',  'piso' => 2, 'abc' => 52.0, 'varanda' => 10.4, 'total' => 62.4, 'orientacao' => 'Trás - Esquerdo'],
    'AB'   => ['tipologia' => 'T1',  'piso' => 2, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'AC'   => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 72.0, 'varanda' => 27.8, 'total' => 99.8, 'orientacao' => 'Trás - Esquerdo'],
    'AD'   => ['tipologia' => 'T2',  'piso' => 2, 'abc' => 72.0, 'varanda' => 25.2, 'total' => 97.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'AE'   => ['tipologia' => 'T0',  'piso' => 2, 'abc' => 39.9, 'varanda' => 8.8, 'total' => 47.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'AF'   => ['tipologia' => 'T1',  'piso' => 3, 'abc' => 52.7, 'varanda' => 8.4, 'total' => 63.8, 'orientacao' => 'Trás - Centro'],
    'AG'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 73.1, 'varanda' => 14.7, 'total' => 87.8, 'orientacao' => 'Trás - centro - Direito'],
    'AH'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 79.0, 'varanda' => 27.2, 'total' => 106.2, 'orientacao' => 'Trás - Direito'],
    'AI'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 79.6, 'varanda' => 33.9, 'total' => 113.5, 'orientacao' => 'Frente - Direito'],
    'AJ'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 79.6, 'varanda' => 20.0, 'total' => 99.6, 'orientacao' => 'Frente - Centro - Direito'],
    'AL'   => ['tipologia' => 'T1',  'piso' => 3, 'abc' => 55.3, 'varanda' => 16.8, 'total' => 82.1, 'orientacao' => 'Frente - Centro'],
    'AM'   => ['tipologia' => 'T1+1', 'piso' => 3, 'abc' => 77.2, 'varanda' => 12.9, 'total' => 90.1, 'orientacao' => 'Frente - Centro - Esquerdo'],
    'AN'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 71.6, 'varanda' => 34.6, 'total' => 106.2, 'orientacao' => 'Frente - Esquerdo'],
    'AO'   => ['tipologia' => 'T1',  'piso' => 3, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'AP'   => ['tipologia' => 'T1',  'piso' => 3, 'abc' => 52.0, 'varanda' => 10.4, 'total' => 62.4, 'orientacao' => 'Trás - Esquerdo'],
    'AQ'   => ['tipologia' => 'T1',  'piso' => 3, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'AR'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 72.0, 'varanda' => 27.8, 'total' => 99.8, 'orientacao' => 'Trás - Esquerdo'],
    'AS'   => ['tipologia' => 'T2',  'piso' => 3, 'abc' => 72.0, 'varanda' => 25.2, 'total' => 97.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'AT'   => ['tipologia' => 'T0',  'piso' => 3, 'abc' => 39.9, 'varanda' => 8.8, 'total' => 47.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'AU'   => ['tipologia' => 'T1',  'piso' => 4, 'abc' => 52.7, 'varanda' => 8.4, 'total' => 63.8, 'orientacao' => 'Trás - Centro'],
    'AV'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 73.1, 'varanda' => 14.7, 'total' => 87.8, 'orientacao' => 'Trás - centro - Direito'],
    'AX'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 79.0, 'varanda' => 27.2, 'total' => 106.2, 'orientacao' => 'Trás - Direito'],
    'AZ'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 79.6, 'varanda' => 33.9, 'total' => 113.5, 'orientacao' => 'Frente - Direito'],
    'BA'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 79.6, 'varanda' => 20.0, 'total' => 99.6, 'orientacao' => 'Frente - Centro - Direito'],
    'BB'   => ['tipologia' => 'T1',  'piso' => 4, 'abc' => 55.3, 'varanda' => 16.8, 'total' => 82.1, 'orientacao' => 'Frente - Centro'],
    'BC'   => ['tipologia' => 'T1+1', 'piso' => 4, 'abc' => 77.2, 'varanda' => 12.9, 'total' => 90.1, 'orientacao' => 'Frente - Centro - Esquerdo'],
    'BD'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 71.6, 'varanda' => 34.6, 'total' => 106.2, 'orientacao' => 'Frente - Esquerdo'],
    'BE'   => ['tipologia' => 'T1',  'piso' => 4, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'BF'   => ['tipologia' => 'T1',  'piso' => 4, 'abc' => 52.0, 'varanda' => 10.4, 'total' => 62.4, 'orientacao' => 'Trás - Esquerdo'],
    'BG'   => ['tipologia' => 'T1',  'piso' => 4, 'abc' => 52.0, 'varanda' => 10.3, 'total' => 62.3, 'orientacao' => 'Trás - Esquerdo'],
    'BH'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 72.0, 'varanda' => 27.8, 'total' => 99.8, 'orientacao' => 'Trás - Esquerdo'],
    'BI'   => ['tipologia' => 'T2',  'piso' => 4, 'abc' => 72.0, 'varanda' => 25.2, 'total' => 97.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'BJ'   => ['tipologia' => 'T0',  'piso' => 4, 'abc' => 39.9, 'varanda' => 8.8, 'total' => 47.2, 'orientacao' => 'Trás - Centro - Esquerdo'],
    'BL'   => ['tipologia' => 'T3',  'piso' => 5, 'abc' => 100.6, 'varanda' => 152.8, 'total' => 253.4, 'orientacao' => 'Frente - Direito'],
    'BM'   => ['tipologia' => 'T2',  'piso' => 5, 'abc' => 76.5, 'varanda' => 34.2, 'total' => 110.7, 'orientacao' => 'Frente - Centro'],
    'BN'   => ['tipologia' => 'T3',  'piso' => 5, 'abc' => 105.2, 'varanda' => 79.0, 'total' => 184.2, 'orientacao' => 'Frente - Esquerdo'],
    'BO'   => ['tipologia' => 'T1',  'piso' => 5, 'abc' => 56.7, 'varanda' => 87.6, 'total' => 144.3, 'orientacao' => 'Trás'],
    'BP'   => ['tipologia' => 'Loja', 'piso' => 0, 'abc' => 1907.3, 'varanda' => 0, 'total' => 1907.3, 'orientacao' => 'Loja / Estacionamento'],
];
