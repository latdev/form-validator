<?php
require_once(__DIR__ . '/vendor/autoload.php');

use Latdev\Validation\{ Validator , GroupValidator };

$db = new class {
    function Scalar($sql, ...$arg) {
        return 0;
    }
};

$_POST['username'] = 'Somik';


$username = (new Validator('username', $_POST['username']))
    ->required('Обязательное поле')
    ->minimumLength(4, 'Имя пользователя должно состаять минимум из 4 знаков')
    ->maximumLength(50, 'Слишком длинное имя пользователя')
    ->alpha('Принимаются только буквы английского алфавита');

$username2 = Validator::after($username)
    ->custom(function ($value) use ($db) {
        $exists = $db->Scalar('SELECT count(*) `exists` FROM users WHERE `login` = ? LIMIT 1', $value);
        return $exists < 1;
    }, 'Этот логин уже используется');


$password = (new Validator('password', $_POST['password']))
    ->required('Обязательное поле')
    ->minimumLength(8, 'Пароль не менее 8 знаков');

$passagain = (new Validator('passagain', $_POST['passagain']))
    ->required('Обязательное поле')
    ->minimumLength(8, 'Пароль не менее 8 знаков')
    ->compare($password, 'Пароли не совпадают');

$email = (new Validator('email', $_POST['email']))
    ->required('Обязательное поле')
    ->validMail('Должно содержать Email адрес');

$valid_domains = ['gmail.com', 'yandex.ru', 'ya.ru', 'yandex.com', 'ya.com', 'mail.ru'];

$email2 = Validator::after($email)
    ->custom(function ($value) use ($valid_domains) {
        $email_domain = substr($value, strpos($value, '@') + 1);
        foreach ($valid_domains as $domain) {
            if (strcasecmp($domain, $email_domain) === 0) {
                return true;
            }
        }
        return false;
    }, 'Можно регистрироваться с почтовых адресов наших партнёров - ' . join(', ', $valid_domains))
    ->custom(function ($value) use ($db) {
        $exists = $db->Scalar('SELECT count(*) `exists` FROM users WHERE `email` = ? LIMIT 1', $value);
        return $exists < 1;
    }, 'Такой Email уже использован ранеее');

$eula = (new Validator('eula', $_POST['rules']))
    ->checked('Укажите что вы обязуетесь соблюдать правила');

$valid = new GroupValidator($username2, $password, $passagain, $email2, $eula);

// header('Content-Type: text/plain; charset=UTF-8');
echo "<pre>" . print_r($valid,1) . "</pre>";



if ($valid->validate()) {
    echo  "\n\n----==== OK ====----\n\n";
} else {
    echo  "\n\n----==== ERRORS ====----\n\n";
    echo "<pre>" . print_r($valid->getErrors(), 1) . "</pre>";
}
