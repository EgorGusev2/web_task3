<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// Проверяем, что форма отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Валидация данных
$errors = [];
$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

// ФИО
if (empty($_POST['fio'])) {
    $errors['fio'] = 'Заполните ФИО.';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $_POST['fio'])) {
    $errors['fio'] = 'ФИО должно содержать только буквы, пробелы и дефисы.';
} elseif (strlen($_POST['fio']) > 150) {
    $errors['fio'] = 'ФИО должно быть не длиннее 150 символов.';
}

// Телефон
if (empty($_POST['phone'])) {
    $errors['phone'] = 'Заполните телефон.';
} elseif (!preg_match('/^[\d\s\+\(\)\-]{10,20}$/', $_POST['phone'])) {
    $errors['phone'] = 'Телефон должен содержать от 10 до 20 символов (цифры, +, -, пробелы, скобки).';
}

// Email
if (empty($_POST['email'])) {
    $errors['email'] = 'Заполните email.';
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email.';
}

// Дата рождения
if (empty($_POST['birthdate'])) {
    $errors['birthdate'] = 'Заполните дату рождения.';
} else {
    $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
    if (!$birthdate) {
        $errors['birthdate'] = 'Неверный формат даты. Используйте ГГГГ-ММ-ДД (например, 2001-02-06).';
    } else {
        $today = new DateTime();
        $age = $birthdate->diff($today)->y;
        if ($age < 18) {
            $errors['birthdate'] = 'Вы должны быть старше 18 лет.';
        }
        if ($age > 150) {
            $errors['birthdate'] = 'Некорректная дата рождения.';
        }
    }
}

// Пол
if (empty($_POST['gender'])) {
    $errors['gender'] = 'Укажите пол.';
} elseif (!in_array($_POST['gender'], ['male', 'female'])) {
    $errors['gender'] = 'Выбран недопустимый пол.';
}

// Языки программирования
if (empty($_POST['languages'])) {
    $errors['languages'] = 'Выберите хотя бы один язык программирования.';
} else {
    foreach ($_POST['languages'] as $lang) {
        if (!in_array($lang, $allowedLanguages)) {
            $errors['languages'] = 'Выбран недопустимый язык программирования.';
            break;
        }
    }
}

// Биография
if (empty($_POST['bio'])) {
    $errors['bio'] = 'Заполните биографию.';
} elseif (strlen($_POST['bio']) > 5000) {
    $errors['bio'] = 'Биография должна быть не длиннее 5000 символов.';
}

// Контракт
if (empty($_POST['contract'])) {
    $errors['contract'] = 'Необходимо ознакомиться с контрактом.';
}

// Если есть ошибки, показываем форму снова
if (!empty($errors)) {
    include('form.html');
    exit();
}

// Подключение к базе данных
$user = 'u82361';
$pass = '9967838';
$dbname = 'u82361';

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errors['db'] = 'Ошибка базы данных: ' . $e->getMessage();
    include('form.html');
    exit();
}
?>
