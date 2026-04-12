error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

// Показываем сообщение об успехе, если есть параметр save
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['save'])) {
    $success = 'Спасибо, данные успешно сохранены!';
}

// Подключаем HTML форму
include('form.html');
?>
