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
} elseif (strlen($_POST['email']) > 100) {
    $errors['email'] = 'Email должен быть не длиннее 100 символов.';
}

// Дата рождения
if (empty($_POST['birthdate'])) {
    $errors['birthdate'] = 'Заполните дату рождения.';
} else {
    $birthdate = DateTime::createFromFormat('Y-m-d', $_POST['birthdate']);
    if (!$birthdate) {
        $errors['birthdate'] = 'Неверный формат даты. Используйте ГГГГ-ММ-ДД.';
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

// Биография (опционально, может быть пустой)
$bio = !empty($_POST['bio']) ? $_POST['bio'] : null;
if (!empty($_POST['bio']) && strlen($_POST['bio']) > 5000) {
    $errors['bio'] = 'Биография должна быть не длиннее 5000 символов.';
}

// Языки программирования
if (empty($_POST['languages'])) {
    $errors['languages'] = 'Выберите хотя бы один язык программирования.';
}

// Контракт (согласие)
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
    
    // Начинаем транзакцию
    $db->beginTransaction();
    
    // Вставка данных в таблицу application
    $stmt = $db->prepare("INSERT INTO application (full_name, phone, email, birth_date, gender, biography, agreed) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['fio'],           // full_name
        $_POST['phone'],         // phone
        $_POST['email'],         // email
        $_POST['birthdate'],     // birth_date
        $_POST['gender'],        // gender
        $bio,                    // biography (может быть NULL)
        1                        // agreed (1 = true, так как чекбокс был отмечен)
    ]);
    
    $applicationId = $db->lastInsertId();
    
    // Получаем ID выбранных языков программирования и вставляем связи
    // Сначала получим все языки из справочника
    $stmt = $db->query("SELECT id, name FROM programming_language");
    $languagesMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $languagesMap[$row['name']] = $row['id'];
    }
    
    // Вставляем связи в таблицу application_language
    $stmt = $db->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
    foreach ($_POST['languages'] as $langName) {
        if (isset($languagesMap[$langName])) {
            $stmt->execute([$applicationId, $languagesMap[$langName]]);
        }
    }
    
    // Подтверждаем транзакцию
    $db->commit();
    
    // Перенаправляем на index.php с параметром save (очищаем POST данные)
    header('Location: index.php?save=1');
    exit();
    
} catch (PDOException $e) {
    // Откатываем транзакцию при ошибке
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $errors['db'] = 'Ошибка базы данных: ' . $e->getMessage();
    include('form.html');
    exit();
}
?>
