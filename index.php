<?php
session_start();
require_once 'core/functions.php';
$currentUser = getCurrentUser();

if (!$currentUser) {
    require_once 'core/login.php';
    die;
}

if (!empty($_GET['logout'])) {
    require_once 'core/logout.php';
    die;
}

$courses = [];
$jsonPath = 'resources/courses.json';
if (file_exists($jsonPath)) {
  $decoded = json_decode(file_get_contents($jsonPath), true);
  if (is_array($decoded)) {
      $courses = $decoded;
  }
}

?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <title>Парсер списка лекций</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="resources/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/devicons/1.8.0/css/devicons.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
          <img src="<?= $_SESSION['pic'] ?>" class="avatar">
          <p class="greet">
            <?= $currentUser ?>
          </p>
          <a class="logout" href="?logout=true">Выход</a>
        </div>

    </header>
    <div class="container">
        <h1>Парсер списка лекций</h1>
        <div class="options hidden">
          <?php if (count($courses) > 0) { ?>
            <table>
            <tr>
              <th>Код курса</th>
              <th>Название курса</th>
              <th>№ группы для начала парсинга</th>
              <th>Количество групп для парсинга</th>
            </tr>

              <?php
              foreach ($courses as $course) {
                  $format = '
                      <tr class="course" data-name="%1$s" data-fullname="%2$s" data-start-num="%3$s" data-groups-num="%4$s">
                        <td>%1$s</td>
                        <td>%2$s</td>
                        <td><input class="start-num" type="text" value="%3$s"></td>
                        <td><input class="groups-num" type="text" value="%4$s"></td>
                      </tr>';
                  echo sprintf($format, $course['name'], $course['fullName'], $course['startNumber'], $course['groupsNumber']);
              } ?>
            </table>
          <?php  } ?>
          <div class="buttons">
            <input type="button" class="btn btn-save" value="Сохранить">
            <input type="button" class="btn btn-back" value="Вернуться">
          </div>
        </div>

      <div class="main">
        <div class="buttons">
          <input type="button" class="btn btn-options" value="Настройки">
          <input type="button" class="btn btn-start" value="Начать парсинг">
          <input type="button" class="btn btn-show-errors hidden" value="Показать расширенный лог">
        </div>
        <div class="progress-container hidden">
          <div class="progress"></div>
        </div>
        <div class="output">
          <div class="info hidden">
            <p>Расписание на ближайшие 7 дней:</p>
            <div class="info-output"></div>
          </div>

            <div class="success hidden"></div>
            <div class="errors hidden"></div>
        </div>

      </div>

    </div>
    <footer class="footer">
      <span class="ver"></span><span class="copyright">, © <a href="https://github.com/AndrXxX">AndrXxX</a>, 2018</span>
    </footer>
  <script src="resources/js/core.js"></script>
</body>
</html>