<?php
// form.php - обработчик формы
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// Если это GET запрос (например, после сохранения), показываем форму с сообщением
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        $successMessage = 'Спасибо, результаты сохранены.';
    }
    include('form.html');
    exit();
}

// POST запрос - обрабатываем данные

// Валидация данных
$errors = [];
$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

// ФИО
if (empty($_POST['fio'])) {
    $errors['fio'] = 'Заполните ФИО.';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $_POST['fio'])) {
    $errors['fio'] = 'ФИО должно содержать только буквы, пробелы и дефисы.';
} elseif (mb_strlen($_POST['fio']) > 150) {
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
    $today = new DateTime();
    $minAge = new DateTime('-150 years');
    
    if (!$birthdate || $birthdate > $today || $birthdate < $minAge) {
        $errors['birthdate'] = 'Введите корректную дату рождения.';
    } elseif ($birthdate->diff($today)->y < 18) {
        $errors['birthdate'] = 'Вы должны быть старше 18 лет.';
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

// Если есть ошибки, показываем форму с ними
if (!empty($errors)) {
    include('form.html');
    exit();
}

// Подключение к базе данных
$user = 'u82361';
$pass = '9967838';
$dbname = 'u82361'; 

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Начало транзакции
    $db->beginTransaction();

    // Вставка основной информации
    $stmt = $db->prepare("INSERT INTO applications (fio, phone, email, birthdate, gender, bio, contract_agreed) 
                          VALUES (:fio, :phone, :email, :birthdate, :gender, :bio, :contract)");
    $stmt->execute([
        ':fio' => $_POST['fio'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':birthdate' => $_POST['birthdate'],
        ':gender' => $_POST['gender'],
        ':bio' => $_POST['bio'],
        ':contract' => 1
    ]);

    // Получаем ID последней вставленной записи
    $applicationId = $db->lastInsertId();

    // Вставка языков программирования (используем ID языков из справочника)
    $langStmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) 
                              VALUES (:app_id, (SELECT id FROM programming_languages WHERE name = :lang))");
    foreach ($_POST['languages'] as $lang) {
        $langStmt->execute([
            ':app_id' => $applicationId,
            ':lang' => $lang
        ]);
    }

    // Завершение транзакции
    $db->commit();

    // Перенаправление с сообщением об успехе
    header('Location: index.php?save=1');
    exit();
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $errors['db'] = 'Ошибка базы данных: ' . $e->getMessage();
    include('form.html');
    exit();
}
?>
