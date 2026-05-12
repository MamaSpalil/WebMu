<?php
// Russian strings
return [
    // nav
    "nav.download"     => "Скачать",
    "nav.ranking"      => "Рейтинг",
    "nav.registration" => "Регистрация",
    "nav.market"       => "Рынок",
    "nav.vote"         => "Голосовать",
    "nav.donate"       => "Донат-магазин",
    "nav.about"        => "О сервере",
    "nav.account"      => "Кабинет",
    "nav.login"        => "Вход",
    "nav.logout"       => "Выход",

    // misc
    "footer.tagline"   => "Создай свою легенду на вечном континенте",
    "footer.col.server"    => "Сервер",
    "footer.col.community" => "Сообщество",
    "footer.col.support"   => "Поддержка",
    "footer.forum"         => "Форум",
    "footer.faq"           => "FAQ",
    "maintenance.title"=> "Сервер на технических работах",
    "maintenance.text" => "Мы скоро вернёмся. Загляните чуть позже.",

    // forms / generic
    "form.submit"      => "Подтвердить",
    "form.csrf_invalid"=> "Сессия устарела, попробуйте ещё раз.",
    "form.required"    => "Заполните все обязательные поля.",
    "db.config_example"=> "Используется opt.example.php. Скопируйте его в opt.php и укажите реальные настройки удалённой базы данных.",
    "db.connection_error" => "Нет подключения к удалённой базе данных. Проверьте настройки db_host, db_port, db_user, db_upwd, db_name и odbc_driver в файле opt.php.",
    "errors.db_failed" => "Часть данных не удалось загрузить из базы. Подробности записаны в logs/errors.log.",
    "errors.module_failed" => "Модуль завершился с ошибкой.",
    "errors.details" => "Показать подробности",
    "errors.unhandled" => "Внутренняя ошибка сервера",
    "errors.unhandled_text" => "Что-то пошло не так при обработке запроса. Инцидент записан в журнал. Попробуйте повторить попытку через минуту.",
    "errors.return_home" => "Вернуться на главную",

    // registration
    "reg.title"        => "Создать аккаунт",
    "reg.success"      => "Аккаунт создан! Добро пожаловать на континент.",
    "reg.exists.login" => "Аккаунт с таким именем уже существует.",
    "reg.exists.mail"  => "На этот e-mail уже зарегистрирован аккаунт.",
    "reg.invalid.login"=> "Имя аккаунта: 4–10 символов, латиница и цифры.",
    "reg.invalid.pwd"  => "Пароль: 4–10 символов (ограничение игрового клиента).",
    "reg.invalid.pwd2" => "Пароли не совпадают.",
    "reg.invalid.mail" => "Некорректный e-mail.",
    "reg.invalid.mail2"=> "E-mail не совпадает.",
    "reg.invalid.pin"  => "PIN должен быть из 4 цифр.",
    "reg.invalid.rules"=> "Необходимо принять правила сервера.",
    "reg.rate_limit"   => "Слишком много регистраций с этого IP. Повторите позже.",

    // login
    "login.title"      => "Вход в кабинет",
    "login.bad"        => "Неверный логин или пароль.",
    "login.rate_limit" => "Слишком много попыток входа. Повторите позже.",

    // ranking
    "rank.title"       => "Зал славы",
    "rank.players"     => "Топ игроков",
    "rank.guilds"      => "Топ гильдий",
    "rank.kills"       => "Топ убийц",
    "rank.online"      => "Онлайн сейчас",

    // vote
    "vote.title"       => "Голосование за сервер",
    "vote.thanks"      => "Спасибо за голос! Награда начислена.",
    "vote.cooldown"    => "Вы уже голосовали недавно. Попробуйте позже.",
    "vote.no_account"  => "Войдите в аккаунт, чтобы получить награду.",
    "vote.rate_limit"  => "Превышен суточный лимит голосов. Попробуйте позже.",

    // donate
    "donate.title"     => "Донат-магазин",
    "donate.bought"    => "Покупка совершена. Предмет начислен на склад.",
    "donate.no_funds"  => "Недостаточно средств.",
    "donate.no_item"   => "Товар не найден.",
    "donate.rate_limit"=> "Слишком много покупок подряд. Попробуйте позже.",

    // account
    "acc.title"        => "Личный кабинет",
    "acc.balances"     => "Баланс",
    "acc.password"     => "Сменить пароль",
    "acc.password_ok"  => "Пароль изменён.",
    "acc.password_bad" => "Текущий пароль не подходит.",
    "acc.rate_limit"   => "Слишком много попыток смены пароля. Повторите позже.",
];
