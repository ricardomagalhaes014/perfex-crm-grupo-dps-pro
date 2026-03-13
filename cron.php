<?php

// Exibir erros apenas para depuração (remova em produção)
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    // Configuração da conexão PDO
    $conn = new PDO("mysql:host=localhost;dbname=dl5naiz2_crmdental;charset=utf8", "dl5naiz2_crmdental", "oHMDM%s5cdm4", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Exibir erros do PDO
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Melhor retorno de dados
        PDO::ATTR_EMULATE_PREPARES => false, // Melhor proteção contra SQL Injection
    ]);

    // Iniciar transação para melhor performance
    $conn->beginTransaction();

    // Deletar valores antigos para evitar duplicação
    $sql = "DELETE FROM tblcustomfieldsvalues;";
    $conn->exec($sql);

    // Inserir novos valores na tabela tblcustomfieldsvalues
    $sql = "INSERT INTO tblcustomfieldsvalues (relid, fieldid, fieldto, value)
            SELECT userid, 1, 'customers', vat FROM tblclients
            UNION ALL
            SELECT userid, 2, 'customers', zip FROM tblclients
            UNION ALL
            SELECT userid, 3, 'customers', city FROM tblclients
            UNION ALL
            SELECT userid, 4, 'customers', address FROM tblclients
            UNION ALL
            SELECT tblproposals.rel_id, 5, 'customers',
                CASE 
                    WHEN status=1 THEN 'Aberta'
                    WHEN status=2 THEN 'Recusada'
                    WHEN status=3 THEN 'Aceite'
                    WHEN status=4 THEN 'Enviada'
                    WHEN status=5 THEN 'Rever'
                    WHEN status=6 THEN 'Rascunho'
                END
            FROM tblproposals
            INNER JOIN (
                SELECT MAX(id) AS id, rel_id
                FROM tblproposals
                WHERE rel_type='customer'
                GROUP BY rel_id
            ) AS Maximos
            ON tblproposals.id=Maximos.id AND tblproposals.rel_id=Maximos.rel_id
            WHERE tblproposals.rel_type='customer';";

    $conn->exec($sql);

    // Confirmar transação
    $conn->commit();

    echo "Cron job executado com sucesso!\n";

} catch (PDOException $e) {
    // Se der erro, cancelar transação e exibir mensagem
    $conn->rollBack();
    echo "Erro no cron job: " . $e->getMessage() . "\n";
}

?>