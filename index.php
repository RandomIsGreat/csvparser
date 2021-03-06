<?php

if(isset($_FILES['csv'])) {
    $xml = simplexml_load_file("http://localhost/dataBaseConnect.xml");
    $dsn = "{$xml->dbtype}:dbname={$xml->dbname};host={$xml->host}";
    $username = (string)$xml->username;
    $db = new PDO($dsn, $username);

    $csv = fopen($_FILES['csv']['tmp_name'], 'r');
    $rowToParse = fgetcsv($csv, '1000', ';');

    $tableCreationSql = "CREATE TABLE IF NOT EXISTS {$xml->tablename} ( ";
    $tableCreationData = [];
    $inputString = '(';

    foreach ($rowToParse as $el) {
            $inputString .='?, ';
            $tableCreationSql .= " $el varchar(255) NULL,";
            $tableCreationData[] = $el;
    }

    $tableCreationSql = mb_substr($tableCreationSql, 0, -1);
    $tableCreationSql .= ')';
    $inputString = mb_substr($inputString, 0, -2);
    $inputString .=')';

    $sth = $db->prepare($tableCreationSql);
    $sth->execute([]);

    $additionToDataBase = $inputString;
    $tableData = [];
    $isFirst = true;

    while ($rowToParse = fgetcsv($csv, '1000', ';')) {
        foreach ($rowToParse as $el) {
            $tableData[] = $el;
        }
        if ($isFirst) {
            $isFirst = false;
        } else {
            $additionToDataBase .=', '.$inputString;
        }
    }
    fclose($csv);
    $collumnsForSql = '(';

    foreach ($tableCreationData as $el) {
        $collumnsForSql .= "$el, ";
    }

    $collumnsForSql = mb_substr($collumnsForSql, 0, -2);
    $collumnsForSql .= ')';

    $sqlForAddition = "INSERT INTO `{$xml->tablename}` $collumnsForSql
                    VALUES $additionToDataBase";

    $sth = $db->prepare($sqlForAddition);
    $sth->execute($tableData);

    $sth = $db->prepare("SELECT * FROM `{$xml->tablename}`");
    $sth->execute([]);
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
}

?>

<html>
<head>
    <meta charset="utf-8">
    <title>Обработка файла формата CSV</title>
</head>
<body>
    <form method="POST" ENCTYPE="multipart/form-data">
        <input type="file" name="csv">
        <button>Upload</button>
    </form>
    <?php if(isset($_FILES['csv'])): ?>
        <table border="2px">
            <tr>
                <?php foreach ($tableCreationData as $el): ?>
                    <td><?= $el ?></td>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($result as $value): ?>
                <tr>
                    <?php foreach ($value as $el): ?>
                        <td><?= $el ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif;?>
</body>
</html>
