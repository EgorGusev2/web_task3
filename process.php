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
    if ($birthdate && $birthdate->diff($today)->y < 18) {
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

// Если есть ошибки, показываем форму снова
if (!empty($errors)) {
    include('form.html');
    exit();
}

// Подключение к базе данных (ваши данные u82361)
$user = 'u82361';
$pass = '9967838';
$dbname = 'u82361';

try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Начинаем транзакцию
    $db->beginTransaction();
    
    // Вставка основной информации
    $stmt = $db->prepare("INSERT INTO application (full_name, phone, email, birth_date, gender, biography, agreed) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['fio'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['bio'],
        1
    ]);
    
    // Получаем ID последней записи
    $applicationId = $db->lastInsertId();
    
    // Вставка языков программирования (по ID из справочника)
    $stmt = $db->prepare("INSERT INTO application_language (application_id, language_id) 
                          VALUES (?, (SELECT id FROM programming_language WHERE name = ?))");
    foreach ($_POST['languages'] as $lang) {
        $stmt->execute([$applicationId, $lang]);
    }
    
    // Подтверждаем транзакцию
    $db->commit();
    
    // Перенаправляем с сообщением об успехе
    header('Location: index.php?save=1');
    exit();
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $errors['db'] = 'Ошибка базы данных: ' . $e->getMessage();
    include('form.html');
    exit();
}
?>