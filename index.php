<?php
$head = '<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Les Plantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  </head>
  <body>';
echo $head;
$footer = '
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
  </body>
</html>';
require_once('db.php');
define('BASE_PATH', '/gebd-1/index.php/' );

$modelsArray = [
  'plantes' => 'plants',
];

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$requestUri = str_replace(BASE_PATH, '', $requestUri);

$requestUriArray = explode('/', $requestUri);
$requestUriArray = array_filter($requestUriArray, function($value) {
    return $value !== '';
});

if (empty($requestUriArray)) {
      echo 'Homepage';
      die;
  }

  $model = $requestUriArray[0];

  //-----------------------------------Affichage de la page d'accueil.---------------------------------
  if (count($requestUriArray) === 1) {
    // Vue "liste"
    echo 'Model: ' . $model;
    echo '<a href="'. BASE_PATH . $model .'/add">Add new</a>';
    $tableName  = $modelsArray[$model] ?? false;
    if (!$tableName) {
        echo 'Invalid model';
        die;
    }
    $results = fetchAll($db, $tableName);
    $firstRow = $results[0];
    $columns = array_keys($firstRow);

    $str = '<table class="table">';
    $str .= '<thead>';

    // Boucle pour les colonnes
    foreach ($columns as $col) {
        $str .= '<th>' . $col . '</th>';
    }

    $str .= '</thead>';
    $str .= '<tbody>';
    //Conversion du tinyint 0-1 par No-Yes
    $columnsMeta = getColumns($db, $tableName);
    $typesMap = [];
    foreach ($columnsMeta as $colMeta) {
        $typesMap[$colMeta['COLUMN_NAME']] = $colMeta['DATA_TYPE'];
    }
    // Boucle pour les lignes
    foreach($results as $row) {
        $str .= '<tr>';
        foreach ($row as $col => $value) {
          if (isset($typesMap[$col]) && $typesMap[$col] === 'tinyint') {
            $value = $value == 1 ? 'Oui' : 'Non';
        }
            $str .= '<td>' . $value . '</td>';
        }
        $str .= '<td>';
        $str .= '<a href="'. BASE_PATH . $model . '/' . $row['id'] .'">View</a>';
        $str .= '</td>';
        $str .= '<td>';
        $str .= '<a href="'. BASE_PATH . $model . '/' . $row['id'] .'/edit">Edit</a>';
        $str .= '</td>';
        $str .= '<td>';
        $str .= '<a href="'. BASE_PATH . $model . '/' . $row['id'] .'/delete">Delete</a>';
        $str .= '</td>';       
        $str .= '</tr>';
    }

    $str .= '</tbody>';
    $str .= '</table>';

    echo $str;
    echo $footer;
}

//-------------------------------------Ajout d'un nouvel élément----------------------------------------
if (count($requestUriArray) === 2) {
  $isAdd = $requestUriArray[1] === 'add';
  if ($isAdd && $requestMethod === 'GET') {
      // Vue "ajout"
      echo 'Add';

      $tableName  = $modelsArray[$model] ?? false;
      if (!$tableName) {
          echo 'Invalid model';
          die;
      }

      $columns = getColumns($db, $tableName);

      $str = '<form method="POST" action="">';
      // Boucle pour les colonnes
      foreach ($columns as $col) {
        $columnName = $col['COLUMN_NAME'];
        $columnType = $col['DATA_TYPE'];
        $isNullable = $col['IS_NULLABLE'];
    
        if ($columnName === 'id') {
            continue;
        }
    
        $inputStr = '';
    /*condition pour le tinyint oui = 1, non = 0
       Mise en forme du formulaire*/
        if ($columnType === 'tinyint') {
            $inputStr .= '
            <label>' . $columnName . ' :</label><br>
            <input type="radio" id="' . $columnName . '_yes" name="' . $columnName . '" value="1" checked>
            <label for="' . $columnName . '_yes">Yes</label>
    
            <input type="radio" id="' . $columnName . '_no" name="' . $columnName . '" value="0">
            <label for="' . $columnName . '_no">No</label>
            ';
        } 
        else {
            $inputStr .= '<input name="' . $columnName . '" placeholder="' . $columnName . '" ';
    
            if ($columnType === 'int' || $columnType === 'double') {
                $inputStr .= 'type="number" ';
            }
    
            if ($columnType === 'date') {
                $inputStr .= 'type="date" ';
            }
    
            $inputStr .= '/>';
        }
    
        $str .= $inputStr . '<br>';
    }
    

      $str .= '<input type="submit" value="Save" />';

      $str .= '</form>';
      echo $str;
      die;
  }//Fin du formulaire


  if ($isAdd && $requestMethod === 'POST') {
      // Enregistrer le nouveau modèle
      $tableName  = $modelsArray[$model] ?? false;
      if (!$tableName) {
          echo 'Invalid model';
          die;
      }
      save($db, $tableName, $_POST);
      //retour à la page principale
      header('Location: '. BASE_PATH . $model);
  }
//---------------------------------------Vue détaillée d'un élément--------------------------------------
  if (ctype_digit($requestUriArray[1])) {
      // Vue "détail"
      echo 'Detail<br>';
      echo '<a href='. BASE_PATH . $model . '>Back to '. $model .'</a><br>';
      echo '<a href='. BASE_PATH . $model . '/'.$requestUriArray[1].'/edit>Edit</a><br>';
      $tableName  = $modelsArray[$model] ?? false;
      if (!$tableName) {
          echo 'Invalid model';
          die;
      }
      $id = $requestUriArray[1];
      $result = fetchById($db, $tableName, $id);

      //Conversion du tinyint 0-1 par No-Yes
      $columnsMeta = getColumns($db, $tableName);
      $typesMap = [];
      foreach ($columnsMeta as $colMeta) {
        $typesMap[$colMeta['COLUMN_NAME']] = $colMeta['DATA_TYPE'];
      }

      foreach ($result as $col => $value) {
        if (isset($typesMap[$col]) && $typesMap[$col] === 'tinyint') {
          $value = $value == 1 ? 'Oui' : 'Non';
        }
        echo $col . ': ' . $value . '<br>';
      }
      die;
  }

  echo 'Invalid id or action';
  die;
}

