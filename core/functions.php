<?php

/**
 * Реализует механизм проверок при авторизации
 * @param $login
 * @param $password
 * @param null $captcha
 * @return bool
 */
function checkForLogin($login, $password, $captcha = null)
{
    $_SESSION['loginErrors'] = [];
    if (userIsBlocked()) {
        $_SESSION['loginErrors'][] = 'Превышено количество попытох ввода! Возможность входа заблокирована на 1 час.';
        return false;
    }
    if (isset($captcha) && !isValidCaptcha($captcha)) {
        $_SESSION['loginErrors'][] = 'Капча введена неправильно';
        increaseLoginAttempts();
        return false;
    }

    $result = login($login, $password);

    if (!$result) {
        $_SESSION['loginErrors'][] = 'Неправильный логин или пароль';
        increaseLoginAttempts();
        return false;
    }
    
    redirect('index');
    return true;
}

/**
 * Реализует механизм авторизации
 * @param $login
 * @param $password
 * @return bool
 */
function login($login, $password)
{
    if (!empty($login) && !empty($password)) {
        $post = [
            'login' => $login,
            'password' => $password,
            'remember' => '1',
            'go_logining' => 'Войти'
        ];

        $result = request_func('https://netology.ru/login/', $post);

        /*Работает у экспертов*/
        preg_match('/<span class=\"user_title\">([^<\/span>]*)/', $result, $userName);
        preg_match('/<img src=\"([^"]*)/', $result, $userPic);
        $userName = $userName[1];
        $userPic = (!empty($userPic[1])) ? "https://netology.ru$userPic[1]" : null;

        if (empty($userName)) {
            $result = request_func('https://netology.ru/loginform/', $post);

            /*Работает у обычных студентов*/
            preg_match('/(window.app_options =)(.+)(;window.server_time)/', $result, $studentInfo);
            $studentDecodedInfo = json_decode($studentInfo[2], true) or null;
            $userName = $studentDecodedInfo['user']['full_name'] or null;
            $userPic = $studentDecodedInfo['user']['medium_avatar_url'] or null;
        }

        if (!empty(getCookieFromSession()) && !(empty($userName)) && !empty($userPic)) {
            $_SESSION['user'] = $userName;
            $_SESSION['pic'] = $userPic;
            return true;
        }
    }
    return false;
}

/**
 * Отправляет запросы на сервер Нетологии
 * @param $url
 * @param null $postdata
 * @return mixed
 */
function request_func($url, $postdata = null){
    $cookie = getCookieFromSession();
    $cookieFileName = generateRandomFileName();
    if (!empty($cookie)) {
         file_put_contents($cookieFileName, $cookie);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0');

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFileName);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFileName);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if( $postdata ){
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postdata );
    }

    $html = curl_exec($ch);
    curl_close( $ch );

    saveCookieToSession(file_get_contents($cookieFileName));
    unlink($cookieFileName);
    return $html;
}

function generateRandomFileName() {
    $name = '';

    while (empty($name) or file_exists("resources/tmp/$name")) {
        $name = 'file_' . rand(0, 100000);
    };

    return "resources/tmp/$name";
}

/*
 * Извлекает куки из сессии
 */
function getCookieFromSession() {
    return !empty($_SESSION['cookie']) ? $_SESSION['cookie'] : '';
}

/*
 * Сохраняет куки в сессию
 */
function saveCookieToSession($cookie) {
    $_SESSION['cookie'] = $cookie;
}

/**
 * Возвращает список ошибок, произошедших во время входа
 * @return mixed
 */
function getLoginErrors()
{
    return !empty($_SESSION['loginErrors']) ? $_SESSION['loginErrors'] : null;
}

/**
 * Проверяет заблокирован ли пользователь
 * @return bool
 */
function userIsBlocked()
{
    if (!empty($_SESSION['timeBlock'])) {
        /* если время блокировки установлено */
        if ($_SESSION['timeBlock'] - time() <= 3600) {
            /* если час не прошел */
            return true;
        } else {
            /* если час прошел */
            $_SESSION['timeBlock'] = null;
            return false;
        }
    } else {
        /* если время блокировки не установлено */
        if (getLoginAttempts(true) >= 5) {
            /* если превышено допустимое количество входов */
            $_SESSION['timeBlock'] = time();
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Проверяет нужно ли вводить капчу
 * @return bool
 */
function isNeedCaptcha()
{
    return getLoginAttempts() >= 6;
}

/**
 * Проверяет, правильно ли введена капча
 * @param $captcha
 * @return bool
 */
function isValidCaptcha($captcha)
{
    return $_SESSION['captcha'] === $captcha;
}

/**
 * функция возвращает количество попыток входа без капчи или с капчей (если $withCaptcha === true)
 * @param bool $withCaptcha
 * @return int
 */
function getLoginAttempts($withCaptcha = false)
{
    if ($withCaptcha) {
        return isset($_SESSION['loginCaptchaAttempts']) ? $_SESSION['loginCaptchaAttempts'] : 0;
    } else {
        return isset($_SESSION['loginAttempts']) ? $_SESSION['loginAttempts'] : 0;
    }
}

/**
 * функция устанавливает количество попыток входа без капчи или с капчей (если $withCaptcha === true)
 * @return bool
 */
function increaseLoginAttempts()
{
    if (isNeedCaptcha()) {
        $_SESSION['loginCaptchaAttempts'] = getLoginAttempts(true) + 1;
        return true;
    } else {
        $_SESSION['loginAttempts'] = getLoginAttempts() + 1;
        return true;
    }
}

/**
 * Проверяет, является ли метод ответа POST
 * @return bool
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

/**
 * Проверяет установлен ли параметр $name в запросе
 * @param $name
 * @return null
 */
function getParam($name)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}

/**
 * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
 * @param null $param
 * @return null
 */
function getCurrentUser($param = null)
{
    if (isset($param)) {
        return isset($_SESSION['user']) && isset($_SESSION['user'][$param]) ? $_SESSION['user'][$param] : null;
    }
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Отправляет переадресацию на указанную страницу
 * @param $action
 */
function redirect($action)
{
    header('Location: ' . $action . '.php');
    die;
}

/**
 * Уничтожает сессию и переадресует на страницу входа
 */
function logout()
{
    session_destroy();
    redirect('index');
}

/**
 * Возвращает ссылку на курс
 * @param $course
 * @param $group
 * @return string
 */
function getLink($course, $group) {
    return "https://netology.ru/rest/1/profile/program/$course-$group/main_course/";
}