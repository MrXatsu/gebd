<?php
 
try {
    $db = new PDO('mysql:host=localhost;dbname=plant_db', 'root', '');
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    die;
}
//Affichage de toute la liste
function fetchAll($connection, $table) {
    $query = $connection->prepare("SELECT * FROM ".$table);
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

//Affichage d'un élément en particulier
function fetchById($connection, $table, $id) {
    $query = $connection->prepare("SELECT * FROM ".$table." WHERE id = :id");
    $query->bindParam(':id', $id);
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC);
}

//
function getColumns($connection, $table) {
    $sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = '".$table."'";
    $query = $connection->prepare($sql);
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function save($connection, $table, $data) {
    $columns = array_keys($data);
    $columnsStr = implode(', ', $columns); // split ; join
    $placeholdersStr = ':' . implode(', :', $columns); // split ; join
    $sql = 'INSERT INTO ' . $table . ' (' . $columnsStr . ') VALUES (' . $placeholdersStr . ');';
    try {
        $query = $connection->prepare($sql);
        foreach ($data as $key => $value) {
            $query->bindValue(':'.$key, $value);
            echo 'value => ' . $value . '<br>';
        }
        $query->execute();
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
        die;
    }
}

function edit($connection, $table, $data, $id) {
    $columns = array_keys($data);
    $columnsStr = implode(', ', $columns); // split ; join
    $placeholdersStr = ':' . implode(', :', $columns); // split ; join
    $sql = 'UPDATE ' . $table . ' SET ';
    foreach ($columns as $col) {
        $sql .= $col . ' = :' . $col . ', ';
 
    }

    $sql = rtrim($sql, ', '); // remove last comma
    $sql .= ' WHERE id = :id';


    try {
        $query = $connection->prepare($sql);
        foreach ($data as $key => $value) {
            $query->bindValue(':'.$key, $value !== '' ? $value : null);
            echo 'value => ' . $value . '<br>';
        }
        $query->bindValue(':id', $id);
        $query->execute();
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
        die;
    }
}

function deleteById($connection, $table, $id) {
    $query = $connection->prepare('DELETE FROM ' . $table . ' WHERE id=:id;');
    $query->bindParam(':id', $id);
    $query->execute();
    return;
}