//-------------------------------------------Modification et suppression d'un élément-------------------
if (count($requestUriArray) === 3) {
  // Vérifier que l'id est numérique
  $id = $requestUriArray[1];
  $isValidId = ctype_digit($requestUriArray[1]); // Vérifier que le 2e element est numérique

  if (!$isValidId) {
      echo 'Invalid ID';
      die;
  }

  // Vérifier que le 3e element est "edit" ou "delete"
  if ($requestUriArray[2] !== 'edit' && $requestUriArray[2] !== 'delete') {
      echo 'Invalid action';
      die;
  }
//-----------------------------------Suppression d'un élément--------------------------------------------
  if ($requestUriArray[2] === 'delete') {
      // Comportement de suppression

      // 1 - vérifier la validité du modèle
      $tableName  = $modelsArray[$model] ?? false;
      if (!$tableName) {
          echo 'Invalid model';
          die;
      }
      // 2 - vérifier qu'il y a une ligne avec l'id spécifié
      $result = fetchById($db, $tableName, $id);

      if (!$result) {
          echo 'Invalid ID';
          die;
      }
      // 3 - effectuer la suppression
      deleteById($db, $tableName, $id);

      // 4 - rediriger vers la vue liste
      header('Location: ' . BASE_PATH . $model . '/');
  }
//-----------------------------------------Modification d'un élément------------------------------------
  echo 'Edit';

  if ($requestMethod === 'GET') {
    // Afficher le formulaire
    $tableName  = $modelsArray[$model] ?? false;
    if (!$tableName) {
        echo 'Invalid model';
        die;
    }

    $result = fetchById($db, $tableName, $id);
    if (!$result) {
        echo 'Invalid ID';
        die;
    }

    $columns = getColumns($db, $tableName);

    $str = '<form method="POST" action="">';

    foreach ($columns as $col) {
        $columnName = $col['COLUMN_NAME'];
        $columnType = $col['DATA_TYPE'];
        $isNullable = $col['IS_NULLABLE'];

        if ($columnName === 'id') {
            continue;
        }

        $inputStr = '';
        $value = htmlspecialchars($result[$columnName]);

        // Pré-remplissage du formulaire pour modification.
        if ($columnType === 'tinyint') {
            $checkedYes = $value == 1 ? 'checked' : '';
            $checkedNo = $value == 0 ? 'checked' : '';

            $inputStr .= '
            <label>' . $columnName . ' :</label><br>
            <input type="radio" id="' . $columnName . '_yes" name="' . $columnName . '" value="1" ' . $checkedYes . '>
            <label for="' . $columnName . '_yes">Yes</label>

            <input type="radio" id="' . $columnName . '_no" name="' . $columnName . '" value="0" ' . $checkedNo . '>
            <label for="' . $columnName . '_no">No</label>
            ';
        } 
        else {
            $inputStr .= '<input name="' . $columnName . '" placeholder="' . $columnName . '" ';

            if ($columnType === 'int' || $columnType === 'double') {
                $inputStr .= 'type="number" ';
            }

            if ($columnType === 'date') {
                $inputStr .= 'type="date" ';
            }

            $inputStr .= 'value="' . $value . '" />';
        }

        $str .= $inputStr . '<br>';
    }

    $str .= '<input type="submit" value="Save" />';
    $str .= '</form>';

    echo $str;
    die;
}


if ($requestMethod === 'POST') {
  // Enregistrer les modifications
  $tableName  = $modelsArray[$model] ?? false;
  if (!$tableName) {
      echo 'Invalid model';
      die;
  }
  edit($db, $tableName, $_POST, $id);

  header('Location: ' . BASE_PATH . $model . '/' . $id);
}
}