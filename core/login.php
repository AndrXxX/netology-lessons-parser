<?php

if (!getCurrentUser() &&
    !(isPost() && checkForLogin(getParam('login'), getParam('password'), getParam('captcha')))
) {
// выводим форму авторизации если пользователь незалогинен
?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
    <link rel="stylesheet" href="./resources/css/login.css">
    <title>Авторизация</title>
  </head>
    <body>
      <section>
        <div class="content">
          <div class="card">
            <div class="firstinfo">
              <div class="profileinfo">
                <h1>Авторизация</h1>

                <?php if (!empty(getLoginErrors())): ?>
                  <div class="content">
                <?php foreach (getLoginErrors() as $error): ?>
                  <p><?= $error ?></p>
                <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <form method="POST" id="login-form">
                  <div class="form-group">
                    <label for="lg">Логин</label>
                    <input type="text" name="login" id="lg" class="form-control">
                  </div>
                  <div class="form-group">
                    <label for="key">Пароль</label>
                    <input type="password" name="password" id="key" class="form-control">
                  </div>

                    <?php if (isNeedCaptcha()): ?>
                      <figure><img src="core/captcha.php" alt="" id="captcha"/></figure>
                      <span class="captcha-upd"
                            onclick="document.getElementById('captcha').src = 'core/captcha.php?' + Math.random()">Обновить код</span>
                      <div class="form-group">
                        <label for="captcha">Введите код с картинки:</label>
                        <input type="text" name="captcha" id="captcha" class="form-control">
                      </div>
                    <?php endif; ?>

                  <input type="submit" id="btn-login" class="btn btn-prime" value="Войти">
                </form>
              </div>
            </div>
          </div>
              <div class="badgescard"><p>Для продолжения необходимо войти. Используйте вашу учетную запись от <a
                    href="https://netology.ru/">Нетологии</a>.</p></div>
            </div>
      </section>
    </body>
</html>

<?php
}

?>